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

use core\dml\sql_join;
use core\output\html_writer;
use mod_capquiz\capquiz;
use question_state;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/local/reports/table.php');

/**
 * This is a table subclass for displaying the capquiz attempts report.
 *
 * @package     capquizreport_attempts
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class table extends \mod_capquiz\local\reports\table {
    /**
     * Constructor.
     *
     * @param capquiz $capquiz
     * @param \core\context $context
     * @param options $options
     * @param sql_join $studentsjoins
     * @param array $questions
     * @param \core\url $reporturl
     */
    public function __construct(
        capquiz $capquiz,
        \core\context $context,
        options $options,
        sql_join $studentsjoins,
        array $questions,
        \core\url $reporturl,
    ) {
        parent::__construct('attempts', $capquiz, $context, $options, $studentsjoins, $questions, $reporturl);
    }

    /**
     * Take the data returned from the db_query and go through all the rows processing each col using either col_{columnname}
     * method or other_cols method or if other_cols returns NULL then put the data straight into the table.
     *
     * After calling this function, don't forget to call close_recordset.
     */
    public function build_table(): void {
        if ($this->rawdata) {
            $this->strtimeformat = str_replace(',', ' ', get_string('strftimedatetimeseconds', 'capquiz'));
            parent::build_table();
        }
    }

    /**
     * Format the submission and feedback columns.
     *
     * @param string $column The column name
     * @param \stdClass $row The attempt row
     * @return ?string
     */
    public function other_cols($column, $row): ?string {
        return match ($column) {
            'question' => $this->data_col((int)$row->slot, 'questionsummary', $row),
            'response' => $this->data_col((int)$row->slot, 'responsesummary', $row),
            'right' => $this->data_col((int)$row->slot, 'rightanswer', $row),
            default => null,
        };
    }

    /**
     * Workaround for preventing errors with name column preferences.
     *
     * @return string
     */
    public function get_sql_where(): string {
        return '';
    }

    /**
     * Format a single column, used in other_cols
     *
     * @param int $slot
     * @param string $field
     * @param \stdClass $attempt
     */
    public function data_col(int $slot, string $field, \stdClass $attempt): string {
        if ($attempt->usageid == 0) {
            return '-';
        }
        $value = $this->field_from_extra_data($attempt, $slot, $field);
        $summary = empty($value) ? '-' : trim($value);
        if ($this->is_downloading() && $this->is_downloading() != 'html') {
            return $summary;
        }
        $summary = s($summary);
        if ($this->is_downloading()) {
            return $summary;
        }
        if ($field === 'responsesummary') {
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
     * @param \stdClass $attempt
     * @param int $slot
     * @param string $field
     */
    protected function field_from_extra_data(\stdClass $attempt, int $slot, string $field): ?string {
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
     * Generate the display of the user's picture column.
     *
     * @param \stdClass $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_picture(\stdClass $attempt): string {
        global $OUTPUT;
        $user = new \stdClass();
        $additionalfields = explode(',', implode(',', \core_user\fields::get_picture_fields()));
        $user = username_load_fields_from_object($user, $attempt, null, $additionalfields);
        $user->id = $attempt->userid;
        return $OUTPUT->user_picture($user);
    }

    /**
     * Generate the display of the answer state column.
     *
     * @param \stdClass $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_answerstate(\stdClass $attempt): string {
        if ($attempt->attempt === null || $attempt->usageid === 0) {
            return '-';
        }
        $showcorrectness = true;
        $state = $this->slot_state($attempt, (int)$attempt->slot);
        if ($this->is_downloading()) {
            return $state->get_state_class($showcorrectness);
        } else {
            $fractionicon = '';
            $text = $state->default_string($showcorrectness);
            if ($state->is_finished() && $state != question_state::$needsgrading) {
                $fractionicon = $this->icon_for_fraction($this->slot_fraction($attempt, (int)$attempt->slot));
            }
            return html_writer::tag('span', $fractionicon . $text);
        }
    }

    /**
     * Generate the display of the grade column.
     *
     * @param \stdClass $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_grade(\stdClass $attempt): string {
        if ($attempt->attempt === null || $attempt->usageid === 0) {
            return '-';
        }
        $state = $this->slot_state($attempt, (int)$attempt->slot);
        if (!$state->is_finished() || $state == question_state::$needsgrading) {
            return '-';
        }
        return (string)$this->slot_fraction($attempt, (int)$attempt->slot);
    }

    /**
     * Generate the display of the user rating column.
     *
     * @param \stdClass $attempt the table row being output.
     */
    public function col_userrating(\stdClass $attempt): string {
        return $attempt->userrating ?: '-';
    }

    /**
     * Generate the display of the question rating column.
     *
     * @param \stdClass $attempt the table row being output.
     */
    public function col_questionrating(\stdClass $attempt): string {
        return $attempt->questionrating ?: '-';
    }

    /**
     * Generate the display of the users's previous rating column.
     *
     * @param \stdClass $attempt the table row being output.
     */
    public function col_userprevrating(\stdClass $attempt): string {
        return $attempt->userrating ? $attempt->prevuserrating : '-';
    }

    /**
     * Generate the display of the question's previous rating column.

     * @param \stdClass $attempt the table row being output.
     */
    public function col_questionprevrating(\stdClass $attempt): string {
        global $OUTPUT;
        if ($attempt->prevquestionrating) {
            $warningalt = get_string('rating_manually_updated', 'capquiz');
            $warningicon = $OUTPUT->pix_icon('i/warning', $warningalt, 'moodle', ['class' => 'icon']);
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
     *
     * @param \stdClass $attempt the table row being output.
     */
    public function col_questionprevratingmanual(\stdClass $attempt): string {
        if ($attempt->manualprevqrating === null) {
            return '-';
        }
        return get_string($attempt->manualprevqrating ? 'true' : 'false', 'capquiz');
    }

    /**
     * Does this report require the detailed information for each question from the question_attempts_steps table?
     */
    protected function requires_latest_steps_loaded(): bool {
        return $this->options->showansstate || $this->options->showqtext || $this->options->showresponses
            || $this->options->showright || $this->options->showgrade;
    }

    /**
     * A chance for subclasses to modify the SQL after the count query is generated, and before the full query is constructed.
     *
     * @param string $fields SELECT list.
     * @param string $from JOINs part of the SQL.
     * @param string $where WHERE clauses.
     * @param array $params Query params.
     * @return array with 4 elements ($fields, $from, $where, $params) as from base_sql.
     */
    protected function update_sql_after_count(string $fields, string $from, string $where, array $params): array {
        $fields .= ',
                    qa.questionid AS questionid,
                    cqr.rating    AS questionrating,
                    pcqr.rating   AS prevquestionrating,
                    pcqr.manual   AS manualprevqrating,
                    cur.rating    AS userrating,
                    pcur.rating   AS prevuserrating,
                    pcur.rating   AS manualprevurating';

        $from .= " LEFT JOIN {capquiz_question_rating} cqr
                          ON cqr.id = ca.questionratingid
                   LEFT JOIN {capquiz_question_rating} pcqr
                          ON pcqr.id = ca.questionprevratingid
                   LEFT JOIN {capquiz_user_rating} cur
                          ON cur.id = ca.userratingid
                   LEFT JOIN {capquiz_user_rating} pcur
                          ON pcur.id = ca.userprevratingid";

        return [$fields, $from, $where, $params];
    }
}
