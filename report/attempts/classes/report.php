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

declare(strict_types=1);

namespace capquizreport_attempts;

use cm_info;
use core\output\html_writer;
use mod_capquiz\capquiz;
use mod_capquiz\capquiz_slot;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/capquiz/classes/local/reports/report.php');
require_once($CFG->dirroot . '/mod/capquiz/report/attempts/classes/form.php');
require_once($CFG->dirroot . '/mod/capquiz/report/attempts/classes/options.php');
require_once($CFG->dirroot . '/mod/capquiz/report/attempts/classes/table.php');

/**
 * The attempts report provides summary information about each attempt in a capquiz.
 *
 * @package     capquizreport_attempts
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report implements \mod_capquiz\local\reports\report {
    /**
     * Display attempts report.
     *
     * @param capquiz $capquiz
     * @param cm_info $cm
     * @param \stdClass $course
     * @return void
     */
    public function display(capquiz $capquiz, cm_info $cm, \stdClass $course): void {
        global $DB, $CFG, $OUTPUT, $PAGE;
        $context = \core\context\module::instance($cm->id);
        $studentsjoins = get_enrolled_with_capabilities_join($context);
        $baseurl = new \core\url('/mod/capquiz/report.php', [
            'id' => $context->instanceid,
            'reporttype' => 'attempts',
        ]);
        $form = new form($baseurl, ['capquiz' => $capquiz, 'context' => $context]);
        $options = new options($capquiz, $cm, $course);
        if ($fromform = $form->get_data()) {
            $options->process_settings_from_form($fromform);
        } else {
            $options->process_settings_from_params();
        }
        $form->set_data($options->get_initial_form_data());
        $questions = capquiz_report_get_questions($capquiz);
        $courseshortname = format_string($course->shortname, true, [
            'context' => \core\context\course::instance($course->id),
        ]);
        $table = new table($capquiz, $context, $options, $studentsjoins, $questions, $options->get_url());
        $filenamesuffix = get_string('attemptsfilename', 'capquizreport_attempts');
        $capquizname = format_string($capquiz->get('name'));
        $filename = "$courseshortname - $capquizname - $filenamesuffix";
        $table->is_downloading($options->download, $filename, "$courseshortname $capquizname");
        if ($table->is_downloading()) {
            raise_memory_limit(MEMORY_EXTRA);
        }
        $hasstudents = false;
        if (!empty($studentsjoins->joins)) {
            $sql = "SELECT DISTINCT u.id
                    FROM {user} u
                    $studentsjoins->joins
                    WHERE $studentsjoins->wheres";
            $hasstudents = $DB->record_exists_sql($sql, $studentsjoins->params);
        }
        $hasquestions = capquiz_slot::count_records(['capquizid' => $capquiz->get('id')]) > 0;
        if (!$table->is_downloading()) {
            echo $OUTPUT->header();
            $PAGE->set_title($capquiz->get('name'));
            $PAGE->set_heading($course->fullname);
            $title = get_string('pluginname', 'capquizreport_attempts') . ' ' . get_string('report');
            echo $OUTPUT->heading(format_string($title, true, ['context' => \core\context\module::instance($cm->id)]));
            echo html_writer::div(get_string('attemptsnum', 'quiz', capquiz_report_num_attempt($capquiz->get('id'))));
            if (!$hasquestions) {
                echo capquiz_no_questions_message($cm, $context);
            } else if (!$hasstudents) {
                echo $OUTPUT->notification(get_string('nostudentsyet'));
            }
            $form->display();
        }
        if (!$hasquestions || empty($questions)) {
            return;
        }
        if (!$hasstudents && $options->attempts !== \mod_capquiz\local\reports\options::ALL_WITH) {
            return;
        }
        $table->setup_sql_queries($studentsjoins);
        $columns = [];
        $headers = [];
        if (!$table->is_downloading() && $options->checkboxcolumn) {
            $columns[] = 'checkbox';
            $headers[] = null;
        }
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
        // Some extra fields are always displayed when downloading because there's no space constraint,
        // therefore do not include in extra-field list.
        $fields = \core_user\fields::for_identity($context);
        if ($table->is_downloading()) {
            $fields = $fields->including('institution', 'department', 'email');
        }
        foreach ($fields->get_required_fields() as $field) {
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
            $columns[] = 'userid';
            $headers[] = get_string('userid', 'capquiz');
            $columns[] = 'questionid';
            $headers[] = get_string('moodlequestionid', 'capquiz');
        }
        if ($options->showansstate) {
            $columns[] = 'answerstate';
            $headers[] = get_string('answerstate', 'capquizreport_attempts');
        }
        if ($options->showgrade) {
            $columns[] = 'grade';
            $headers[] = get_string('gradenoun');
        }
        if ($options->showurating) {
            $columns[] = 'userrating';
            $headers[] = get_string('userrating', 'capquiz');
        }
        if ($options->showuprevrating) {
            $columns[] = 'userprevrating';
            $headers[] = get_string('userprevrating', 'capquiz');
        }
        if ($options->showqprevrating) {
            $columns[] = 'questionprevrating';
            $headers[] = get_string('questionprevrating', 'capquiz');
        }
        if ($table->is_downloading()) {
            $columns[] = 'questionprevratingmanual';
            $headers[] = get_string('questionprevratingmanual', 'capquiz');
            $columns[] = 'timeanswered';
            $headers[] = get_string('timeanswered', 'capquiz');
            $columns[] = 'timereviewed';
            $headers[] = get_string('timereviewed', 'capquiz');
        }
        if ($options->showqtext) {
            $columns[] = 'question';
            $headers[] = get_string('question');
        }
        if ($options->showresponses) {
            $columns[] = 'response';
            $headers[] = get_string('response', 'quiz');
        }
        if ($options->showright) {
            $columns[] = 'right';
            $headers[] = get_string('rightanswer', 'question');
        }
        $table->define_columns($columns);
        $table->define_headers($headers);
        $table->sortable(true, 'uniqueid');
        $table->define_baseurl($options->get_url());
        $table->column_suppress('picture');
        $table->column_suppress('fullname');
        foreach (\core_user\fields::for_identity($context)->get_required_fields() as $field) {
            $table->column_suppress($field);
        }
        $table->column_class('picture', 'picture');
        $table->column_class('lastname', 'bold');
        $table->column_class('firstname', 'bold');
        $table->column_class('fullname', 'bold');
        $table->no_sorting('answerstate');
        $table->no_sorting('question');
        $table->no_sorting('response');
        $table->no_sorting('right');
        $table->set_attribute('id', 'responses');
        $table->collapsible(true);
        $table->out($options->pagesize, true);
    }
}
