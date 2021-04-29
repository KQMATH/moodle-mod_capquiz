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
 * Helper functions for the capquiz reports.
 *
 * @package     mod_capquiz
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_capquiz\capquiz;
use mod_capquiz\capquiz_urls;
use mod_capquiz\report\capquiz_report_factory;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/capquiz/adminlib.php');
require_once($CFG->dirroot . '/mod/capquiz/lib.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * Generates and returns list of available CAPQuiz report sub-plugins
 *
 * @param context_module $context the context level to check caps against
 * @return array list of valid reports present
 */
function capquiz_report_list($context) {
    static $reportlist;
    if (!empty($reportlist)) {
        return $reportlist;
    }
    $pluginmanager = new capquiz_plugin_manager('capquizreport');
    $installedplugins = $pluginmanager->get_sorted_plugins_list();
    $enabledplugins = core_plugin_manager::instance()->get_enabled_plugins('capquizreport');
    foreach ($installedplugins as $idx => $reportname) {
        $report = capquiz_report_factory::make($reportname);

        if (isset($enabledplugins[$reportname]) && $report->canview($context)) {
            $reportlist[] = $reportname;
        }
        continue;
    }
    return $reportlist;
}



/**
 * Create a filename for use when downloading data from a capquiz report. It is
 * expected that this will be passed to flexible_table::is_downloading, which
 * cleans the filename of bad characters and adds the file extension.
 * @param string $report the type of report.
 * @param string $courseshortname the course shortname.
 * @param string $capquizname the capquiz name.
 * @return string the filename.
 */
function capquiz_report_download_filename($report, $courseshortname, $capquizname) {
    return $courseshortname . '-' . format_string($capquizname, true) . '-' . $report;
}

/**
 * Are there any questions in this capquiz?
 * @param int $capquizid the capquizid id.
 */
function capquiz_has_questions($capquizid) {
    global $DB;
    $sql = 'SELECT cq.id
              FROM {capquiz_question} cq
              JOIN {capquiz_question_list} cql ON cql.capquiz_id = :capquizid AND cql.is_template = 0
              WHERE cq.question_list_id = cql.id';
    return $DB->record_exists_sql($sql, ['capquizid' => $capquizid]);
}

/**
 * Get the questions in this capquiz, in order.
 *
 * @param capquiz $capquiz the capquiz.
 * @return array of slot => $question object with fields
 *      ->slot, ->id, ->qtype, ->length.
 * @throws dml_exception
 */
function capquiz_report_get_questions(capquiz $capquiz) {
    global $DB;
    $sql = 'SELECT DISTINCT ' . $DB->sql_concat('qa.id', "'#'", 'cu.id', 'ca.slot') . ' AS uniqueid,
                ca.slot,
                q.id,
                q.qtype,
                q.length
            FROM {question} q
            JOIN {capquiz_question} cq ON cq.question_id = q.id
            JOIN {capquiz_question_list} cql ON cql.id = cq.question_list_id AND cql.is_template = 0
            JOIN {capquiz_user} cu ON cu.capquiz_id = cql.capquiz_id
            JOIN {question_usages} qu ON qu.id = cu.question_usage_id
            JOIN {question_attempts} qa ON qa.questionusageid = qu.id
            JOIN {capquiz_attempt} ca ON ca.question_id = cq.id AND ca.slot = qa.slot AND ca.user_id = cu.id

            WHERE cu.capquiz_id = ?
            AND q.length > 0

            ORDER BY ca.slot';
    $qsbyslot = $DB->get_records_sql($sql, array($capquiz->id()));
    $number = 1;
    foreach ($qsbyslot as $question) {
        $question->number = $number;
        $number += $question->length;
        $question->type = $question->qtype;
    }

    return $qsbyslot;
}

/**
 * Return a textual summary of the number of attempts that have been made at a particular quiz,
 * returns '' if no attempts have been made yet, unless $returnzero is passed as true.
 *
 * @param capquiz $capquiz
 * @param bool $returnzero if false (default), when no attempts have been
 *      made '' is returned instead of 'Attempts: 0'.
 * @return string a string like "Attempts: 123".
 */
function capquiz_num_attempt_summary(capquiz $capquiz, $returnzero = false) {
    $numattempts = capquiz_report_num_attempt($capquiz);
    if ($numattempts || $returnzero) {
        return get_string('attemptsnum', 'quiz', $numattempts);
    }
    return '';
}

/**
 * Returns the number of CAPQuiz attempts.
 *
 * @param capquiz $capquiz
 * @return int number of answered CAPQuiz attempts
 * @throws dml_exception
 */
function capquiz_report_num_attempt(capquiz $capquiz): int {
    global $DB;
    $sql = 'SELECT  COUNT( ca.id)
              FROM {capquiz_attempt} ca
              JOIN {capquiz_user} cu ON cu.capquiz_id = :capquizid AND cu.id = ca.user_id
              JOIN {question_usages} qu ON qu.id = cu.question_usage_id
              JOIN {question_attempts} qa ON qa.questionusageid = qu.id AND qa.slot = ca.slot
              JOIN {capquiz_question} cq ON cq.id = ca.question_id';
    $attempts = $DB->count_records_sql($sql, ['capquizid' => $capquiz->id()]);
    return $attempts;
}


/**
 * Generate a message saying that this capquiz has no questions, with a button to
 * go to the edit page, if the user has the right capability.
 * @param object $quiz the quiz settings.
 * @param object $cm the course_module object.
 * @param object $context the quiz context.
 * @return string HTML to output.
 */
function capquiz_no_questions_message($quiz, $cm, $context) {
    global $OUTPUT;

    $output = '';
    $output .= $OUTPUT->notification(get_string('noquestions', 'quiz'));
    if (has_capability('mod/capquiz:manage', $context)) {
        $output .= $OUTPUT->single_button(capquiz_urls::view_question_list_url(), get_string('editquiz', 'quiz'), 'get');
    }

    return $output;
}

/**
 * Generate a message saying that this capquiz has no questions, with a button to
 * go to the dashboard page (question list settings), if the user has the right capability.
 * @param object $quiz the quiz settings.
 * @param object $cm the course_module object.
 * @param object $context the quiz context.
 * @return string HTML to output.
 */
function capquiz_not_published_message($quiz, $cm, $context) {
    global $OUTPUT;

    $output = '';
    $output .= $OUTPUT->notification(get_string('question_list_not_published', 'capquiz'));
    if (has_capability('mod/capquiz:manage', $context)) {
        $output .= $OUTPUT->single_button(capquiz_urls::view_url(), get_string('question_list_settings', 'capquiz'), 'get');
    }

    return $output;
}

/**
 * Make all named SQL parameters unique and
 * generate a new parameter array with the unique parameters.
 * @param string $sql The SQL with patameters to uniquify
 * @param array $params The patameters to uniquify
 * @return array
 */
function uniquify_sql_params($sql, $params): array {
    $pattern = "/:([a-zA-Z0-9_]+)/";
    $paramsres = [];
    $processed = [];
    $sqlres = preg_replace_callback($pattern,
        function ($matches) use (&$params, &$paramsres, &$processed) {
            $index = 1;
            $key = substr($matches[0], 1);
            if (!array_key_exists($key, $params)) {
                return $matches[0];
            }

            if (array_key_exists($key, $processed)) {
                $processed[$key] += 1;
                $index = $processed[$key];
            } else {
                $processed[$key] = 1;
            }

            $newkey = $key . $index;
            $paramsres[$newkey] = $params[$key];

            return $matches[0] . $index;
        }, $sql);

    foreach ($params as $param => $value) {
        if (!array_key_exists($param, $paramsres) && !array_key_exists($param, $processed)) {
            $paramsres[$param] = $value;
        }
    }

    return [$sqlres, $paramsres];
}
