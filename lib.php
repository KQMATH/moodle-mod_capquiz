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
 * @author      Sebastian Gundersen <sebastian@sgundersen.com>
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2025 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Commenting.ValidTags.Invalid

use mod_capquiz\capquiz;
use mod_capquiz\capquiz_attempt;
use mod_capquiz\capquiz_user;
use mod_capquiz\local\helpers\questions;
use mod_capquiz\local\helpers\stars;
use mod_capquiz\question\bank\question_bank_view;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/capquiz/adminlib.php');

/**
 * Create a new CAPQuiz instance.
 *
 * @used-by add_moduleinfo()
 * @param stdClass $moduleinfo The data submitted from the form
 */
function capquiz_add_instance(stdClass $moduleinfo): int {
    $moduleinfo->timecreated = \core\di::get(\core\clock::class)->time();
    $moduleinfo->timemodified = $moduleinfo->timecreated;
    $capquiz = new capquiz(record: $moduleinfo);
    $capquiz->create();
    return $capquiz->get('id');
}

/**
 * Update an existing CAPQuiz instance.
 *
 * @used-by update_moduleinfo()
 * @param stdClass $moduleinfo
 */
function capquiz_update_instance(stdClass $moduleinfo): bool {
    $moduleinfo->id = (int)$moduleinfo->instance;
    $capquiz = new capquiz(record: $moduleinfo);
    return $capquiz->update();
}

/**
 * Delete a CAPQuiz instance.
 *
 * @author Sebastian Gundersen <sebastian@sgundersen.com>
 * @author Sumaiya Javed <sumaiya.javed@catalyst.net.nz>
 * @used-by course_delete_module()
 * @param int $capquizid CAPQuiz ID
 * @return bool
 */
function capquiz_delete_instance(int $capquizid): bool {
    $capquiz = new capquiz($capquizid);
    return $capquiz->delete();
}

/**
 * Standard callback used by questions_in_use.
 *
 * @used-by questions_in_use()
 * @see quiz_questions_in_use()
 * @param array $questionids of question ids.
 * @return bool whether any of these questions are used by any instance of this module.
 */
function capquiz_questions_in_use(array $questionids): bool {
    $qubaidjoin = new qubaid_join(
        from: '{' . capquiz_user::TABLE . '} cu',
        usageidcolumn: 'cu.questionusageid',
    );
    return question_engine::questions_in_use($questionids, $qubaidjoin);
}

/**
 * Implementation of the reset course functionality, delete all the assignment submissions for course $data->courseid.
 *
 * @param stdClass $data
 * @return array containing the statusreport from execution
 */
function capquiz_reset_userdata(stdClass $data): array {
    global $DB;
    $status = [];
    capquiz_reset_gradebook($data->courseid);
    $status[] = [
        'component' => get_string('modulenameplural', 'capquiz'),
        'item' => get_string('deleted_grades', 'capquiz'),
        'error' => false,
    ];
    foreach (capquiz::get_records(['course' => $data->courseid]) as $capquiz) {
        foreach (capquiz_user::get_records(['capquizid' => $capquiz->get('id')]) as $user) {
            question_engine::delete_questions_usage_by_activity($user->get('questionusageid'));
        }
        $DB->delete_records(capquiz_attempt::TABLE, ['capquizid' => $capquiz->get('id')]);
        $DB->delete_records(capquiz_user::TABLE, ['capquizid' => $capquiz->get('id')]);
    }
    $status[] = [
        'component' => get_string('modulenameplural', 'capquiz'),
        'item' => get_string('deleted_attempts', 'capquiz'),
        'error' => false,
    ];
    return $status;
}

/**
 * Generates and returns list of available CAPQuiz report sub-plugins
 *
 * @return array list of valid reports present
 */
function capquiz_report_list(): array {
    static $reportlist;
    if (!empty($reportlist)) {
        return $reportlist;
    }
    $reportlist = [];
    $pluginmanager = new capquiz_plugin_manager('capquizreport');
    $enabledplugins = core_plugin_manager::instance()->get_enabled_plugins('capquizreport');
    foreach ($pluginmanager->get_sorted_plugins_list() as $reportname) {
        if (isset($enabledplugins[$reportname])) {
            $reportlist[] = $reportname;
        }
    }
    return $reportlist;
}

/**
 * This function extends the settings navigation block for the site.
 *
 * @param settings_navigation $settings
 * @param navigation_node $capquiznode
 */
function capquiz_extend_settings_navigation(settings_navigation $settings, navigation_node $capquiznode): void {
    global $PAGE, $CFG;
    $cm = $settings->get_page()->cm;
    if (!has_capability('mod/capquiz:instructor', $cm->context)) {
        return;
    }
    $capquiznode->add_node(navigation_node::create(
        text: get_string('questions', 'capquiz'),
        action: new \core\url('/mod/capquiz/edit.php', ['id' => $cm->id]),
        type: navigation_node::TYPE_SETTING,
        key: 'capquiz_edit',
    ));
    $reportsnode = $capquiznode->add_node(navigation_node::create(
        text: get_string('results', 'quiz'),
        action: new \core\url('/mod/capquiz/report.php', ['id' => $cm->id]),
        type: navigation_node::TYPE_SETTING,
        key: 'capquiz_viewreports',
    ));
    // We could use showchildreninsubmenu = true to show report types in a submenu,
    // but this seems to mess with the styling when a tab in the show more submenu is active.
    // Maybe this changes in a future version of Moodle?
    foreach (capquiz_report_list() as $reporttype) {
        $reportsnode->add_node(navigation_node::create(
            text: get_string('pluginname', "capquizreport_$reporttype"),
            action: new \core\url('/mod/capquiz/report.php', ['id' => $cm->id, 'reporttype' => $reporttype]),
            type: navigation_node::TYPE_SETTING,
            key: "capquiz_viewreport_$reporttype",
            icon: new pix_icon('i/report', ''),
        ));
    }

    require_once($CFG->libdir . '/questionlib.php');
    question_extend_settings_navigation($capquiznode, $PAGE->cm->context)->trim_if_empty();
}

/**
 * Return grade for a given user, or all users.
 * TODO: This function seems to have been implemented incorrectly for a long time.
 *       Need to fix dategraded/datesubmitted. Raw grade has been fixed from 'higheststars' to 'starsgraded'.
 *
 * @param stdClass $capquiz database record
 * @param int $userid int or 0 for all users
 * @see quiz_get_user_grades
 */
function capquiz_get_user_grades(stdClass $capquiz, int $userid = 0): array {
    $params = ['capquizid' => $capquiz->id];
    if ($userid > 0) {
        $params['userid'] = $userid;
    }
    $grades = [];
    foreach (capquiz_user::get_records($params) as $user) {
        $grades[$user->get('userid')] = (object) [
            'userid' => $user->get('userid'),
            'rawgrade' => $user->get('starsgraded'),
            'dategraded' => \core\di::get(\core\clock::class)->time(),
            'datesubmitted' => $user->get('timemodified'),
        ];
    }
    return $grades;
}

/**
 * Update grades in gradebook.
 *
 * @param stdClass $capquiz database record
 * @param int $userid specific user only, 0 means all
 * @param bool $nullifnone
 * @see quiz_update_grades
 */
function capquiz_update_grades(stdClass $capquiz, int $userid = 0, $nullifnone = true): void {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $grades = capquiz_get_user_grades($capquiz, $userid);
    if ($grades) {
        capquiz_grade_item_update($capquiz, $grades);
    } else if ($userid && $nullifnone) {
        $grade = new stdClass();
        $grade->userid = $userid;
        $grade->rawgrade = null;
        capquiz_grade_item_update($capquiz, $grade);
    } else {
        capquiz_grade_item_update($capquiz);
    }
}

/**
 * Create or update the grade item for a given CAPQuiz.
 *
 * @param stdClass $capquiz record with extra cmidnumber
 * @param array|string|null $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 * @see quiz_grade_item_update
 */
function capquiz_grade_item_update(stdClass $capquiz, $grades = null): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    if (!isset($capquiz->cmidnumber)) {
        $capquiz->cmidnumber = get_coursemodule_from_instance('capquiz', $capquiz->id, $capquiz->course)->id;
    }
    $itemdetails = [
        'itemname' => $capquiz->name,
        'idnumber' => $capquiz->cmidnumber,
        'gradetype' => GRADE_TYPE_VALUE,
        'grademax' => stars::get_max_stars($capquiz->starratings),
        'grademin' => 0,
        'gradepass' => $capquiz->starstopass,
    ];
    if ($grades === 'reset') {
        $itemdetails['reset'] = true;
        $grades = null;
    }
    return grade_update('mod/capquiz', $capquiz->course, 'mod', 'capquiz', $capquiz->id, 0, $grades, $itemdetails);
}

/**
 * Remove all grades from gradebook.
 *
 * @param int $courseid id of the course to be reset
 * @param string $type Optional type of assignment to limit the reset to a particular assignment type
 * @see quiz_reset_gradebook
 */
function capquiz_reset_gradebook($courseid, $type = ''): void {
    foreach (capquiz::get_records(['course' => $courseid]) as $capquiz) {
        capquiz_grade_item_update($capquiz->to_record(), 'reset');
    }
}

/**
 * Generates the question bank in a fragment output. This allows
 * the question bank to be displayed in a modal.
 *
 * The only expected argument provided in the $args array is
 * 'querystring'. The value should be the list of parameters
 * URL encoded and used to build the question bank page.
 *
 * The individual list of parameters expected can be found in
 * question_build_edit_resources.
 *
 * @param array $args The fragment arguments.
 * @return string The rendered mform fragment.
 */
function capquiz_output_fragment_capquiz_qbank(array $args): string {
    global $PAGE;
    require_capability('mod/capquiz:instructor', $PAGE->context);
    $querystring = parse_url($args['querystring'], PHP_URL_QUERY);
    $params = [];
    parse_str($querystring, $params);
    $params['cmid'] = $PAGE->cm->id;
    [$url, $contexts, $cmid, $cm, $capquiz, $pagevars] = question_build_edit_resources('editq', '/mod/capquiz/edit.php', $params);
    $extraparams = ['cmid' => $cmid];
    ob_start();
    $qbank = new question_bank_view($contexts, $url, get_course($cm->course), $cm, $pagevars, $extraparams);
    $qbank->display();
    $qbankhtml = ob_get_clean();
    return html_writer::div(html_writer::div($qbankhtml, 'bd'), 'questionbankformforpopup');
}

/**
 * Serve question files.
 *
 * @param stdClass $course
 * @param stdClass $context
 * @param string $component
 * @param string $filearea
 * @param int $questionusageid
 * @param int $slot
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @see quiz_question_pluginfile
 */
function capquiz_question_pluginfile(
    stdClass $course,
    stdClass $context,
    string $component,
    string $filearea,
    int $questionusageid,
    int $slot,
    array $args,
    bool $forcedownload,
    array $options = [],
): void {
    $user = capquiz_user::get_record(['questionusageid' => $questionusageid], MUST_EXIST);
    $cm = get_coursemodule_from_instance('capquiz', $user->get('capquizid'), $course->id, false, MUST_EXIST);
    require_login($course, false, $cm);
    $quba = question_engine::load_questions_usage_by_activity($questionusageid);
    $displayoptions = questions::get_question_display_options(new capquiz($user->get('capquizid')));
    if (!$quba->check_file_access($slot, $displayoptions, $component, $filearea, $args, $forcedownload)) {
        send_file_not_found();
    }
    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/$component/$filearea/$relativepath";
    $file = $fs->get_file_by_hash(sha1($fullpath));
    if (!$file || $file->is_directory()) {
        send_file_not_found();
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Checks if $feature is supported.
 *
 * @param string $feature
 */
function capquiz_supports(string $feature): bool|string|null {
    return match ($feature) {
        FEATURE_MOD_INTRO,
        FEATURE_BACKUP_MOODLE2,
        FEATURE_SHOW_DESCRIPTION,
        FEATURE_USES_QUESTIONS,
        FEATURE_GRADE_HAS_GRADE => true,
        FEATURE_MOD_PURPOSE => MOD_PURPOSE_ASSESSMENT,
        default => null,
    };
}
