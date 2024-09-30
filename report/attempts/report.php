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
 * CAPQuiz attempts report class.
 *
 * @package     capquizreport_attempts
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace capquizreport_attempts;

use context_course;
use mod_capquiz\capquiz;
use mod_capquiz\report\capquiz_attempts_report;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/capquiz/report/attemptsreport.php');
require_once(__DIR__ . '/attempts_form.php');
require_once(__DIR__ . '/attempts_table.php');
require_once(__DIR__ . '/attempts_options.php');

/**
 * The capquiz attempts report provides summary information about each attempt in a capquiz.
 *
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquizreport_attempts_report extends capquiz_attempts_report {

    /**
     * Displays the full report
     * @param capquiz $capquiz capquiz object
     * @param stdClass $cm - course_module object
     * @param stdClass $course - course object
     * @param string $download - type of download being requested
     */
    public function display($capquiz, $cm, $course, $download): bool {
        global $DB;

        list($studentsjoins) = $this->init('attempts', 'capquizreport_attempts\capquizreport_attempts_settings_form',
            $capquiz, $cm, $course);

        $this->options = new capquizreport_attempts_options('attempts', $capquiz, $cm, $course);

        if ($fromform = $this->form->get_data()) {
            $this->options->process_settings_from_form($fromform);
        } else {
            $this->options->process_settings_from_params();
        }

        $this->form->set_data($this->options->get_initial_form_data());

        // Load the required questions.
        $questions = capquiz_report_get_questions($capquiz);

        // Prepare for downloading, if applicable.
        $courseshortname = format_string($course->shortname, true, ['context' => context_course::instance($course->id)]);

        $table = new capquizreport_attempts_table($capquiz, $this->context,
            $this->options, $studentsjoins, $questions, $this->options->get_url());

        $filename = capquiz_report_download_filename(get_string('attemptsfilename', 'capquizreport_attempts'),
            $courseshortname, $capquiz->name());

        $table->is_downloading($this->options->download, $filename, $courseshortname . ' ' . format_string($capquiz->name()));

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

        // phpcs:disable
        // TODO enable when support for attempt deletion is implemented {@see delete_selected_attempts}.
        // $this->process_actions($capquiz, $cm, $studentsjoins, $this->options->get_url());
        // phpcs:enable

        $hasquestions = capquiz_has_questions($capquiz->id());
        // Start output.
        if (!$table->is_downloading()) {
            // Only print headers if not asked to download data.
            $this->print_standard_header_and_messages($cm, $course, $capquiz, $this->options, $hasquestions, $hasstudents);

            // Print the display options.
            $this->form->display();
        }

        if ($hasquestions && !empty($questions) && ($hasstudents || $this->options->attempts == self::ALL_WITH)) {

            $table->setup_sql_queries($studentsjoins);

            // Define table columns.
            $columns = [];
            $headers = [];

            if (!$table->is_downloading() && $this->options->checkboxcolumn) {
                $columns[] = 'checkbox';
                $headers[] = null;
            }

            $this->add_user_columns($table, $columns, $headers);

            if ($table->is_downloading()) {
                $this->add_uesrid_column($columns, $headers);
                $this->add_moodlequestionid_column($columns, $headers);
            }

            if ($this->options->showansstate) {
                $columns[] = 'answerstate';
                $headers[] = get_string('answerstate', 'capquizreport_attempts');
            }

            $this->add_rating_columns($columns, $headers);

            if ($table->is_downloading()) {
                $columns[] = 'questionprevratingmanual';
                $headers[] = get_string('questionprevratingmanual', 'capquizreport_attempts');
            }

            if ($table->is_downloading()) {
                $this->add_time_columns($columns, $headers);
            }

            if ($this->options->showqtext) {
                $columns[] = 'question';
                $headers[] = get_string('question', 'capquizreport_attempts');
            }
            if ($this->options->showresponses) {
                $columns[] = 'response';
                $headers[] = get_string('response', 'capquizreport_attempts');
            }
            if ($this->options->showright) {
                $columns[] = 'right';
                $headers[] = get_string('rightanswer', 'capquizreport_attempts');
            }

            $table->define_columns($columns);
            $table->define_headers($headers);
            $table->sortable(true, 'uniqueid');

            // Set up the table.
            $table->define_baseurl($this->options->get_url());

            $this->configure_user_columns($table);

            $table->no_sorting('answerstate');
            $table->no_sorting('question');
            $table->no_sorting('response');
            $table->no_sorting('right');

            $table->set_attribute('id', 'responses');

            $table->collapsible(true);

            $table->out($this->options->pagesize, true);
        }
        return true;
    }

    /**
     * Adds rating columns to this report
     *
     * @param array $columns columns to be added
     * @param array $headers column headers
     */
    protected function add_rating_columns(array &$columns, array &$headers) {
        if ($this->options->showurating) {
            $this->add_user_rating_column($columns, $headers);
        }
        if ($this->options->showuprevrating) {
            $this->add_user_previous_rating_column($columns, $headers);
        }
        if ($this->options->showqprevrating) {
            $this->add_question_previous_rating_column($columns, $headers);
        }
    }

    /**
     * Adds a user rating column to this report
     *
     * @param array $columns columns to be added
     * @param array $headers column headers
     */
    protected function add_user_rating_column(array &$columns, array &$headers) {
        $columns[] = 'userrating';
        $headers[] = get_string('userrating', 'capquiz');
    }

    /**
     * Adds a column with a users previous rating
     *
     * @param array $columns columns to be added
     * @param array $headers column headers
     */
    protected function add_user_previous_rating_column(array &$columns, array &$headers) {
        $columns[] = 'userprevrating';
        $headers[] = get_string('userprevrating', 'capquizreport_attempts');
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
        $headers[] = get_string('questionprevrating', 'capquizreport_attempts');
    }
}
