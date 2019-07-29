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
 * This file defines the capquiz attempts table for showing question attempts.
 *
 * @package     capquizreport_attempts
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace capquizreport_attempts;

use core\dml\sql_join;
use mod_capquiz\report\capquiz_attempts_report_options;
use mod_capquiz\report\capquiz_attempts_report_table;
use moodle_url;
use quiz_responses_options;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/capquiz/report/attemptsreport_table.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');


/**
 * This is a table subclass for displaying the capquiz attempts report.
 *
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquizreport_attempts_table extends capquiz_attempts_report_table {

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
        parent::__construct('mod-capquiz-report-attempts-report', $capquiz, $context,
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
            case 'response':
                return $this->data_col($attempt->slot, 'responsesummary', $attempt);
            case 'right':
                return $this->data_col($attempt->slot, 'rightanswer', $attempt);
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

        if ($field === 'responsesummary') {
            return $this->make_review_link($summary, $attempt, $slot);

        } else if ($field === 'questionsummary') {
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
            return $state->__toString();
        } else {
            return $this->make_review_link($state, $attempt, $attempt->slot);
        }
    }

    /**
     * Generate the display of the user rating column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_userrating($attempt) {
        if ($attempt->userrating) {
            return $attempt->userrating;
        } else {
            return '-';
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
     * Generate the display of the users's previous rating column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_userprevrating($attempt) {
        if ($attempt->userrating) {
            return $attempt->prevuserrating;
        } else {
            return '-';
        }
    }

    /**
     * Generate the display of the question's previous rating column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_questionprevrating($attempt) {
        global $OUTPUT;
        if ($attempt->prevquestionrating) {
            $warningicon = $OUTPUT->pix_icon('i/warning', get_string('rating_manually_updated', 'capquizreport_attempts'),
                'moodle', array('class' => 'icon'));

            if (!$this->is_downloading() && $attempt->manualprevqrating) {
                return $warningicon . $attempt->prevquestionrating;
            } else {
                return $attempt->prevquestionrating;
            }
        } else {
            return '-';
        }
    }

    /**
     * Generate the display of the question's previous rating manual column.
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

    protected function requires_latest_steps_loaded() {
        if ($this->options->showansstate
            || $this->options->showqtext
            || $this->options->showresponses
            || $this->options->showright) {
            return true;
        } else {
            return false;
        }
    }

    protected function is_latest_step_column($column) {
        if (preg_match('/^(?:question|response|right)/', $column, $matches)) {
            return $matches[1];
        }
        return false;
    }

    protected function update_sql_after_count($fields, $from, $where, $params) {
        $fields .= ',
                    cq.question_id AS moodlequestionid,
                    cqr.rating AS questionrating,
                    pcqr.rating AS prevquestionrating,
                    pcqr.manual AS manualprevqrating,
                    cur.rating AS userrating,
                    pcur.rating AS prevuserrating,
                    pcur.rating AS manualprevurating';

        $from .= "\nJOIN {capquiz_question} cq ON cq.question_list_id = cql.id AND cq.id = ca.question_id";
        $from .= "\nLEFT JOIN {capquiz_question_rating} cqr ON cqr.id = ca.question_rating_id";
        $from .= "\nLEFT JOIN {capquiz_question_rating} pcqr ON pcqr.id = ca.question_prev_rating_id";
        $from .= "\nLEFT JOIN {capquiz_user_rating} cur ON cur.id = ca.user_rating_id";
        $from .= "\nLEFT JOIN {capquiz_user_rating} pcur ON pcur.id = ca.user_prev_rating_id";

        return [$fields, $from, $where, $params];
    }
}
