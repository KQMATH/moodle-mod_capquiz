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

use core\context\module;
use mod_capquiz\capquiz;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/capquiz/adminlib.php');
require_once($CFG->dirroot . '/mod/capquiz/lib.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * Get the questions in this capquiz, in order.
 *
 * @param capquiz $capquiz
 * @return array of slot => $question object with fields
 *      ->slot, ->id, ->qtype, ->length.
 */
function capquiz_report_get_questions(capquiz $capquiz): array {
    global $DB;
    $sql = 'SELECT DISTINCT ' . $DB->sql_concat('qa.id', "'#'", 'cu.id', 'ca.slot') . " AS uniqueid,
                   ca.slot,
                   q.id,
                   q.qtype,
                   q.length
              FROM {capquiz_slot} cs
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
              JOIN {question} q
                ON q.id = qv.questionid
               AND q.length > 0
              JOIN {capquiz_user} cu
                ON cu.capquizid = cs.capquizid
              JOIN {question_usages} qu
                ON qu.id = cu.questionusageid
              JOIN {question_attempts} qa
                ON qa.questionusageid = qu.id
              JOIN {capquiz_attempt} ca
                ON ca.slotid = cs.id
               AND ca.slot = qa.slot
               AND ca.capquizuserid = cu.id
             WHERE cs.capquizid = :capquizid
             ORDER BY ca.slot";
    $questionsbyslot = $DB->get_records_sql($sql, ['capquizid' => $capquiz->get('id')]);
    $number = 1;
    foreach ($questionsbyslot as $question) {
        $question->number = $number;
        $number += $question->length;
        $question->type = $question->qtype;
    }
    return $questionsbyslot;
}

/**
 * Returns the number of question attempts in a CAPQuiz.
 *
 * @param int $capquizid
 */
function capquiz_report_num_attempt(int $capquizid): int {
    global $DB;
    $sql = 'SELECT COUNT(ca.id)
              FROM {capquiz_attempt} ca
              JOIN {capquiz_user} cu
                ON cu.capquizid = :capquizid
               AND cu.id = ca.capquizuserid
              JOIN {question_usages} qu
                ON qu.id = cu.questionusageid
              JOIN {question_attempts} qa
                ON qa.questionusageid = qu.id
               AND qa.slot = ca.slot
              JOIN {capquiz_slot} cs
                ON cs.id = ca.slotid';
    return $DB->count_records_sql($sql, ['capquizid' => $capquizid]);
}

/**
 * Generate a message saying that this capquiz has no questions, with a button to
 * go to the edit page, if the user has the right capability.
 *
 * @param cm_info $cm the course_module object.
 * @param module $context the quiz context.
 * @return string HTML to output.
 */
function capquiz_no_questions_message(cm_info $cm, module $context): string {
    global $OUTPUT;
    $output = $OUTPUT->notification(get_string('noquestions', 'quiz'));
    if (has_capability('mod/capquiz:manage', $context)) {
        $url = new \core\url('/mod/capquiz/edit.php', ['id' => $cm->id]);
        $output .= $OUTPUT->single_button($url, get_string('editquiz', 'quiz'), 'get');
    }
    return $output;
}

/**
 * Make all named SQL parameters unique and generate a new parameter array with the unique parameters.
 *
 * @param string $sql The SQL with patameters to uniquify
 * @param array $params The patameters to uniquify
 */
function uniquify_sql_params(string $sql, array $params): array {
    $pattern = "/:([a-zA-Z0-9_]+)/";
    $paramsres = [];
    $processed = [];
    $sqlres = preg_replace_callback($pattern, function ($matches) use (&$params, &$paramsres, &$processed) {
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
