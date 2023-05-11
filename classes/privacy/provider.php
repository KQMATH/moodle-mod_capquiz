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

use coding_exception;
use context;
use context_module;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;
use dml_exception;
use moodle_exception;
use question_display_options;
use stdClass;

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
     * @param collection $items The initialised collection to add metadata to.
     * @return  collection  A listing of user data stored through this system.
     */
    public static function get_metadata(collection $items): collection {
        // The table 'capquiz' stores a record for each capquiz.
        // It does not contain user personal data, but data is returned from it for contextual requirements.

        // The table 'capquiz_attempt' stores a record of each capquiz attempt.
        // It contains a userid which links to the user making the attempt and contains information about that attempt.
        $items->add_database_table('capquiz_attempt', [
            'userid' => 'privacy:metadata:capquiz_attempt:userid',
            'time_answered' => 'privacy:metadata:capquiz_attempt:time_answered',
            'time_reviewed' => 'privacy:metadata:capquiz_attempt:time_reviewed'
        ], 'privacy:metadata:capquiz_attempt');

        // The 'capquiz_question' table is used to map the usage of a question used in a CAPQuiz activity.
        // It does not contain user data.

        // The 'capquiz_question_rating' contains each change of rating for a question.
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

        // The table 'capquiz_user_rating' stores a record of each user rating in each capquiz attempt.
        // This is to kep track of rating and achievement level, in addition to provide a historical log.
        // It contains a capquiz_user_id which links to the capquiz_user and contains information about that user.
        $items->add_database_table('capquiz_user_rating', [
            'capquiz_user_id' => 'privacy:metadata:capquiz_user_rating:capquiz_user_id',
            'rating' => 'privacy:metadata:capquiz_user_rating:rating',
            'manual' => 'privacy:metadata:capquiz_user_rating:manual',
            'timecreated' => 'privacy:metadata:capquiz_user_rating:timecreated'
        ], 'privacy:metadata:capquiz_user_rating');

        // CAPQuiz links to the 'core_question' subsystem for all question functionality.
        $items->add_subsystem_link('core_question', [], 'privacy:metadata:core_question');
        return $items;
    }

    /**
     * Get the list of contexts where the specified user has attempted a capquiz.
     *
     * @param int $userid The user to search.
     * @return  contextlist  $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = 'SELECT cx.id
                  FROM {context} cx
                  JOIN {course_modules} cm
                    ON cm.id = cx.instanceid
                   AND cx.contextlevel = :contextlevel
                  JOIN {modules} m
                    ON m.id = cm.module
                   AND m.name = :modname
                  JOIN {capquiz} cq
                    ON cq.id = cm.instance
                  JOIN {capquiz_question_list} cql
                    ON cql.capquiz_id = cq.id
                  JOIN {capquiz_user} cu
                    ON cu.capquiz_id = cq.id
                   AND cu.user_id = :userid';
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'capquiz',
            'userid' => $userid
        ]);
        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        if (empty($contextlist)) {
            return;
        }
        $user = $contextlist->get_user();
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $sql = "SELECT cx.id                 AS contextid,
                       cm.id                 AS cmid,
                       ca.id                 AS capattemptid,
                       ca.question_id        AS questionid,
                       ca.answered           AS answered,
                       ca.time_reviewed      AS timereviewed,
                       ca.time_answered      AS timeanswered,
                       cu.id                 AS capuserid,
                       cu.question_usage_id  AS qubaid
                  FROM {context} cx
                  JOIN {course_modules} cm
                    ON cm.id = cx.instanceid
                   AND cx.contextlevel = :contextlevel
                  JOIN {modules} m
                    ON m.id = cm.module
                   AND m.name = :modname
                  JOIN {capquiz} cq
                    ON cq.id = cm.instance
                  JOIN {capquiz_question_list} cql
                    ON cql.capquiz_id = cq.id
                  JOIN {capquiz_user} cu
                    ON cu.capquiz_id = cq.id
                   AND cu.user_id = :userid
                  JOIN {capquiz_attempt} ca
                    ON ca.user_id = cu.id
                 WHERE cx.id {$contextsql}";
        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'capquiz',
            'userid' => $user->id
        ];
        $params += $contextparams;
        $qubaidforcontext = [];
        $attempts = $DB->get_recordset_sql($sql, $params);

        $context = null;
        foreach ($attempts as $attempt) {
            if (!$context || $context->instanceid != $attempt->cmid) {
                // This row belongs to the different data module than the previous row.
                // Start new data module.
                $context = context_module::instance($attempt->cmid);
            }
            $qubaidforcontext[$context->id] = $attempt->qubaid;
            // Store the quiz attempt data.
            $data = new stdClass();
            $data->timereviewed = transform::datetime($attempt->timereviewed);
            $data->timeanswered = transform::datetime($attempt->timeanswered);

            // The capquiz attempt data is organised in: {Course name}/{CAPQuiz activity name}/{Attempts}/{_X}/data.json
            // where X is the attempt number.
            $subcontext = [
                get_string('attempts', 'capquiz'),
                get_string('attempt', 'capquiz') . " $attempt->capattemptid"
            ];

            writer::with_context($context)->export_data($subcontext, $data);

            static::export_user_rating($context, $attempt->capuserid);
        }
        $attempts->close();

        // The capquiz question data is organised in: {Course name}/{CAPQuiz activity name}/{Questions}/{_X}/data.json
        // where X is the question attempt number.
        /* TODO we should rather organize the questions data and steps in:
                {Course name}/{CAPQuiz activity name}/{Attempts}/{_X}/Question/
                where X is the attempt number.*/
        foreach ($contextlist as $context) {
            $options = new question_display_options();
            $options->context = $context;
            $data = helper::get_context_data($context, $user);
            helper::export_context_files($context, $user);
            writer::with_context($context)->export_data([], $data);
            // This attempt was made by the user. They 'own' all data on it. Store the question usage data.
            \core_question\privacy\provider::export_question_usage(
                $user->id, $context, [], $qubaidforcontext[$context->id], $options, true
            );
        }
    }

    /**
     * Export the rating data from a specified user
     *
     * @param context $context The context to export the users rating for
     * @param int $userid the specified users id
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function export_user_rating(context $context, int $userid) {
        global $DB;
        $sql = "SELECT cur.id                AS ratingid,
                       cur.rating            AS rating,
                       cur.manual            AS manual,
                       cur.timecreated       AS timecreated
                  FROM {capquiz_user} cu
                  JOIN {capquiz_user_rating} cur
                    ON cur.capquiz_user_id = cu.id
                 WHERE cu.id = :userid";
        $ratings = $DB->get_recordset_sql($sql, ['userid' => $userid]);

        foreach ($ratings as $rating) {
            $data = new stdClass();
            $data->rating = $rating->rating;
            $data->manual = $rating->manual;
            $data->timecreated = transform::datetime($rating->timecreated);
            $subcontext = [
                get_string('userratings', 'capquiz'),
                get_string('userrating', 'capquiz') . " $rating->ratingid"
            ];
            writer::with_context($context)->export_data($subcontext, $data);
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(context $context) {
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
            $DB->delete_records('capquiz_attempt', ['user_id' => $user->id]);
            $DB->delete_records('capquiz_user_rating', ['capquiz_user_id' => $user->id]);
        }
        $DB->delete_records('capquiz_user', ['capquiz_id' => $cm->instance]);
        \core_question\privacy\provider::delete_data_for_all_users_in_context($context);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
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
                $DB->delete_records('capquiz_user_rating', ['capquiz_user_id' => $user->id]);
                $DB->delete_records('capquiz_user', ['capquiz_id' => $cm->instance, 'user_id' => $userid]);
            }
        }
        \core_question\privacy\provider::delete_data_for_user($contextlist);
    }
}
