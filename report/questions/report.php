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
 * CAPQuiz questions report class.
 *
 * @package     capquizreport_questions
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace capquizreport_questions;

use context_course;
use mod_capquiz\report\capquiz_attempts_report;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/capquiz/report/attemptsreport.php');
require_once(__DIR__ . '/questions_form.php');
require_once(__DIR__ . '/questions_table.php');
require_once(__DIR__ . '/questions_options.php');

/**
 * The capquiz questions report provides summary information about the questions in a capquiz (mainly ratings).
 *
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquizreport_questions_report extends capquiz_attempts_report {

    /**
     * Displays the full report
     * @param capquiz $capquiz capquiz object
     * @param stdClass $cm - course_module object
     * @param stdClass $course - course object
     * @param string $download - type of download being requested
     */
    public function display($capquiz, $cm, $course, $download) {
        global $OUTPUT, $DB;

        list($studentsjoins) = $this->init(
            'questions', 'capquizreport_questions\capquizreport_questions_settings_form', $capquiz, $cm, $course);

        $this->options = new capquizreport_questions_options('questions', $capquiz, $cm, $course);

        if ($fromform = $this->form->get_data()) {
            $this->options->process_settings_from_form($fromform);

        } else {
            $this->options->process_settings_from_params();
        }

        $this->form->set_data($this->options->get_initial_form_data());

        // Load the required questions.
        $questions = capquiz_report_get_questions($capquiz);

        // Prepare for downloading, if applicable.
        $courseshortname = format_string($course->shortname, true,
            array('context' => context_course::instance($course->id)));

        $table = new capquizreport_questions_table($capquiz, $this->context,
            $this->options, $studentsjoins, $questions, $this->options->get_url());
        $filename = capquiz_report_download_filename(get_string('questionsfilename', 'capquizreport_questions'),
            $courseshortname, $capquiz->name());
        $table->is_downloading($this->options->download, $filename,
            $courseshortname . ' ' . format_string($capquiz->name(), true));
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

        $hasquestions = capquiz_has_questions($capquiz->id());
        // Start output.
        if (!$table->is_downloading()) {
            // Only print headers if not asked to download data.
            $this->print_standard_header_and_messages($cm, $course, $capquiz,
                $this->options, $hasquestions, $hasstudents);

            // Print the display options.
            $this->form->display();
        }

        if ($hasquestions && !empty($questions) && ($hasstudents || $this->options->attempts == self::ALL_WITH)) {

            $table->setup_sql_queries($studentsjoins);

            // Define table columns.
            $columns = array();
            $headers = array();

            if ($table->is_downloading()) {
                $columns[] = 'attemptid';
                $headers[] = get_string('attemptid', 'capquizreport_questions');
            }

            $this->add_questionid_column($columns, $headers);
            $this->add_question_rating_columns($columns, $headers);

            if ($table->is_downloading()) {
                $columns[] = 'questionprevratingmanual';
                $headers[] = get_string('questionprevratingmanual', 'capquizreport_questions');
            }

            $this->add_moodlequestionid_column($columns, $headers);

            if ($this->options->showqtext) {
                $columns[] = 'question';
                $headers[] = get_string('question', 'capquizreport_questions');
            }

            $columns[] = 'timecreated';
            $headers[] = get_string('timecreated', 'capquizreport_questions');

            $table->define_columns($columns);
            $table->define_headers($headers);
            $table->sortable(true, 'uniqueidquestion');

            // Set up the table.
            $table->define_baseurl($this->options->get_url());

            $this->configure_user_columns($table);

            $table->no_sorting('answerstate');
            $table->no_sorting('question');

            $table->set_attribute('id', 'responses');

            $table->collapsible(true);

            $table->out($this->options->pagesize, true);
        }
        return true;
    }

    /**
     * Outputs the things you commonly want at the top of a capquiz report.
     *
     * Calls through to {@link print_header_and_tabs()} and then
     * outputs the standard group selector, number of attempts summary,
     * and messages to cover common cases when the report can't be shown.
     *
     * @param \stdClass $cm the course_module information.
     * @param \stdClass $course the course settings.
     * @param \stdClass $capquiz the capquiz settings.
     * @param mod_quiz_attempts_report_options $options the current report settings.
     * @param bool $hasquestions whether there are any questions in the capquiz.
     * @param bool $hasstudents whether there are any relevant students.
     */
    protected function print_standard_header_and_messages($cm, $course, $capquiz,
                                                          $options, $hasquestions, $hasstudents) {
        global $OUTPUT;

        echo $this->print_header_and_tabs($cm, $course, $capquiz, $this->mode);

        if (!$hasquestions) {
            echo capquiz_no_questions_message($capquiz, $cm, $this->context);
        } else if (!$capquiz->is_published()) {
            echo capquiz_not_published_message($capquiz, $cm, $this->context);
        } else if (!$hasstudents) {
            echo $OUTPUT->notification(get_string('nostudentsyet'));
        }

    }

    /**
     * Adds column with question rating
     *
     * @param array $columns columns to be added
     * @param array $headers column headers
     */
    protected function add_question_rating_columns(array &$columns, array &$headers) {
        $this->add_question_rating_column($columns, $headers);
        $this->add_question_previous_rating_column($columns, $headers);
    }

    /**
     * Adds column with question rating
     *
     * @param array $columns columns to be added
     * @param array $headers column headers
     */
    protected function add_question_rating_column(array &$columns, array &$headers) {
        $columns[] = 'questionrating';
        $headers[] = get_string('questionrating', 'capquiz');
    }

    /**
     * Adds column with previous question ratings
     *
     * @param array $columns columns to be added
     * @param array $headers column headers
     */
    protected function add_question_previous_rating_column(array &$columns, array &$headers) {
        $columns[] = 'questionprevrating';
        $headers[] = get_string('questionprevrating', 'capquizreport_questions');
    }
}
