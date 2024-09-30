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
 * The file defines a base class to be used to build a report like the overview or responses report, with one row per attempt.
 *
 * @package     mod_capquiz
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz\report;

use context_module;
use core\context\module;
use core\dml\sql_join;
use mod_capquiz\capquiz;
use mod_quiz\local\reports\attempts_report_options;
use mod_quiz\local\reports\attempts_report_options_form;
use moodle_url;
use stdClass;
use table_sql;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/mod/capquiz/report/report.php');

/**
 * Base class for capquiz reports that are basically a table with one row for each attempt.
 *
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class capquiz_attempts_report extends report {
    /** @var int default page size for reports. */
    const DEFAULT_PAGE_SIZE = 30;

    /** @var string constant used for the options, means all users with attempts. */
    const ALL_WITH = 'all_with';
    /** @var string constant used for the options, means only enrolled users with attempts. */
    const ENROLLED_WITH = 'enrolled_with';
    /** @var string constant used for the options, means only enrolled users without attempts. */
    const ENROLLED_WITHOUT = 'enrolled_without';
    /** @var string constant used for the options, means all enrolled users. */
    const ENROLLED_ALL = 'enrolled_any';

    /** @var string the mode this report is. */
    protected $mode;

    /** @var module the capquiz context. */
    protected module $context;

    /** @var attempts_report_options_form The settings form to use. */
    protected $form;

    /** @var attempts_report_options the options affecting this report. */
    protected $options = null;

    /**
     *  Initialise various aspects of this report.
     *
     * @param string $mode
     * @param string $formclass
     * @param object $capquiz
     * @param object $cm
     * @param object $course
     * @return array with four elements:
     *      0 => integer the current group id (0 for none).
     *      1 => \core\dml\sql_join Contains joins, wheres, params for all the students in this course.
     *      2 => \core\dml\sql_join Contains joins, wheres, params for all the students in the current group.
     *      3 => \core\dml\sql_join Contains joins, wheres, params for all the students to show in the report.
     *              Will be the same as either element 1 or 2.
     */
    protected function init(string $mode, string $formclass, $capquiz, $cm, $course) {
        $this->mode = $mode;
        $this->context = context_module::instance($cm->id);
        $studentsjoins = get_enrolled_with_capabilities_join($this->context);
        $this->form = new $formclass($this->get_base_url(), ['capquiz' => $capquiz, 'context' => $this->context]);
        return [$studentsjoins];
    }


    /**
     * Get the base URL for this report.
     */
    protected function get_base_url(): moodle_url {
        return new moodle_url('/mod/capquiz/view_report.php', ['id' => $this->context->instanceid, 'mode' => $this->mode]);
    }

    /**
     * Outputs the things you commonly want at the top of a capquiz report.
     *
     * Calls through to {@see print_header_and_tabs()} and then
     * outputs the standard group selector, number of attempts summary,
     * and messages to cover common cases when the report can't be shown.
     *
     * @param stdClass $cm the course_module information.
     * @param stdClass $course the course settings.
     * @param capquiz $capquiz the capquiz settings.
     * @param attempts_report_options $options the current report settings.
     * @param bool $hasquestions whether there are any questions in the capquiz.
     * @param bool $hasstudents whether there are any relevant students.
     */
    protected function print_standard_header_and_messages(stdClass $cm, stdClass $course, capquiz $capquiz,
                                                          attempts_report_options $options, bool $hasquestions,
                                                          bool $hasstudents): void {
        global $OUTPUT;

        $this->print_header_and_tabs($cm, $course, $capquiz, $this->mode);

        // Print information on the number of existing attempts.
        $strattemptnum = capquiz_num_attempt_summary($capquiz, true);
        if (!empty($strattemptnum)) {
            echo '<div class="quizattemptcounts">' . $strattemptnum . '</div>';
        }

        if (!$hasquestions) {
            echo capquiz_no_questions_message($capquiz, $cm, $this->context);
        } else if (!$capquiz->is_published()) {
            echo capquiz_not_published_message($capquiz, $cm, $this->context);
        } else if (!$hasstudents) {
            echo $OUTPUT->notification(get_string('nostudentsyet'));
        }
    }

    /**
     * Add all the user-related columns to the $columns and $headers arrays.
     *
     * @param table_sql $table the table being constructed.
     * @param array $columns the list of columns. Added to.
     * @param array $headers the columns headings. Added to.
     */
    protected function add_user_columns(table_sql $table, array &$columns, array &$headers): void {
        global $CFG;
        if (!$table->is_downloading() && $CFG->grade_report_showuserimage) {
            $columns[] = 'picture';
            $headers[] = '';
        }
        if (!$table->is_downloading()) {
            $columns[] = 'fullname';
            $headers[] = get_string('name');
        } else {
            $columns[] = 'lastname';
            $headers[] = get_string('lastname');
            $columns[] = 'firstname';
            $headers[] = get_string('firstname');
        }

        // When downloading, some extra fields are always displayed (because
        // there's no space constraint) so do not include in extra-field list.
        $fields = \core_user\fields::for_identity($this->context);
        if ($table->is_downloading()) {
            $fields = $fields->including('institution', 'department', 'email')->get_required_fields();
        }
        $extrafields = $fields->get_required_fields();

        foreach ($extrafields as $field) {
            $columns[] = $field;
            $headers[] = \core_user\fields::get_display_name($field);
        }

        if ($table->is_downloading()) {
            $columns[] = 'institution';
            $headers[] = get_string('institution');

            $columns[] = 'department';
            $headers[] = get_string('department');

            $columns[] = 'email';
            $headers[] = get_string('email');
        }
    }

    /**
     * Add the state column to the $columns and $headers arrays.
     *
     * @param array $columns the list of columns. Added to.
     * @param array $headers the columns headings. Added to.
     */
    protected function add_questionid_column(array &$columns, array &$headers): void {
        $columns[] = 'questionid';
        $headers[] = get_string('questionid', 'capquiz');
    }

    /**
     * Add the state column to the $columns and $headers arrays.
     *
     * @param array $columns the list of columns. Added to.
     * @param array $headers the columns headings. Added to.
     */
    protected function add_moodlequestionid_column(array &$columns, array &$headers): void {
        $columns[] = 'moodlequestionid';
        $headers[] = get_string('moodlequestionid', 'capquiz');
    }

    /**
     * Add the state column to the $columns and $headers arrays.
     *
     * @param array $columns the list of columns. Added to.
     * @param array $headers the columns headings. Added to.
     */
    protected function add_uesrid_column(array &$columns, array &$headers): void {
        $columns[] = 'userid';
        $headers[] = get_string('userid', 'capquiz');
    }

    /**
     * Add all the time-related columns to the $columns and $headers arrays.
     *
     * @param array $columns the list of columns. Added to.
     * @param array $headers the columns headings. Added to.
     */
    protected function add_time_columns(array &$columns, array &$headers): void {
        $columns[] = 'timeanswered';
        $headers[] = get_string('timeanswered', 'capquiz');

        $columns[] = 'timereviewed';
        $headers[] = get_string('timereviewed', 'capquiz');
    }

    /**
     * Set the display options for the user-related columns in the table.
     *
     * @param table_sql $table the table being constructed.
     */
    protected function configure_user_columns(table_sql $table): void {
        $table->column_suppress('picture');
        $table->column_suppress('fullname');
        $extrafields = \core_user\fields::for_identity($this->context)->get_required_fields();
        foreach ($extrafields as $field) {
            $table->column_suppress($field);
        }
        $table->column_class('picture', 'picture');
        $table->column_class('lastname', 'bold');
        $table->column_class('firstname', 'bold');
        $table->column_class('fullname', 'bold');
    }

    /**
     * Process any submitted actions.
     *
     * @param stdClass $capquiz
     * @param stdClass $cm the cm object for the capquiz.
     * @param sql_join $allowedjoins (joins, wheres, params) the users whose attempt this user is allowed to modify.
     * @param moodle_url $redirecturl where to redircet to after a successful action.
     */
    protected function process_actions(stdClass $capquiz, stdClass $cm, sql_join $allowedjoins, moodle_url $redirecturl): void {
        if (optional_param('delete', 0, PARAM_BOOL) && confirm_sesskey()) {
            if ($attemptids = optional_param_array('attemptid', [], PARAM_INT)) {
                require_capability('mod/capquiz:deleteattempts', $this->context);
                $this->delete_selected_attempts($capquiz, $cm, $attemptids, $allowedjoins);
                redirect($redirecturl);
            }
        }
    }

    /**
     * Delete the capquiz attempts.
     *
     * @param stdClass $capquiz the capquiz settings. Attempts that don't belong to this capquiz are not deleted.
     * @param stdClass $cm the course_module object.
     * @param array $attemptids the list of attempt ids to delete.
     * @param sql_join $allowedjoins (joins, wheres, params) This list of userids that are visible in the report.
     *      Users can only delete attempts that they are allowed to see in the report.
     *      Empty means all users.
     */
    protected function delete_selected_attempts(stdClass $capquiz, stdClass $cm, array $attemptids, sql_join $allowedjoins) {
        // TODO implement to add support for attempt deletion.
    }
}
