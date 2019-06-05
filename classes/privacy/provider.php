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
 * Privacy Subsystem implementation for mod_capquiz.
 *
 * @package     mod_capquiz
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @author      Sebastian Søviknes Gundersen <sebastian@sgundersen.com>
 * @copyright   2019 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;
use mod_capquiz\capquiz;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy Subsystem implementation for mod_capquiz.
 *
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @author      Sebastian Søviknes Gundersen <sebastian@sgundersen.com>
 * @copyright   2019 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    // This plugin has data.
    \core_privacy\local\metadata\provider,

    // This plugin currently implements the original plugin_provider interface.
    \core_privacy\local\request\plugin\provider {

    /**
     * Returns meta data about this system.
     * @param   collection $items The initialised collection to add metadata to.
     * @return  collection  A listing of user data stored through this system.
     */
    public static function get_metadata(collection $items) : collection {
        // The table 'capquiz' stores a record for each capquiz.
        // It does not contain user personal data, but data is returned from it for contextual requirements.

        // The table 'capquiz_attempt' stores a record of each capquiz attempt.
        // It contains a userid which links to the user making the attempt and contains information about that attempt.
        $items->add_database_table('capquiz_attempt', [
            'userid' => 'privacy:metadata:capquiz_attempt:userid',
            'time_answered' => 'privacy:metadata:capquiz_attempt:time_answered',
            'time_reviewed' => 'privacy:metadata:capquiz_attempt:time_reviewed',
        ], 'privacy:metadata:capquiz_attempt');

        // The 'capquiz_question' table is used to map the usage of a question used in a CAPQuiz activity.
        // It does not contain user data.

        // The 'capquiz_question_list' table is used to store the set of question lists used by a CapQuiz activity.
        // It does not contain user data.

        // The 'capquiz_question_selection' contains selections / settings for each CAPQuiz activity.
        // It does not contain user data.

        // The 'capquiz_rating_system' does not contain any user identifying data and does not need a mapping.

        // The table 'capquiz_user' stores a record of each user in each capquiz attempt.
        // This is to kep track of rating and achievement level.
        // It contains a userid which links to the user and contains information about that user.
        $items->add_database_table('capquiz_user', [
            'userid' => 'privacy:metadata:capquiz_user:userid',
            'rating' => 'privacy:metadata:capquiz_user:rating',
            'highest_level' => 'privacy:metadata:capquiz_user:highest_level',
        ], 'privacy:metadata:capquiz_user');

        // CAPQuiz links to the 'core_question' subsystem for all question functionality.
        $items->add_subsystem_link('core_question', [], 'privacy:metadata:core_question');
        return $items;
    }

    /**
     * Get the list of contexts where the specified user has attempted a capquiz.
     *
     * @param   int $userid The user to search.
     * @return  contextlist  $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        // Select the context of any quiz attempt where a user has an attempt, plus the related usages.
        $sql = 'SELECT cx.id
                  FROM {context} cx
                  JOIN {course_modules} cm ON cm.id = cx.instanceid AND cx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {capquiz} cq ON cq.id = cm.instance
                  JOIN {capquiz_question_list} cql ON cql.capquiz_id = cq.id
                  JOIN {capquiz_user} cu ON cu.capquiz_id = cq.id
                 WHERE cu.user_id = :userid';

        $qubaid = \core_question\privacy\provider::get_related_question_usages_for_user(
            'rel',
            'mod_capquiz',
            'cql.question_usage_id',
            $userid
        );
        $params = array_merge([
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'capquiz',
            'userid' => $userid,
        ], $qubaid->from_where_params());

        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist $contextlist The approved contexts to export information for.
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        if (empty($contextlist)) {
            return;
        }
        $user = $contextlist->get_user();
        $userid = $user->id;
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $sql = "SELECT cq.*,
                       ca.id            AS hasgrade,
                       ca.time_answered AS attempt_timeanswered,
                       ca.time_reviewed AS attempt_timereviewed,
                       cu.id            AS hasoverride,
                       cu.rating        AS user_rating,
                       cu.highest_level AS highest_level,
                       cx.id            AS contextid,
                       cm.id            AS cmid
                  FROM {context} cx
            INNER JOIN {course_modules} cm ON cm.id = cx.instanceid AND cx.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {capquiz} cq ON cq.id = cm.instance
             LEFT JOIN {capquiz_attempt} ca ON ca.user_id = :userid
             LEFT JOIN {capquiz_user} cu ON cu.capquiz_id = cq.id AND cu.user_id = ca.user_id
                 WHERE cx.id {$contextsql}";

        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'capquiz',
            'userid' => $userid,
        ];
        $params += $contextparams;

        // Fetch the individual quizzes.
        $quizzes = $DB->get_recordset_sql($sql, $params);
        foreach ($quizzes as $quiz) {
            list($course, $cm) = get_course_and_cm_from_cmid($quiz->cmid, 'capquiz');
            $capquiz = new capquiz($cm->id);
            $context = $capquiz->context();
            $data = helper::get_context_data($context, $contextlist->get_user());
            helper::export_context_files($context, $contextlist->get_user());
            writer::with_context($context)->export_data([], $data);
        }
        $quizzes->close();
        static::export_quiz_attempts($contextlist);
    }

    private static function get_quiz_attempt_subcontext($userid, \stdClass $attempt, \stdClass $user) {
        $subcontext = [get_string('questions', 'mod_capquiz')];
        if ($userid != $user->id) {
            $subcontext[] = fullname($user);
        }
        return $subcontext;
    }

    protected static function export_quiz_attempts(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        $qubaid = \core_question\privacy\provider::get_related_question_usages_for_user(
            'rel',
            'mod_capquiz',
            'cql.question_usage_id',
            $userid
        );
        $sql = 'SELECT DISTINCT 
                       cx.id AS contextid,
                       cm.id AS cmid,
                       ca.id AS capattemptid,
                       ca.question_id AS questionid,
                       ca.answered AS answered,
                       ca.time_reviewed AS timereviewed,
                       ca.time_answered AS timeanswered,
                       cql.question_usage_id AS qubaid
                  FROM {context} cx
                  JOIN {course_modules} cm ON cm.id = cx.instanceid AND cx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {capquiz_question_list} cql ON cql.capquiz_id = cm.instance
                  JOIN {capquiz_user} cu ON cu.user_id = :userid AND cu.capquiz_id = cm.instance
                  JOIN {capquiz_attempt} ca ON ca.user_id = cu.id ' . $qubaid->from . ' WHERE (' . $qubaid->where() . ') AND ca.answered = 1';
        $params = array_merge([
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
            'modname' => 'capquiz'
        ],  $qubaid->from_where_params());

        $attempts = $DB->get_recordset_sql($sql, $params);
        foreach ($attempts as $attempt) {
            $context = \context_module::instance($attempt->cmid);
            $options = new \question_display_options();
            $options->context = $context;
            $attemptsubcontext = static::get_quiz_attempt_subcontext($userid, $attempt, $contextlist->get_user());
            // This attempt was made by the user. They 'own' all data on it. Store the question usage data.
            \core_question\privacy\provider::export_question_usage(
                $userid,
                $context,
                $attemptsubcontext,
                $attempt->qubaid,
                $options,
                true
            );
            // Store the quiz attempt data.
            $data = new \stdClass();
            if (!empty($attempt->timereviewed)) {
                $data->timereviewed = transform::datetime($attempt->timereviewed);
            }
            if (!empty($attempt->timeanswered)) {
                $data->timeanswered = transform::datetime($attempt->timeanswered);
            }
            writer::with_context($context)->export_data($attemptsubcontext, $data);
        }
        $attempts->close();
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param   \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }
        $cm = get_coursemodule_from_id('capquiz', $context->instanceid);
        if (!$cm) {
            return;
        }
        $users = $DB->get_records('capquiz_user', ['capquiz_id' => $cm->instance]);
        foreach ($users as $user) {
            $DB->delete_records('capquiz_attempt', ['user_id' => $user->user_id]);
        }
        $DB->delete_records('capquiz_user', ['capquiz_id' => $cm->instance]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        if (empty($contextlist->count())) {
            return;
        }
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $cm = get_coursemodule_from_id('capquiz', $context->instanceid);
            $user = $DB->get_record('capquiz_user', ['capquiz_id' => $cm->instance, 'user_id' => $userid]);
            if ($user) {
                $DB->delete_records('capquiz_attempt', ['user_id' => $user->id]);
                $DB->delete_records('capquiz_user', ['capquiz_id' => $cm->instance, 'user_id' => $userid]);
            }
        }
    }

}
