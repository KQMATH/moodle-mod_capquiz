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
 * This file defines the capquiz questions table for showing question data.
 *
 * @package     capquizreport_questions
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace capquizreport_questions;

use core\context;
use core\dml\sql_join;
use mod_capquiz\report\capquiz_attempts_report;
use mod_capquiz\report\capquiz_attempts_report_options;
use mod_capquiz\report\capquiz_attempts_report_table;
use moodle_url;
use quiz_responses_options;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/capquiz/report/attemptsreport_table.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');


/**
 * This is a table subclass for displaying the capquiz question report.
 *
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquizreport_questions_table extends capquiz_attempts_report_table {

    /**
     * Constructor.
     *
     * @param object $capquiz
     * @param context $context
     * @param capquiz_attempts_report_options $options
     * @param sql_join $studentsjoins
     * @param array $questions
     * @param moodle_url $reporturl
     */
    public function __construct($capquiz, $context, capquiz_attempts_report_options $options,
                                sql_join $studentsjoins, $questions, $reporturl) {
        parent::__construct('mod-capquiz-report-questions-report', $capquiz, $context,
            $options, $studentsjoins, $questions, $reporturl);
    }

    /**
     * Take the data returned from the db_query and go through all the rows
     * processing each col using either col_{columnname} method or other_cols
     * method or if other_cols returns NULL then put the data straight into the
     * table.
     *
     * This overwrites the parent method because full SQL query may fail on Mysql
     * because of the limit in the number of tables in the join. Therefore we only
     * join 59 tables in the main query and add the rest here.
     */
    public function build_table(): void {
        if (!$this->rawdata) {
            return;
        }
        $this->strtimeformat = str_replace(',', ' ', get_string('strftimedatetimeseconds', 'capquiz'));
        parent::build_table();
    }

    /**
     * Format the submission and feedback columns.
     *
     * @param string $colname
     * @param stdClass $attempt
     */
    public function other_cols(string $colname, stdClass $attempt): ?string {
        return match ($colname) {
            'question' => $this->data_col($attempt->slot, 'questionsummary', $attempt),
            default => null,
        };
    }

    /**
     * Format a single column, used in other_cols
     *
     * @param int $slot  attempts slot
     * @param string $field
     * @param stdClass $attempt
     */
    public function data_col(int $slot, string $field, stdClass $attempt): string {
        if ($attempt->usageid == 0) {
            return '-';
        }
        $value = $this->field_from_extra_data($attempt, $slot, $field);
        $summary = $value !== null ? trim($value) : '-';
        if ($this->is_downloading() && $this->is_downloading() != 'html') {
            return $summary;
        }
        $summary = s($summary);
        if ($this->is_downloading()) {
            return $summary;
        }
        if ($field === 'questionsummary') {
            return $this->make_preview_link($summary, $attempt, $slot);
        }
        return $summary;
    }

    /**
     * Column text from the extra data loaded in load_extra_data(), before html formatting etc.
     *
     * @param stdClass $attempt
     * @param int $slot
     * @param string $field
     */
    protected function field_from_extra_data(stdClass $attempt, int $slot, string $field): string {
        if (!isset($this->lateststeps[$attempt->usageid][$slot])) {
            return '-';
        }
        $stepdata = $this->lateststeps[$attempt->usageid][$slot];
        if (property_exists($stepdata, $field . 'full')) {
            $value = $stepdata->{$field . 'full'};
        } else {
            $value = $stepdata->$field;
        }
        return $value;
    }

    /**
     * Generate the display of the answer state column.
     *
     * @param stdClass $attempt the table row being output.
     */
    public function col_answerstate(stdClass $attempt): string {
        if ($attempt->attempt === null || $attempt->usageid === 0) {
            return '-';
        }
        $state = $this->slot_state($attempt, $attempt->slot);
        if ($this->is_downloading()) {
            return $state->__toString();
        } else {
            return $this->make_review_link($state, $attempt, $attempt->slot);
        }
    }


    /**
     * Generate the display of the question rating column.
     *
     * @param stdClass $attempt the table row being output.
     */
    public function col_questionrating(stdClass $attempt): string {
        return $attempt->questionrating ?: '-';
    }

    /**
     * Generate the display of the time created (actually time answered) rating column.
     *
     * @param stdClass $attempt the table row being output.
     */
    public function col_timecreated(stdClass $attempt): string {
        return $attempt->attempt ? userdate($attempt->timeanswered, $this->strtimeformat) : '-';
    }

    /**
     * Generate the display of the time created (actually time answered) rating column.
     *
     * @param stdClass $attempt the table row being output.
     */
    public function col_attemptid(stdClass $attempt) {
        return $attempt->attempt ?: '-';
    }

    /**
     * Generate the display of the previous question rating column.
     *
     * @param stdClass $attempt the table row being output.
     */
    public function col_questionprevrating(stdClass $attempt): string {
        global $OUTPUT;
        if ($attempt->questionprevrating) {
            $warningalt = get_string('rating_manually_updated', 'capquizreport_questions');
            $warningicon = $OUTPUT->pix_icon('i/warning', $warningalt, 'moodle', ['class' => 'icon']);
            if (!$this->is_downloading() && $attempt->manualprevqrating) {
                return $warningicon . $attempt->questionprevrating;
            } else {
                return $attempt->questionprevrating;
            }
        } else {
            return '-';
        }
    }

    /**
     * Generate the display of the previous question rating column.
     *
     * @param stdClass $attempt the table row being output.
     */
    public function col_prevquestionrating(stdClass $attempt): string {
        global $OUTPUT;
        if ($attempt->questionprevrating) {
            $warningalt = get_string('rating_manually_updated', 'capquizreport_questions');
            $warningicon = $OUTPUT->pix_icon('i/warning', $warningalt, 'moodle', ['class' => 'icon']);
            if (!$this->is_downloading() && $attempt->manualprevqrating) {
                return $warningicon . $attempt->questionprevrating;
            } else {
                return $attempt->questionprevrating;
            }
        } else {
            return '-';
        }
    }

    /**
     * Generate the display of the previous question rating column.
     *
     * @param stdClass $attempt the table row being output.
     */
    public function col_prevquestionprevrating(stdClass $attempt): string {
        global $OUTPUT;
        if ($attempt->questionprevrating) {
            $warningalt = get_string('rating_manually_updated', 'capquizreport_questions');
            $warningicon = $OUTPUT->pix_icon('i/warning', $warningalt, 'moodle', ['class' => 'icon']);
            if (!$this->is_downloading() && $attempt->manualprevqrating) {
                return $warningicon . $attempt->questionprevrating;
            } else {
                return $attempt->questionprevrating;
            }
        } else {
            return '-';
        }
    }

    /**
     * Generate the display of the previous question manual rating column.
     *
     * @param stdClass $attempt the table row being output.
     */
    public function col_questionprevratingmanual(stdClass $attempt): string {
        if ($attempt->manualprevqrating === null) {
            return '-';
        }
        return get_string($attempt->manualprevqrating ? 'true' : 'false', 'capquiz');
    }

    /**
     * Contruct all the parts of the main database query.
     *
     * @param sql_join $allowedstudentsjoins (joins, wheres, params) defines allowed users for the report.
     * @return array 4 elements ($fields, $from, $where, $params) that can be used to build the actual database query.
     */
    public function base_sql(sql_join $allowedstudentsjoins): array {
        global $DB;
        list($fields, $from, $where, $params) = parent::base_sql($allowedstudentsjoins);
        $fields1 = ',
                    1 AS identifier,
                    ca.time_answered AS timecreated,
                    cqr.rating AS questionrating,
                    pcqr.rating AS questionprevrating,
                    pcqr.manual AS manualprevqrating,
                    cq2.id AS questionid,
                    cq2.question_id AS moodlequestionid';

        $from1 = "\nJOIN {capquiz_question_rating} cqr ON cqr.id = ca.question_rating_id";
        $from1 .= "\nJOIN {capquiz_question_rating} pcqr ON pcqr.id = ca.question_prev_rating_id";
        $from1 .= "\nJOIN {capquiz_question} cq2 ON cq2.id = cqr.capquiz_question_id";

        $sql1 = "SELECT {$fields}{$fields1}
                 \nFROM ({$from}{$from1})
                 \nWHERE {$where}";

        $fields2 = ',
                    2 AS identifier,
                    ca.time_answered AS timecreated,
                    cqr.rating AS questionrating,
                    pcqr.rating AS questionprevrating,
                    pcqr.manual AS manualprevqrating,
                    cq2.id AS questionid,
                    cq2.question_id AS moodlequestionid';

        $from2 = "\nJOIN {capquiz_question_rating} cqr ON cqr.id = ca.prev_question_rating_id";
        $from2 .= "\nJOIN {capquiz_question_rating} pcqr ON pcqr.id = ca.prev_question_prev_rating_id";
        $from2 .= "\nJOIN {capquiz_question} cq2 ON cq2.id = cqr.capquiz_question_id";

        $sql2 = "SELECT {$fields}{$fields2}
                 \nFROM {$from}{$from2}
                 \nWHERE {$where}";

        $fields = 'DISTINCT ' . $DB->sql_concat('userid', "'#'", 'COALESCE(attempt, 0)', "'#'", 'identifier')
            . ' AS uniqueidquestion,';
        $fields .= "ratings.*";
        $from = "(\n{$sql1} \nUNION ALL\n {$sql2}) AS ratings";

        list($from, $params) = uniquify_sql_params($from, $params);
        return [$fields, $from, '1=1', $params];
    }

    /**
     * Does this report require the detailed information for each question from the question_attempts_steps table?
     */
    protected function requires_latest_steps_loaded(): bool {
        return (bool)$this->options->showqtext;
    }
}
