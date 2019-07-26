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

use core\dml\sql_join;
use mod_capquiz\report\capquiz_attempts_report;
use mod_capquiz\report\capquiz_attempts_report_options;
use mod_capquiz\report\capquiz_attempts_report_table;
use moodle_url;
use quiz_responses_options;

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
     * Constructor
     * @param object $capquiz
     * @param context $context
     * @param quiz_responses_options $options
     * @param sql_join $groupstudentsjoins
     * @param sql_join $studentsjoins
     * @param array $questions
     * @param moodle_url $reporturl
     */
    public function __construct($capquiz, $context, capquiz_attempts_report_options $options,
                                sql_join $studentsjoins, $questions, $reporturl) {
        parent::__construct('mod-capquiz-report-questions-report', $capquiz, $context,
            $options, $studentsjoins, $questions, $reporturl);
    }

    public function build_table() {
        if (!$this->rawdata) {
            return;
        }

        $this->strtimeformat = str_replace(',', ' ', get_string('strftimedatetimeseconds', 'capquiz'));
        parent::build_table();
    }

    public function other_cols($colname, $attempt) {
        switch ($colname) {
            case 'question':
                return $this->data_col($attempt->slot, 'questionsummary', $attempt);
            default:
                return null;
        }
    }

    public function data_col($slot, $field, $attempt) {
        if ($attempt->usageid == 0) {
            return '-';
        }
        $value = $this->field_from_extra_data($attempt, $slot, $field);

        if (is_null($value)) {
            $summary = '-';
        } else {
            $summary = trim($value);
        }

        if ($this->is_downloading() && $this->is_downloading() != 'html') {
            return $summary;
        }
        $summary = s($summary);

        if ($this->is_downloading()) {
            return $summary;
        }

        if ($field === 'questionsummary') {
            return $this->make_preview_link($summary, $attempt, $slot);

        } else {
            return $summary;
        }
    }

    /**
     * Column text from the extra data loaded in load_extra_data(), before html formatting etc.
     *
     * @param object $attempt
     * @param int $slot
     * @param string $field
     * @return string
     */
    protected function field_from_extra_data($attempt, $slot, $field) {
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
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_answerstate($attempt) {
        if (is_null($attempt->attempt)) {
            return '-';
        }
        if ($attempt->usageid == 0) {
            return '-';
        }

        $state = $this->slot_state($attempt, $attempt->slot);
        if ($this->is_downloading()) {
            return $state;
        } else {
            return $this->make_review_link($state, $attempt, $attempt->slot);
        }
    }


    /**
     * Generate the display of the question rating column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_questionrating($attempt) {
        if ($attempt->questionrating) {
            return $attempt->questionrating;
        } else {
            return '-';
        }
    }

    /**
     * Generate the display of the time created (actually time answered) rating column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_timecreated($attempt) {
        if ($attempt->attempt) {
            return userdate($attempt->timeanswered, $this->strtimeformat);
        } else {
            return '-';
        }
    }

    /**
     * Generate the display of the time created (actually time answered) rating column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_attemptid($attempt) {
        if ($attempt->attempt) {
            return $attempt->attempt;
        } else {
            return '-';
        }
    }

    /**
     * Generate the display of the previous question rating column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_questionprevrating($attempt) {
        global $OUTPUT;
        if ($attempt->questionprevrating) {
            $warningicon = $OUTPUT->pix_icon('i/warning', get_string('rating_manually_updated', 'capquizreport_questions'),
                'moodle', array('class' => 'icon'));

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
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_prevquestionrating($attempt) {
        global $OUTPUT;
        if ($attempt->questionprevrating) {
            $warningicon = $OUTPUT->pix_icon('i/warning', get_string('rating_manually_updated', 'capquizreport_questions'),
                'moodle', array('class' => 'icon'));

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
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_prevquestionprevrating($attempt) {
        global $OUTPUT;
        if ($attempt->questionprevrating) {
            $warningicon = $OUTPUT->pix_icon('i/warning', get_string('rating_manually_updated', 'capquizreport_questions'),
                'moodle', array('class' => 'icon'));

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
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_questionprevratingmanual($attempt) {
        if (is_null($attempt->manualprevqrating)) {
            return '-';
        }
        $ismanual = ($attempt->manualprevqrating) ? 'true' : 'false';
        $manualprevqrating = get_string($ismanual, 'capquiz');
        return $manualprevqrating;
    }

    public function base_sql(sql_join $allowedstudentsjoins) {
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

        $fields = 'DISTINCT ' . $DB->sql_concat('userid', "'#'", 'COALESCE(attempt, 0)', "'#'", 'identifier') . ' AS uniqueidquestion,';
        $fields .= "ratings.*";
        $from = "(\n{$sql1} \nUNION ALL\n {$sql2}) AS ratings";

        list($from, $params) = uniquify_sql_params($from, $params);
        return [$fields, $from, '1=1', $params];
    }

    protected function requires_latest_steps_loaded() {
        if ($this->options->showqtext) {
            return true;
        } else {
            return false;
        }
    }
}
