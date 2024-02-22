<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains functions used by the capquiz interface
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_capquiz\capquiz;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/gradelib.php');

/**
 * Add this capquiz instance to the database
 *
 * @param stdClass $modformdata The data submitted from the form
 * @return bool|int
 */
function capquiz_add_instance(stdClass $modformdata) {
    global $DB;
    $modformdata->time_modified = time();
    $modformdata->time_created = time();
    $modformdata->published = false;
    return $DB->insert_record('capquiz', $modformdata);
}

/**
 * Update this instance in the database
 *
 * @param stdClass $capquiz database record
 * @return bool
 */
function capquiz_update_instance(stdClass $capquiz) {
    global $DB;
    $capquiz->id = $capquiz->instance;
    $DB->update_record('capquiz', $capquiz);
    $capquiz->cmidnumber = get_coursemodule_from_instance('capquiz', $capquiz->id)->id;
    return true;
}

/**
 * Delete this instance from the database
 *
 * @param int $cmid Course module id for the instance to be deleted
 */
function capquiz_delete_instance($id) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/capquiz/locallib.php');
    if (! $capquiz = $DB->get_record('capquiz', array('id' => $id))) {
        return false;
    }
    // Delete question usage from core question.
    question_engine::delete_questions_usage_by_activities(new qubaids_for_capquiz($capquiz->id));
    // Delete any dependent records here.
    $records = array();
    if($records = $DB->get_records('capquiz_question_list', array('capquiz_id' => $capquiz->id))) {
        foreach($records as $record) {
            $recordsquestion = $DB->get_records('capquiz_question', array('question_list_id' => $record->id));
            foreach($recordsquestion as $recordquestion) {
                if (! $DB->delete_records('capquiz_question_rating', array('capquiz_question_id' => $recordquestion->id))) {
                    return false;
                }
            }
            if (! $DB->delete_records('capquiz_question', array('question_list_id' => $record->id))) {
                return false;
            }
        }
    }
    $records = array();
    if($records = $DB->get_records('capquiz_user', array('capquiz_id' => $capquiz->id))) {
        foreach($records as $record) {
            if (! $DB->delete_records('capquiz_attempt', array('user_id' => $record->id))) {
                return false;
            }
            if (! $DB->delete_records('capquiz_user_rating', array('capquiz_user_id' => $record->id))) {
                return false;
            }
        }
    }
    if (! $DB->delete_records('capquiz', array('id' => $capquiz->id))) {
        return false;
    }
    if (! $DB->delete_records('capquiz_question_list', array('capquiz_id' => $capquiz->id))) {
        return false;
    }
    if (! $DB->delete_records('capquiz_user', array('capquiz_id' => $capquiz->id))) {
        return false;
    }
    if (! $DB->delete_records('capquiz_question_selection', array('capquiz_id' => $capquiz->id))) {
        return false;
    }
    if (! $DB->delete_records('capquiz_rating_system', array('capquiz_id' => $capquiz->id))) {
        return false;
    }
    if (! $DB->delete_records('event', array('modulename' => 'capquiz', 'instance' => $capquiz->id))) {
        return false;
    }

    return true;
}

/**
 * Implementation of the reset course functionality, delete all the assignment submissions for course $data->courseid.
 *
 * @param object $data
 * @return array containing the statusreport from execution
 * @throws coding_exception
 * @throws dml_exception
 */
function capquiz_reset_userdata($data) {
    global $DB;
    $status = [];
    $strmodname = get_string('modulenameplural', 'capquiz');
    $strdeletegrades = get_string('deleted_grades', 'capquiz');
    $strdeleteattempts = get_string('deleted_attempts', 'capquiz');

    capquiz_reset_gradebook($data->courseid);
    $status[] = ['component' => $strmodname, 'item' => $strdeletegrades, 'error' => false];

    $instances = $DB->get_records('capquiz', ['course' => $data->courseid]);
    foreach ($instances as $instance) {
        $users = $DB->get_records('capquiz_user', ['capquiz_id' => $instance->id]);
        if (!$users) {
            continue;
        }
        foreach ($users as $user) {
            \question_engine::delete_questions_usage_by_activity($user->question_usage_id);
            $DB->delete_records('capquiz_attempt', ['user_id' => $user->id]);
        }
        $DB->delete_records('capquiz_user', ['capquiz_id' => $instance->id]);
    }
    $status[] = ['component' => $strmodname, 'item' => $strdeleteattempts, 'error' => false];
    return $status;
}

/**
 * Finds all assignment notifications that have yet to be mailed out, and mails them.
 *
 * Cron function to be run periodically according to the moodle cron.
 * @return bool
 */
function capquiz_cron() {
    return true;
}

/**
 * This function extends the settings navigation block for the site.
 *
 * @param settings_navigation $settings
 * @param navigation_node $capquiznode
 * @return void
 */
function capquiz_extend_settings_navigation($settings, $capquiznode) {
    global $PAGE, $CFG;

    // Require {@link https://github.com/moodle/moodle/blob/master/lib/questionlib.php}
    // Included here as we only ever want to include this file if we really need to.
    require_once($CFG->libdir . '/questionlib.php');

    question_extend_settings_navigation($capquiznode, $PAGE->cm->context)->trim_if_empty();
}

/**
 * Get an upto date list of user grades and feedback for the gradebook.
 *
 * @param stdClass $capquiz database record
 * @param int $userid int or 0 for all users
 * @return array
 */
function capquiz_get_user_grades(stdClass $capquiz, int $userid = 0) {
    global $DB;
    $params = ['capquiz_id' => $capquiz->id];
    if ($userid > 0) {
        $params['user_id'] = $userid;
    }
    $users = $DB->get_records('capquiz_user', $params);
    if (!$users) {
        return [];
    }
    $grades = [];
    foreach ($users as $user) {
        $grade = new stdClass();
        $grade->userid = $user->user_id;
        $grade->rawgrade = $user->highest_level;
        $grade->dategraded = time();
        $grade->datesubmitted = time();
        $grades[$user->user_id] = $grade;
    }
    return $grades;
}

/**
 * Create grade item for given assignment.
 *
 * @param stdClass $capquiz record with extra cmidnumber
 * @param array $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function capquiz_grade_item_update(stdClass $capquiz, $grades = null) {
    global $DB;
    $capquiz->cmidnumber = get_coursemodule_from_instance('capquiz', $capquiz->id)->id;
    $params = [
        'itemname' => $capquiz->name,
        'idnumber' => $capquiz->cmidnumber
    ];
    $params['gradetype'] = GRADE_TYPE_VALUE;
    $params['grademax'] = 5;
    $params['grademin'] = 0;
    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }
    $status = grade_update('mod/capquiz', $capquiz->course, 'mod', 'capquiz', $capquiz->id, 0, $grades, $params);
    $item = grade_item::fetch([
        'courseid' => $capquiz->course,
        'itemtype' => 'mod',
        'itemmodule' => 'capquiz',
        'iteminstance' => $capquiz->id,
        'outcomeid' => null
    ]);
    $item->gradepass = $capquiz->stars_to_pass;
    $item->update();
    $users = $DB->get_records('capquiz_user', ['capquiz_id' => $capquiz->id]);
    foreach ($users as $user) {
        $user->stars_graded = $user->highest_level;
        $DB->update_record('capquiz_user', $user);
    }
    return $status;
}

/**
 * Update activity grades
 *
 * @param stdClass $capquiz database record
 * @param int $userid specific user only, 0 means all
 * @param bool $nullifnone
 */
function capquiz_update_grades(stdClass $capquiz, int $userid = 0, $nullifnone = true) {
    $grades = capquiz_get_user_grades($capquiz, $userid);
    if ($grades) {
        capquiz_grade_item_update($capquiz, $grades);
    } else if ($userid && $nullifnone) {
        $grade = new stdClass();
        $grade->userid = $userid;
        $grade->rawgrade = null;
        capquiz_grade_item_update($capquiz, [$userid => $grade]);
    } else {
        capquiz_grade_item_update($capquiz);
    }
}

/**
 * Reset activity gradebook
 *
 * @param int $courseid  id of the course to be reset
 * @param string $type Optional type of assignment to limit the reset to a particular assignment type
 */
function capquiz_reset_gradebook($courseid, $type = '') {
    global $DB;
    $instances = $DB->get_records('capquiz', ['course' => $courseid]);
    foreach ($instances as $instance) {
        capquiz_grade_item_update($instance, 'reset');
    }
}


// Ugly hack to make 3.11 and 4.0 work seamlessly.
if (!defined('FEATURE_MOD_PURPOSE')) {
    define('FEATURE_MOD_PURPOSE', 'mod_purpose');
}
if (!defined('MOD_PURPOSE_ASSESSMENT')) {
    define('MOD_PURPOSE_ASSESSMENT', 'assessment');
}

/**
 * Checks if $feature is supported
 *
 * @param string $feature
 * @return mixed
 */
function capquiz_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_BACKUP_MOODLE2:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_USES_QUESTIONS:
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;
        default:
            return false;
    }
}

