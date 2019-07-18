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
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_capquiz\capquiz;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/gradelib.php');

function capquiz_add_instance(stdClass $modformdata) {
    global $DB;
    $modformdata->time_modified = time();
    $modformdata->time_created = time();
    $modformdata->published = false;
    $modformdata->question_list_id = null;
    $modformdata->question_usage_id = null;
    return $DB->insert_record('capquiz', $modformdata);
}

function capquiz_update_instance(stdClass $capquiz) {
    global $DB;
    $capquiz->id = $capquiz->instance;
    $DB->update_record('capquiz', $capquiz);
    capquiz_update_grades($capquiz);
    return true;
}

function capquiz_delete_instance(int $cmid) {
    $capquiz = new capquiz($cmid);
    if ($capquiz) {
        $quba = $capquiz->question_usage();
        \question_engine::delete_questions_usage_by_activity($quba->get_id());
    }
}

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
        $qlist = $DB->get_record('capquiz_question_list', ['capquiz_id' => $instance->id]);
        if (!$qlist) {
            continue;
        }
        \question_engine::delete_questions_usage_by_activity($qlist->question_usage_id);
        $users = $DB->get_records('capquiz_user', ['capquiz_id' => $instance->id]);
        foreach ($users as $user) {
            $DB->delete_records('capquiz_attempt', ['user_id' => $user->id]);
        }
        $DB->delete_records('capquiz_user', ['capquiz_id' => $instance->id]);
        $qlist->question_usage_id = null;
        $DB->update_record('capquiz_question_list', $qlist);
    }
    $status[] = ['component' => $strmodname, 'item' => $strdeleteattempts, 'error' => false];
    return $status;
}

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

    // Require {@link questionlib.php}
    // Included here as we only ever want to include this file if we really need to.
    require_once($CFG->libdir . '/questionlib.php');

    question_extend_settings_navigation($capquiznode, $PAGE->cm->context)->trim_if_empty();
}

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

function capquiz_reset_gradebook($courseid, $type = '') {
    global $DB;
    $instances = $DB->get_records('capquiz', ['course' => $courseid]);
    foreach ($instances as $instance) {
        capquiz_grade_item_update($instance, 'reset');
    }
}

function capquiz_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_BACKUP_MOODLE2:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_USES_QUESTIONS:
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        default:
            return false;
    }
}
