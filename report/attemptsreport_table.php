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
 * Base class for the table used by a {@link quiz_attempts_report}.
 *
 * @package     mod_capquiz
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz\report;

use coding_exception;
use core\dml\sql_join;
use html_writer;
use mod_capquiz\capquiz_question_attempt;
use mod_quiz_attempts_report_options;
use moodle_url;
use qubaid_condition;
use qubaid_list;
use question_engine_data_mapper;
use question_state;
use quiz_attempt;
use stdClass;
use table_sql;
use user_picture;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');


/**
 * Base class for the table used by a {@link capquiz_attempts_report}.
 *
 * @package     mod_capquiz
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class capquiz_attempts_report_table extends table_sql {
    public $useridfield = 'userid';

    /** @var moodle_url the URL of this report. */
    protected $reporturl;

    /** @var array the display options. */
    protected $displayoptions;

    /**
     * @var array information about the latest step of each question.
     * Loaded by {@link load_question_latest_steps()}, if applicable.
     */
    protected $lateststeps = null;

    /** @var object the capquiz settings for the capquiz we are reporting on. */
    protected $capquiz;

    /** @var context the capquiz context. */
    protected $context;

    /** @var object mod_quiz_attempts_report_options the options affecting this report. */
    protected $options;

    /** @var sql_join Contains joins, wheres, params to find the students in the course. */
    protected $studentsjoins;

    /** @var array the questions that comprise this capquiz.. */
    protected $questions;

    /** @var bool whether to include the column with checkboxes to select each attempt. */
    protected $includecheckboxes;

    /**
     * Constructor
     * @param string $uniqueid
     * @param object $quiz
     * @param context $context
     * @param mod_quiz_attempts_report_options $options
     * @param sql_join $groupstudentsjoins Contains joins, wheres, params
     * @param sql_join $studentsjoins Contains joins, wheres, params
     * @param array $questions
     * @param moodle_url $reporturl
     */
    public function __construct($uniqueid, $quiz, $context,
                                capquiz_attempts_report_options $options, sql_join $studentsjoins,
                                $questions, $reporturl) {
        parent::__construct($uniqueid);
        $this->capquiz = $quiz;
        $this->context = $context;
        $this->studentsjoins = $studentsjoins;
        $this->questions = $questions;
        $this->includecheckboxes = $options->checkboxcolumn;
        $this->reporturl = $reporturl;
        $this->options = $options;
    }

    /**
     * Generate the display of the checkbox column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_checkbox($attempt) {
        if ($attempt->attempt) {
            return '<input type="checkbox" name="attemptid[]" value="' . $attempt->attempt . '" />';
        } else {
            return '';
        }
    }

    /**
     * Generate the display of the user's picture column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_picture($attempt) {
        global $OUTPUT;
        $user = new stdClass();
        $additionalfields = explode(',', user_picture::fields());
        $user = username_load_fields_from_object($user, $attempt, null, $additionalfields);
        $user->id = $attempt->userid;
        return $OUTPUT->user_picture($user);
    }

    /**
     * Generate the display of the user's full name column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_fullname($attempt) {
        $html = parent::col_fullname($attempt);
        if ($this->is_downloading() || empty($attempt->attempt)) {
            return $html;
        }
        return $html; /*. html_writer::empty_tag('br') . html_writer::link(
                new moodle_url('/mod/capquiz/review.php', array('attempt' => $attempt->attempt)),
                get_string('reviewattempt', 'quiz'), array('class' => 'reviewlink'))*/
    }

    /**
     * Generate the display of the time answered column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_timeanswered($attempt) {
        if ($attempt->attempt) {
            return userdate($attempt->timeanswered, $this->strtimeformat);
        } else {
            return '-';
        }
    }

    /**
     * Generate the display of the time answered column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_timereviewed($attempt) {
        if ($attempt->attempt) {
            return userdate($attempt->timereviewed, $this->strtimeformat);
        } else {
            return '-';
        }
    }


    /**
     * Generate the display of the question id column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_questionid($attempt) {
        if ($attempt->questionid) {
            return $attempt->questionid;
        } else {
            return '-';
        }
    }

    /**
     * Generate the display of the moodle question rating column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_moodlequestionid($attempt) {
        if ($attempt->moodlequestionid) {
            return $attempt->moodlequestionid;
        } else {
            return '-';
        }
    }

    /**
     * Generate the display of the user id column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_userid($attempt) {
        if ($attempt->userid) {
            return $attempt->userid;
        } else {
            return '-';
        }
    }

    /**
     * Make a link to review an individual question in a popup window.
     *
     * @param string $data HTML fragment. The text to make into the link.
     * @param object $attempt data for the row of the table being output.
     * @param int $slot the number used to identify this question within this usage.
     */
    public function make_review_link($data, $attempt, $slot) {
        global $OUTPUT;

        $feedbackimg = '';
        $state = $this->slot_state($attempt, $slot);
        if ($state->is_finished() && $state != question_state::$needsgrading) {
            $feedbackimg = $this->icon_for_fraction($this->slot_fraction($attempt, $slot));
        }

        $output = html_writer::tag('span', $feedbackimg . html_writer::tag('span',
                $data, array('class' => $state->get_state_class(true))), array('class' => 'que'));

        $reviewparams = array('attempt' => $attempt->attempt, 'slot' => $slot);
        if (isset($attempt->try)) {
            $reviewparams['step'] = $this->step_no_for_try($attempt->usageid, $slot, $attempt->try);
        }

        // TODO enable this when capquiz implements a "review question attempt" page.
        /*$url = new moodle_url('/mod/capquiz/reviewquestion.php', $reviewparams);
        $output = $OUTPUT->action_link($url, $output,
                new popup_action('click', $url, 'reviewquestion',
                        array('height' => 450, 'width' => 650)),
                array('title' => get_string('reviewresponse', 'quiz')));*/

        return $output;
    }

    /**
     * Make a link to review an individual question in a popup window.
     *
     * @param string $data HTML fragment. The text to make into the link.
     * @param object $attempt data for the row of the table being output.
     * @param int $slot the number used to identify this question within this usage.
     */
    public function make_preview_link($data, $attempt, $slot) {
        global $OUTPUT;

        $questionid = $this->slot_questionid($attempt, $slot);

        $output = html_writer::tag('span', html_writer::tag('span', $data),
            array('class' => 'que'));

        $url = question_preview_url($questionid)->out(false);

        $output = $OUTPUT->action_link($url, $output,
            new \popup_action('click', $url, 'previewquestion',
                array('height' => 450, 'width' => 650)),
            array('title' => get_string('previewquestion', 'quiz')));

        return $output;
    }

    /**
     * @param object $attempt the row data
     * @param int $slot
     * @return question_state
     */
    protected function slot_state($attempt, $slot) {
        $stepdata = $this->lateststeps[$attempt->usageid][$slot];
        return question_state::get($stepdata->state);
    }

    /**
     * @param object $attempt the row data
     * @param int $slot
     * @return question_id
     */
    protected function slot_questionid($attempt, $slot) {
        $stepdata = $this->lateststeps[$attempt->usageid][$slot];
        return $stepdata->questionid;
    }

    /**
     * Return an appropriate icon (green tick, red cross, etc.) for a grade.
     * @param float $fraction grade on a scale 0..1.
     * @return string html fragment.
     */
    protected function icon_for_fraction($fraction) {
        global $OUTPUT;

        $feedbackclass = question_state::graded_state_for_fraction($fraction)->get_feedback_class();
        return $OUTPUT->pix_icon('i/grade_' . $feedbackclass, get_string($feedbackclass, 'question'),
            'moodle', array('class' => 'icon'));
    }

    /**
     * @param object $attempt the row data
     * @param int $slot
     * @return float
     */
    protected function slot_fraction($attempt, $slot) {
        $stepdata = $this->lateststeps[$attempt->usageid][$slot];
        return $stepdata->fraction;
    }

    /**
     * Set up the SQL queries (count rows, and get data).
     *
     * @param sql_join $allowedjoins (joins, wheres, params) defines allowed users for the report.
     */
    public function setup_sql_queries($allowedjoins) {
        list($fields, $from, $where, $params) = $this->base_sql($allowedjoins);

        // The WHERE clause is vital here, because some parts of tablelib.php will expect to
        // add bits like ' AND x = 1' on the end, and that needs to leave to valid SQL.
        $this->set_count_sql("SELECT COUNT(1) FROM (SELECT $fields FROM $from WHERE $where) temp WHERE 1 = 1", $params);

        list($fields, $from, $where, $params) = $this->update_sql_after_count($fields, $from, $where, $params);
        $this->set_sql($fields, $from, $where, $params);
    }

    /**
     * Contruct all the parts of the main database query.
     * @param sql_join $allowedstudentsjoins (joins, wheres, params) defines allowed users for the report.
     * @return array with 4 elements ($fields, $from, $where, $params) that can be used to
     *     build the actual database query.
     */
    public function base_sql(sql_join $allowedstudentsjoins) {
        global $DB;

        $fields = 'DISTINCT ' . $DB->sql_concat('u.id', "'#'", 'COALESCE(ca.id, 0)') . ' AS uniqueid,';

        $extrafields = get_extra_user_fields_sql($this->context, 'u', '',
            array('id', 'idnumber', 'firstname', 'lastname', 'picture',
                'imagealt', 'institution', 'department', 'email'));
        $allnames = get_all_user_name_fields(true, 'u');
        $fields .= '
                cu.question_usage_id AS usageid,
                ca.id AS attempt,
                u.id AS userid,
                u.idnumber, ' . $allnames . ',
                u.picture,
                u.imagealt,
                u.institution,
                u.department,
                u.email' . $extrafields . ',
                ca.slot,
                ca.time_answered AS timeanswered,
                ca.time_reviewed AS timereviewed';

        // This part is the same for all cases. Join the users and capquiz_attempts tables.
        $from = " {user} u";
        $from .= "\nJOIN {capquiz_user} cu ON u.id = cu.user_id";
        $from .= "\nLEFT JOIN {capquiz_question_list} cql
                        ON cql.capquiz_id = :capquizid
                        AND cql.is_template = 0";

        $from .= "\nJOIN {question_usages} qu ON qu.id = cu.question_usage_id";
        $from .= "\nJOIN {question_attempts} qa ON qa.questionusageid = qu.id";

        $from .= "\nJOIN {capquiz_attempt} ca ON ca.user_id = cu.id AND ca.slot = qa.slot";
        $from .= "\nJOIN {capquiz_question} cq ON cq.question_list_id = cql.id AND cq.id = ca.question_id";

        $params = array('capquizid' => $this->capquiz->id());

        switch ($this->options->attempts) {
            case capquiz_attempts_report::ALL_WITH:
                // Show all attempts, including students who are no longer in the course.
                $where = 'ca.id IS NOT NULL';
                break;
            case capquiz_attempts_report::ENROLLED_WITH:
                // Show only students with attempts.
                $from .= "\n" . $allowedstudentsjoins->joins;
                $where = "ca.id IS NOT NULL AND " . $allowedstudentsjoins->wheres;
                $params = array_merge($params, $allowedstudentsjoins->params);
                break;
            /*
            case capquiz_attempts_report::ENROLLED_WITHOUT:
                // Show only students without attempts.
                $from .= "\n" . $allowedstudentsjoins->joins;
                $where = "ca.id IS NULL AND " . $allowedstudentsjoins->wheres;
                $params = array_merge($params, $allowedstudentsjoins->params);
                break;
            case capquiz_attempts_report::ENROLLED_ALL:
                // Show all students with or without attempts.
                $from .= "\n" . $allowedstudentsjoins->joins;
                $where = $allowedstudentsjoins->wheres;
                $params = array_merge($params, $allowedstudentsjoins->params);
                break;
            */
        }

        if ($this->options->onlyanswered) {
            $where .= " AND ca.answered = 1";
        }

        return array($fields, $from, $where, $params);
    }

    /**
     * A chance for subclasses to modify the SQL after the count query has been generated,
     * and before the full query is constructed.
     * @param string $fields SELECT list.
     * @param string $from JOINs part of the SQL.
     * @param string $where WHERE clauses.
     * @param array $params Query params.
     * @return array with 4 elements ($fields, $from, $where, $params) as from base_sql.
     */
    protected function update_sql_after_count($fields, $from, $where, $params) {
        return [$fields, $from, $where, $params];
    }

    public function query_db($pagesize, $useinitialsbar = true) {
        parent::query_db($pagesize, $useinitialsbar);

        if ($this->requires_extra_data()) {
            $this->load_extra_data();
        }
    }

    /**
     * Does this report require loading any more data after the main query. After the main query then
     * you can use $this->get
     *
     * @return bool should {@link query_db()} call {@link load_extra_data}?
     */
    protected function requires_extra_data() {
        return $this->requires_latest_steps_loaded();
    }

    /**
     * Does this report require the detailed information for each question from the
     * question_attempts_steps table?
     * @return bool should {@link load_extra_data} call {@link load_question_latest_steps}?
     */
    protected function requires_latest_steps_loaded() {
        return false;
    }

    /**
     * Load any extra data after main query. At this point you can call {@link get_qubaids_condition} to get the condition that
     * limits the query to just the question usages shown in this report page or alternatively for all attempts if downloading a
     * full report.
     */
    protected function load_extra_data() {
        $this->lateststeps = $this->load_question_latest_steps();
    }

    /**
     * Load information about the latest state of selected questions in selected attempts.
     *
     * The results are returned as an two dimensional array $qubaid => $slot => $dataobject
     *
     * @param qubaid_condition|null $qubaids used to restrict which usages are included
     * in the query. See {@link qubaid_condition}.
     * @return array of records. See the SQL in this function to see the fields available.
     */
    protected function load_question_latest_steps(qubaid_condition $qubaids = null) {
        if ($qubaids === null) {
            $qubaids = $this->get_qubaids_condition();
        }

        $dm = new question_engine_data_mapper();
        $latesstepdata = $dm->load_questions_usages_latest_steps(
            $qubaids, array_map(function($o) {
                return $o->slot;
                }, $this->questions));

        $lateststeps = array();
        foreach ($latesstepdata as $step) {
            $lateststeps[$step->questionusageid][$step->slot] = $step;
        }

        return $lateststeps;
    }

    /**
     * Get an appropriate qubaid_condition for loading more data about the
     * attempts we are displaying.
     * @return qubaid_condition
     */
    protected function get_qubaids_condition() {
        if (is_null($this->rawdata)) {
            throw new coding_exception(
                'Cannot call get_qubaids_condition until the main data has been loaded.');
        }
        $qubaids = array();
        foreach ($this->rawdata as $attempt) {
            if ($attempt->usageid > 0) {
                $qubaids[] = $attempt->usageid;
            }
        }
        return new qubaid_list($qubaids);
    }

    public function get_sort_columns() {
        // Add attemptid as a final tie-break to the sort. This ensures that
        // Attempts by the same student appear in order when just sorting by name.
        $sortcolumns = parent::get_sort_columns();
        $sortcolumns['attempt'] = SORT_ASC;
        return $sortcolumns;
    }

    public function wrap_html_start() {
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

    public function wrap_html_finish() {
        global $PAGE;
        if ($this->is_downloading() || !$this->includecheckboxes) {
            return;
        }

        echo '<div id="commands">';
        echo '<a id="checkattempts" href="#">' .
            get_string('selectall', 'quiz') . '</a> / ';
        echo '<a id="uncheckattempts" href="#">' .
            get_string('selectnone', 'quiz') . '</a> ';
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

        // TODO enable when support for attempt deletion is added {@link delete_selected_attempts}.
        // $this->submit_buttons();
        echo '</div>';

        // Close the form.
        echo '</div>';
        echo '</form></div>';
    }

    /**
     * Is this a column that depends on joining to the latest state information?
     * If so, return the corresponding slot. If not, return false.
     * @param string $column a column name
     * @return int false if no, else a slot.
     */
    protected function is_latest_step_column($column) {
        return false;
    }

    /**
     * Output any submit buttons required by the $this->includecheckboxes form.
     */
    protected function submit_buttons() {
        global $PAGE;
        if (has_capability('mod/capquiz:deleteattempts', $this->context)) {
            echo '<input type="submit" class="btn btn-secondary m-r-1" id="deleteattemptsbutton" name="delete" value="' .
                get_string('deleteselected', 'quiz_overview') . '"/>';
            $PAGE->requires->event_handler('#deleteattemptsbutton', 'click', 'M.util.show_confirm_dialog',
                array('message' => get_string('deleteattemptcheck', 'quiz')));
        }
    }

}
