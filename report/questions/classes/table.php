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

namespace capquizreport_questions;

use core\dml\sql_join;
use mod_capquiz\capquiz;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/local/reports/table.php');

/**
 * This is a table subclass for displaying the capquiz question report.
 *
 * @package     capquizreport_questions
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
        parent::__construct('questions', $capquiz, $context, $options, $studentsjoins, $questions, $reporturl);
    }

    /**
     * Take the data returned from the db_query and go through all the rows
     * processing each col using either col_{columnname} method or other_cols
     * method or if other_cols returns NULL then put the data straight into the table.
     *
     * This overwrites the parent method because full SQL query may fail on Mysql
     * because of the limit in the number of tables in the join. Therefore we only
     * join 59 tables in the main query and add the rest here.
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
     * @param string $column
     * @param stdClass $row The attempt row
     */
    public function other_cols($column, $row): ?string {
        return match ($column) {
            'question' => $this->data_col((int)$row->slot, 'questionsummary', $row),
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
        $summary = empty($value) ? '-' : trim($value);
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
    protected function field_from_extra_data(stdClass $attempt, int $slot, string $field): ?string {
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
        $state = $this->slot_state($attempt, (int)$attempt->slot);
        return (string)$state;
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
            $warningalt = get_string('rating_manually_updated', 'capquiz');
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
            $warningalt = get_string('rating_manually_updated', 'capquiz');
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
            $warningalt = get_string('rating_manually_updated', 'capquiz');
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
        [$fields, $from, $where, $params] = parent::base_sql($allowedstudentsjoins);

        $sql1 = "SELECT $fields,
                        1                AS identifier,
                        ca.timeanswered  AS timecreated,
                        cqr.rating       AS questionrating,
                        pcqr.rating      AS questionprevrating,
                        pcqr.manual      AS manualprevqrating,
                        cs.id            AS slotid,
                        qv.questionid    AS questionid
                   FROM $from
                   JOIN {capquiz_question_rating} cqr
                     ON cqr.id = ca.questionratingid
                   JOIN {capquiz_question_rating} pcqr
                     ON pcqr.id = ca.questionprevratingid
                   JOIN {capquiz_slot} cs
                     ON cs.id = cqr.slotid
                   JOIN {question_references} qr
                     ON qr.itemid = cs.id
                    AND qr.component = 'mod_capquiz'
                    AND qr.questionarea = 'slot'
                   JOIN {question_versions} qv
                     ON qv.questionbankentryid = qr.questionbankentryid
                    AND qv.version = COALESCE(
                            qr.version,
                            (SELECT MAX(qv2.version)
                              FROM {question_versions} qv2
                             WHERE qv2.questionbankentryid = qr.questionbankentryid)
                        )
                  WHERE $where";

        $sql2 = "SELECT $fields,
                        2                AS identifier,
                        ca.timeanswered  AS timecreated,
                        cqr.rating       AS questionrating,
                        pcqr.rating      AS questionprevrating,
                        pcqr.manual      AS manualprevqrating,
                        cs2.id           AS slotid,
                        qv.questionid    AS questionid
                   FROM $from
                   JOIN {capquiz_question_rating} cqr
                     ON cqr.id = ca.prevquestionratingid
                   JOIN {capquiz_question_rating} pcqr
                     ON pcqr.id = ca.prevquestionprevratingid
                   JOIN {capquiz_slot} cs
                     ON cs.id = cqr.slotid
                   JOIN {question_references} qr
                     ON qr.itemid = cs.id
                    AND qr.component = 'mod_capquiz'
                    AND qr.questionarea = 'slot'
                   JOIN {question_versions} qv
                     ON qv.questionbankentryid = qr.questionbankentryid
                    AND qv.version = COALESCE(
                            qr.version,
                            (SELECT MAX(qv2.version)
                              FROM {question_versions} qv2
                             WHERE qv2.questionbankentryid = qr.questionbankentryid)
                        )
                  WHERE $where";

        $fields = 'DISTINCT ' . $DB->sql_concat('userid', "'#'", 'COALESCE(attempt, 0)', "'#'", 'identifier')
            . ' AS uniqueidquestion, ratings.*';
        $from = "($sql1 UNION ALL $sql2) AS ratings";

        [$from, $params] = uniquify_sql_params($from, $params);
        return [$fields, $from, '1=1', $params];
    }

    /**
     * Does this report require the detailed information for each question from the question_attempts_steps table?
     */
    protected function requires_latest_steps_loaded(): bool {
        return (bool)$this->options->showqtext;
    }
}
