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

namespace mod_capquiz\local\reports;

use coding_exception;
use core\context;
use core\dml\sql_join;
use core\output\html_writer;
use mod_capquiz\capquiz;
use popup_action;
use qubaid_condition;
use qubaid_list;
use question_engine_data_mapper;
use question_state;
use stdClass;
use table_sql;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

/**
 * Base class for the table used by a {@see report}.
 *
 * @package     mod_capquiz
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class table extends table_sql {
    /** @var \core\url the URL of this report. */
    protected \core\url $reporturl;

    /** @var array the display options. */
    protected array $displayoptions;

    /**
     * @var ?array information about the latest step of each question.
     * Loaded by {@see load_question_latest_steps()}, if applicable.
     */
    protected ?array $lateststeps = null;

    /** @var capquiz the capquiz settings for the capquiz we are reporting on. */
    protected capquiz $capquiz;

    /** @var context the capquiz context. */
    protected context $context;

    /** @var options the options affecting this report. */
    protected options $options;

    /** @var sql_join Contains joins, wheres, params to find the students in the course. */
    protected sql_join $studentsjoins;

    /** @var array the questions that comprise this capquiz.. */
    protected array $questions;

    /** @var bool whether to include the column with checkboxes to select each attempt. */
    protected bool $includecheckboxes;

    /** @var string date format. */
    protected string $strtimeformat;

    /**
     * Constructor.
     *
     * @param string $uniqueid
     * @param capquiz $capquiz
     * @param context $context
     * @param options $options
     * @param sql_join $studentsjoins Contains joins, wheres, params
     * @param array $questions
     * @param \core\url $reporturl
     */
    public function __construct(
        $uniqueid,
        capquiz $capquiz,
        context $context,
        options $options,
        sql_join $studentsjoins,
        array $questions,
        \core\url $reporturl,
    ) {
        parent::__construct("capquiz_report_$uniqueid");
        $this->useridfield = 'userid';
        $this->capquiz = $capquiz;
        $this->context = $context;
        $this->studentsjoins = $studentsjoins;
        $this->questions = $questions;
        $this->includecheckboxes = $options->checkboxcolumn;
        $this->reporturl = $reporturl;
        $this->options = $options;
    }

    /**
     * Generate the display of the checkbox column.
     *
     * @param stdClass $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_checkbox(stdClass $attempt): string {
        if (property_exists($attempt, 'attempt')) {
            return '<input type="checkbox" name="attemptid[]" value="' . $attempt->attempt . '" />';
        } else {
            return '';
        }
    }

    /**
     * Generate the display of the time answered column.
     *
     * @param stdClass $attempt the table row being output.
     */
    public function col_timeanswered(stdClass $attempt): string {
        return property_exists($attempt, 'attempt') ? userdate($attempt->timeanswered, $this->strtimeformat) : '-';
    }

    /**
     * Generate the display of the time answered column.
     *
     * @param stdClass $attempt the table row being output.
     */
    public function col_timereviewed(stdClass $attempt): string {
        return property_exists($attempt, 'attempt') ? userdate($attempt->timereviewed, $this->strtimeformat) : '-';
    }

    /**
     * Generate the display of the slot id column.
     *
     * @param stdClass $attempt the table row being output.
     */
    public function col_slotid(stdClass $attempt): string {
        return $attempt->slotid ?: '-';
    }

    /**
     * Generate the display of the moodle question id column.
     *
     * @param stdClass $attempt the table row being output.
     */
    public function col_questionid(stdClass $attempt): string {
        return $attempt->questionid ?: '-';
    }

    /**
     * Generate the display of the user id column.
     *
     * @param stdClass $attempt the table row being output.
     */
    public function col_userid(stdClass $attempt): string {
        return $attempt->userid ?: '-';
    }

    /**
     * Make a link to review an individual question in a popup window.
     *
     * @param string $data HTML fragment. The text to make into the link.
     * @param stdClass $attempt data for the row of the table being output.
     * @param int $slot the number used to identify this question within this usage.
     */
    public function make_review_link(string $data, stdClass $attempt, int $slot): string {
        $feedbackimg = '';
        $state = $this->slot_state($attempt, $slot);
        if ($state->is_finished() && $state != question_state::$needsgrading) {
            $feedbackimg = $this->icon_for_fraction($this->slot_fraction($attempt, $slot));
        }
        $dataspan = html_writer::tag('span', $data, ['class' => $state->get_state_class(true)]);
        return html_writer::tag('span', $feedbackimg . $dataspan, ['class' => 'que']);
    }

    /**
     * Make a link to review an individual question in a popup window.
     *
     * @param string $data HTML fragment. The text to make into the link.
     * @param stdClass $attempt data for the row of the table being output.
     * @param int $slot the number used to identify this question within this usage.
     */
    public function make_preview_link(string $data, stdClass $attempt, int $slot) {
        global $OUTPUT;
        $questionid = $this->slot_questionid($attempt, $slot);
        $output = html_writer::tag('span', html_writer::tag('span', $data), ['class' => 'que']);
        $url = \qbank_previewquestion\helper::question_preview_url($questionid);
        $action = new popup_action('click', $url, 'previewquestion', ['height' => 450, 'width' => 650]);
        return $OUTPUT->action_link($url, $output, $action, ['title' => get_string('previewquestion', 'quiz')]);
    }

    /**
     * Find the state for $slot given after this try.
     *
     * @param stdClass $attempt
     * @param int $slot
     * @return question_state
     */
    protected function slot_state(stdClass $attempt, int $slot): question_state {
        $stepdata = $this->lateststeps[$attempt->usageid][$slot];
        return question_state::get($stepdata->state);
    }

    /**
     * Returns the id of the question
     *
     * @param stdClass $attempt
     * @param int $slot
     */
    protected function slot_questionid(stdClass $attempt, int $slot): int {
        $stepdata = $this->lateststeps[$attempt->usageid][$slot];
        return (int)$stepdata->questionid;
    }

    /**
     * Return an appropriate icon (green tick, red cross, etc.) for a grade.
     *
     * @param float $fraction grade on a scale 0..1.
     */
    protected function icon_for_fraction(float $fraction): string {
        global $OUTPUT;
        $feedbackclass = question_state::graded_state_for_fraction($fraction)->get_feedback_class();
        $feedbackalt = get_string($feedbackclass, 'question');
        return $OUTPUT->pix_icon('i/grade_' . $feedbackclass, $feedbackalt, 'moodle', ['class' => 'icon']);
    }

    /**
     * The grade for this slot after this try.
     *
     * @param stdClass $attempt
     * @param int $slot
     */
    protected function slot_fraction(stdClass $attempt, int $slot): float {
        $stepdata = $this->lateststeps[$attempt->usageid][$slot];
        return (float)$stepdata->fraction;
    }

    /**
     * Set up the SQL queries (count rows, and get data).
     *
     * @param sql_join $allowedjoins (joins, wheres, params) defines allowed users for the report.
     */
    public function setup_sql_queries(sql_join $allowedjoins): void {
        [$fields, $from, $where, $params] = $this->base_sql($allowedjoins);

        // The WHERE clause is vital here, because some parts of tablelib.php will expect to
        // add bits like ' AND x = 1' on the end, and that needs to leave to valid SQL.
        $this->set_count_sql("SELECT COUNT(1) FROM (SELECT $fields FROM $from WHERE $where) temp WHERE 1 = 1", $params);

        [$fields, $from, $where, $params] = $this->update_sql_after_count($fields, $from, $where, $params);
        $this->set_sql($fields, $from, $where, $params);
    }

    /**
     * Contruct all the parts of the main database query.
     * @param sql_join $allowedstudentsjoins (joins, wheres, params) defines allowed users for the report.
     * @return array with 4 elements ($fields, $from, $where, $params) that can be used to
     *     build the actual database query.
     */
    public function base_sql(sql_join $allowedstudentsjoins): array {
        global $DB;

        $extrafields = \core_user\fields::for_identity($this->context)
            ->including('id', 'idnumber', 'firstname', 'lastname', 'picture', 'imagealt', 'institution', 'department', 'email')
            ->get_sql('u')->selects;

        $allnames = \core_user\fields::for_name()
            ->with_identity($this->context)
            ->get_sql('u')->selects;

        $fields = 'DISTINCT ' . $DB->sql_concat('u.id', "'#'", 'COALESCE(ca.id, 0)') . ' AS uniqueid,';
        $fields .= '
                cu.questionusageid AS usageid,
                ca.id AS attempt,
                u.id AS userid,
                u.idnumber' . $allnames . ',
                u.picture,
                u.imagealt,
                u.institution,
                u.department,
                u.email' . $extrafields . ',
                ca.slot,
                ca.timeanswered,
                ca.timereviewed';

        // This part is the same for all cases. Join the user and capquiz_attempt tables.
        $from = ' {user} u
                 JOIN {capquiz_user} cu
                   ON cu.userid = u.id
                  AND cu.capquizid = :capquizid
                 JOIN {question_usages} qu
                   ON qu.id = cu.questionusageid
                 JOIN {question_attempts} qa
                   ON qa.questionusageid = qu.id
                 JOIN {capquiz_attempt} ca
                   ON ca.capquizuserid = cu.id
                  AND ca.slot = qa.slot
                 JOIN {capquiz_slot} cs2
                   ON cs2.id = ca.slotid';

        $params = ['capquizid' => $this->capquiz->get('id')];

        switch ($this->options->attempts) {
            case options::ALL_WITH:
                // Show all attempts, including students who are no longer in the course.
                $where = 'ca.id IS NOT NULL';
                break;
            case options::ENROLLED_WITH:
                // Show only students with attempts.
                $from .= "\n" . $allowedstudentsjoins->joins;
                $where = "ca.id IS NOT NULL AND " . $allowedstudentsjoins->wheres;
                $params = array_merge($params, $allowedstudentsjoins->params);
                break;
            case options::ENROLLED_WITHOUT:
                // Show only students without attempts.
                $from .= "\n" . $allowedstudentsjoins->joins;
                $where = "ca.id IS NULL AND " . $allowedstudentsjoins->wheres;
                $params = array_merge($params, $allowedstudentsjoins->params);
                break;
            case options::ENROLLED_ALL:
                // Show all students with or without attempts.
                $from .= "\n" . $allowedstudentsjoins->joins;
                $where = $allowedstudentsjoins->wheres;
                $params = array_merge($params, $allowedstudentsjoins->params);
                break;
            default:
                return [$fields, $from, '', $params];
        }

        if ($this->options->onlyanswered) {
            $where .= " AND ca.answered = 1";
        }
        return [$fields, $from, $where, $params];
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
        return [$fields, $from, $where, $params];
    }

    /**
     * Query the db. Store results in the table object for use by build_table.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar (only used if there is a fullname column)
     */
    public function query_db($pagesize, $useinitialsbar = true): void {
        parent::query_db($pagesize, $useinitialsbar);
        if ($this->requires_latest_steps_loaded()) {
            $this->load_extra_data();
        }
    }

    /**
     * Does this report require the detailed information for each question from the question_attempts_steps table?
     *
     * @return bool should {@see load_extra_data} call {@see load_question_latest_steps}?
     */
    protected function requires_latest_steps_loaded(): bool {
        return false;
    }

    /**
     * Load any extra data after main query. At this point you can call {@see get_qubaids_condition} to get the condition that
     * limits the query to just the question usages shown in this report page or alternatively for all attempts if downloading a
     * full report.
     */
    protected function load_extra_data(): void {
        $this->lateststeps = $this->load_question_latest_steps();
    }

    /**
     * Load information about the latest state of selected questions in selected attempts.
     * The results are returned as an two dimensional array $qubaid => $slot => $dataobject
     *
     * @param ?qubaid_condition $qubaids used to restrict which usages are included in the query. See {@see qubaid_condition}.
     * @return array of records. See the SQL in this function to see the fields available.
     */
    protected function load_question_latest_steps(?qubaid_condition $qubaids = null): array {
        if ($qubaids === null) {
            $qubaids = $this->get_qubaids_condition();
        }
        $lateststeps = [];
        $dm = new question_engine_data_mapper();
        foreach ($dm->load_questions_usages_latest_steps($qubaids, array_map(fn($o) => $o->slot, $this->questions)) as $step) {
            $lateststeps[$step->questionusageid][$step->slot] = $step;
        }
        return $lateststeps;
    }

    /**
     * Get an appropriate qubaid_condition for loading more data about the attempts we are displaying.
     *
     * @return qubaid_list
     */
    protected function get_qubaids_condition(): qubaid_list {
        if ($this->rawdata === null) {
            throw new coding_exception('Cannot call get_qubaids_condition until the main data has been loaded.');
        }
        $qubaids = [];
        foreach ($this->rawdata as $attempt) {
            if ($attempt->usageid > 0) {
                $qubaids[] = $attempt->usageid;
            }
        }
        return new qubaid_list($qubaids);
    }

    /**
     * Get the columns to sort by, in the form required by {@see construct_order_by()}.
     *
     * @return array column name => SORT_... constant.
     */
    public function get_sort_columns(): array {
        // Add attemptid as a final tie-break to the sort.
        // This ensures that attempts by the same student appear in order when just sorting by name.
        $sortcolumns = parent::get_sort_columns();
        $sortcolumns['attempt'] = SORT_ASC;
        return $sortcolumns;
    }

    /**
     * Wrap start of table
     */
    public function wrap_html_start(): void {
        if ($this->is_downloading() || !$this->includecheckboxes) {
            return;
        }
        $url = $this->options->get_url();
        $url->param('sesskey', sesskey());
        echo '<div id="tablecontainer">';
        echo '<form id="attemptsform" method="post" action="' . $url->out_omit_querystring() . '">';
        echo html_writer::input_hidden_params($url);
        echo '<div>';
    }

    /**
     * End of table wrap.
     */
    public function wrap_html_finish(): void {
        global $PAGE;
        if ($this->is_downloading() || !$this->includecheckboxes) {
            return;
        }
        echo '<div id="commands">';
        echo '<a id="checkattempts" href="#">' . get_string('selectall', 'quiz') . '</a> / ';
        echo '<a id="uncheckattempts" href="#">' . get_string('selectnone', 'quiz') . '</a> ';
        $PAGE->requires->js_amd_inline("
        require(['jquery'], function($) {
            $('#checkattempts').click(function(e) {
                $('#attemptsform').find('input:checkbox').prop('checked', true);
                e.preventDefault();
            });
            $('#uncheckattempts').click(function(e) {
                $('#attemptsform').find('input:checkbox').prop('checked', false);
                e.preventDefault();
            });
        });");
        echo '&nbsp;&nbsp;';
        echo '</div>';
        echo '</div>';
        echo '</form></div>';
    }
}
