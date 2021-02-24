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

defined('MOODLE_INTERNAL') || die();

/**
 * File to keep track of upgrades to the capquiz plugin
 *
 * @package     mod_capquiz
 * @author      Sebastian S. Gundersen <sebastian@sgundersen.com>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
/**
 * Function to upgrade mod_capquiz
 *
 * @param int $oldversion the version to be upgraded from
 * @return bool result
 */
function xmldb_capquiz_upgrade($oldversion) {
    global $DB, $OUTPUT;
    $dbman = $DB->get_manager();
    if ($oldversion < 2019060705) {
        $table = new xmldb_table('capquiz_attempt');
        $field = new xmldb_field('feedback', XMLDB_TYPE_TEXT);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2019060705, 'capquiz');
    }
    if ($oldversion < 2019061700) {
        $table = new xmldb_table('capquiz_question_list');
        $field = new xmldb_field('context_id', XMLDB_TYPE_INTEGER, 10);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2019061700, 'capquiz');
    }
    if ($oldversion < 2019062550) {
        $table = new xmldb_table('capquiz');
        $field = new xmldb_field('stars_to_pass', XMLDB_TYPE_INTEGER, 10, null, true, null, 3);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('timedue', XMLDB_TYPE_INTEGER, 10, null, true, null, 0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $table = new xmldb_table('capquiz_user');
        $field = new xmldb_field('stars_graded', XMLDB_TYPE_INTEGER, 10, null, true, null, 0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2019062550, 'capquiz');
    }
    if ($oldversion < 2019062553) {
        $table = new xmldb_table('capquiz_question_list');
        $default = '1300,1450,1600,1800,2000';
        $field = new xmldb_field('star_ratings', XMLDB_TYPE_CHAR, 255, null, true, null, $default);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $qlists = $DB->get_records('capquiz_question_list');
        foreach ($qlists as $qlist) {
            $qlist->star_ratings = implode(',', [
                $qlist->level_1_rating,
                $qlist->level_2_rating,
                $qlist->level_3_rating,
                $qlist->level_4_rating,
                $qlist->level_5_rating
            ]);
            $DB->update_record('capquiz_question_list', $qlist);
        }
        for ($i = 1; $i <= 5; $i++) {
            $field = new xmldb_field("level_{$i}_rating", XMLDB_TYPE_INTEGER, 10);
            if ($dbman->field_exists($table, $field)) {
                $dbman->drop_field($table, $field);
            }
        }
        upgrade_mod_savepoint(true, 2019062553, 'capquiz');
    }
    if ($oldversion < 2019073000) {
        // Define table capquiz_user_rating to be created.
        $utable = new xmldb_table('capquiz_user_rating');

        // Adding fields to table capquiz_user_rating.
        $utable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $utable->add_field('capquiz_user_id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $utable->add_field('rating', XMLDB_TYPE_FLOAT, '11', null, XMLDB_NOTNULL, null, null);
        $utable->add_field('manual', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 0);
        $utable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table capquiz_user_rating.
        $utable->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $utable->add_key('capquiz_user_id', XMLDB_KEY_FOREIGN, array('capquiz_user_id'), 'capquiz_user', array('id'));

        // Adding indexes to table capquiz_user_rating.
        $utable->add_index('timecreated', XMLDB_INDEX_NOTUNIQUE, array('timecreated'));

        // Conditionally launch create table for enrol_lti_lti2_consumer.
        if (!$dbman->table_exists($utable)) {
            $dbman->create_table($utable);
        }

        // Define table capquiz_question_rating to be created.
        $qtable = new xmldb_table('capquiz_question_rating');

        // Adding fields to table capquiz_question_rating.
        $qtable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $qtable->add_field('capquiz_question_id', XMLDB_TYPE_INTEGER, '11', null, null, null, null);
        $qtable->add_field('rating', XMLDB_TYPE_FLOAT, '11', null, XMLDB_NOTNULL, null, 0);
        $qtable->add_field('manual', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 0);
        $qtable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table capquiz_question_rating.
        $qtable->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $qtable->add_key('capquiz_question_id', XMLDB_KEY_FOREIGN, array('capquiz_question_id'), 'capquiz_question', array('id'));

        // Adding indexes to table capquiz_question_rating.
        $qtable->add_index('timecreated', XMLDB_INDEX_NOTUNIQUE, array('timecreated'));

        // Conditionally launch create table for capquiz_question_rating.
        if (!$dbman->table_exists($qtable)) {
            $dbman->create_table($qtable);
        }

        $atable = new xmldb_table('capquiz_attempt');

        $aqrfield = new xmldb_field(
            'question_rating_id', XMLDB_TYPE_INTEGER, 11, null, null, null, null);
        $aqrkey = new xmldb_key(
            'question_rating_id', XMLDB_KEY_FOREIGN, array('question_rating_id'), 'capquiz_question_rating', array('id'));
        $aqprevrfield = new xmldb_field(
            'question_prev_rating_id',
            XMLDB_TYPE_INTEGER, 11, null, null, null, null);
        $aqprevrkey = new xmldb_key(
            'question_prev_rating_id',
            XMLDB_KEY_FOREIGN, array('question_prev_rating_id'), 'capquiz_question_rating', array('id'));

        $aprevqrfield = new xmldb_field(
            'prev_question_rating_id',
            XMLDB_TYPE_INTEGER, 11, null, null, null, null);
        $aprevqrkey = new xmldb_key(
            'prev_question_rating_id',
            XMLDB_KEY_FOREIGN, array('prev_question_rating_id'), 'capquiz_question_rating', array('id'));

        $aprevqprevrfield = new xmldb_field(
            'prev_question_prev_rating_id',
            XMLDB_TYPE_INTEGER, 11, null, null, null, null);
        $aprevqprevrkey = new xmldb_key(
            'prev_question_prev_rating_id',
            XMLDB_KEY_FOREIGN, array('prev_question_prev_rating_id'), 'capquiz_question_rating', array('id'));

        if (!$dbman->field_exists($atable, $aqrfield)) {
            $dbman->add_field($atable, $aqrfield);
            $dbman->add_key($atable, $aqrkey);
        }
        if (!$dbman->field_exists($atable, $aqprevrfield)) {
            $dbman->add_field($atable, $aqprevrfield);
            $dbman->add_key($atable, $aqprevrkey);
        }
        if (!$dbman->field_exists($atable, $aprevqrfield)) {
            $dbman->add_field($atable, $aprevqrfield);
            $dbman->add_key($atable, $aprevqrkey);
        }
        if (!$dbman->field_exists($atable, $aprevqprevrfield)) {
            $dbman->add_field($atable, $aprevqprevrfield);
            $dbman->add_key($atable, $aprevqprevrkey);
        }

        $aurfield = new xmldb_field(
            'user_rating_id', XMLDB_TYPE_INTEGER, 11, null, null, null, null);
        $aurkey = new xmldb_key(
            'user_rating_id', XMLDB_KEY_FOREIGN, array('user_rating_id'), 'capquiz_user_rating', array('id'));
        $aprevurfield = new xmldb_field(
            'user_prev_rating_id',
            XMLDB_TYPE_INTEGER, 11, null, null, null, null);
        $aprevurkey = new xmldb_key(
            'user_prev_rating_id',
            XMLDB_KEY_FOREIGN, array('user_prev_rating_id'), 'capquiz_user_rating', array('id'));

        if (!$dbman->field_exists($atable, $aurfield)) {
            $dbman->add_field($atable, $aurfield);
            $dbman->add_key($atable, $aurkey);
        }
        if (!$dbman->field_exists($atable, $aprevurfield)) {
            $dbman->add_field($atable, $aprevurfield);
            $dbman->add_key($atable, $aprevurkey);
        }

        upgrade_mod_savepoint(true, 2019073000, 'capquiz');
    }
    if ($oldversion < 2019103100 ) {

        // Define index timereviewed (not unique) to be added to capquiz_attempt.
        $table = new xmldb_table('capquiz_attempt');
        $index = new xmldb_index('timereviewed', XMLDB_INDEX_NOTUNIQUE, ['user_id', 'time_reviewed']);

        // Conditionally launch add index timereviewed.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Capquiz savepoint reached.
        upgrade_mod_savepoint(true, 2019103100, 'capquiz');
    }
    if ($oldversion < 2020091600 ) {
        $table = new xmldb_table('capquiz_attempt');
        $field = new xmldb_field("feedback");

        // Conditionally launch delete feedback.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        // Capquiz savepoint reached.
        upgrade_mod_savepoint(true, 2020091600, 'capquiz');
    }
    if ($oldversion < 2021020600) {

        // This might take a while on large databases.
        set_time_limit(0);

        // Define field id to be added to capquiz_user.
        $table = new xmldb_table('capquiz_user');
        $field = new xmldb_field('question_usage_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null, null);

        // Conditionally launch add field id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define key question_usage_id (foreign-unique) to be added to capquiz_user.
        $table = new xmldb_table('capquiz_user');
        $key = new xmldb_key('question_usage_id', XMLDB_KEY_FOREIGN_UNIQUE, ['question_usage_id'], 'question_usages', ['id']);

        // Launch add key question_usage_id.
        $dbman->add_key($table, $key);

        // Split question usages.
        $qlists = $DB->get_records('capquiz_question_list');
        $totalqlists = count($qlists);
        $qlistindex = 0;
        foreach ($qlists as &$qlist) {
            $qlistindex++;
            $oldqubaid = $qlist->question_usage_id;
            if (!$oldqubaid) {
                continue;
            }
            $oldquba = $DB->get_record('question_usages', ['id' => $oldqubaid]);
            if (!$oldquba) {
                echo $OUTPUT->notification("[$qlistindex/$totalqlists] Did not find question usage with id $oldqubaid
                 for question list {$qlist->title} ({$qlist->id})");
                continue;
            }
            $users = $DB->get_records('capquiz_user', ['capquiz_id' => $qlist->capquiz_id]);
            $totalusers = count($users);
            echo $OUTPUT->notification("[$qlistindex/$totalqlists] Migrating question list {$qlist->title} with "
                . $totalusers . ' users', 'notifysuccess');
            echo '<progress id="capquiz_progress_2021020600_' . $qlistindex . '" value="0" max="' . $totalusers . '"></progress>';
            echo '<label id="capquiz_progress_2021020600_' . $qlistindex . '_label">0%</label>';
            $userindex = 0;
            foreach ($users as &$user) {

                // Create new question usage for user.
                $newquba = new stdClass();
                $newquba->contextid = $oldquba->contextid;
                $newquba->component = $oldquba->component;
                $newquba->preferredbehaviour = $oldquba->preferredbehaviour;
                $newqubaid = $DB->insert_record('question_usages', $newquba);

                // Update question usage for user and their attempts.
                $user->question_usage_id = $newqubaid;
                $DB->update_record('capquiz_user', $user);

                // Update user's question attempts.
                $attempts = $DB->get_records_sql(
                    ' SELECT DISTINCT qa.id, qa.slot, qa.questionusageid ' .
                    '   FROM {question_attempts}      AS qa ' .
                    '   JOIN {question_attempt_steps} AS qas' .
                    '     ON qas.questionattemptid = qa.id '  .
                    '    AND qas.userid = ? ' .
                    '  WHERE qa.questionusageid = ?'
                , [$user->user_id, $oldqubaid]);

                $slot = 1;
                foreach ($attempts as &$attempt) {
                    $attempt->slot = $slot;
                    $attempt->questionusageid = $newqubaid;
                    $DB->update_record_raw('question_attempts', $attempt, true);
                    $slot++;
                }

                // Update user's CAPQuiz question attempts.
                $capquizattempts = $DB->get_records('capquiz_attempt', ['user_id' => $user->id], 'slot', 'id, slot');
                $slot = 1;
                foreach ($capquizattempts as &$capquizattempt) {
                    $capquizattempt->slot = $slot;
                    $DB->update_record_raw('capquiz_attempt', $capquizattempt, true);
                    $slot++;
                }

                // Feedback.
                $userindex++;
                echo '<script> document.getElementById("capquiz_progress_2021020600_'
                    . $qlistindex . '").value = ' . $userindex . '; </script>';
                echo '<script> document.getElementById("capquiz_progress_2021020600_'
                    . $qlistindex . '_label").innerHTML = "'
                . $userindex . '/' . $totalusers . ' users processed"; </script>';
            }

            // Delete original question usage.
            $DB->delete_records('question_usages', ['id' => $oldqubaid]);
        }

        // Define key question_usage_id (foreign-unique) to be dropped from capquiz_question_list.
        $table = new xmldb_table('capquiz_question_list');
        $key = new xmldb_key('question_usage_id', XMLDB_KEY_FOREIGN_UNIQUE, ['question_usage_id'], 'question_usages', ['id']);

        // Launch drop key question_usage_id.
        $dbman->drop_key($table, $key);

        // Define field question_usage_id to be dropped from capquiz_question_list.
        $table = new xmldb_table('capquiz_question_list');
        $field = new xmldb_field('question_usage_id');

        // Conditionally launch drop field question_usage_id.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Capquiz savepoint reached.
        upgrade_mod_savepoint(true, 2021020600, 'capquiz');
    }
    if ($oldversion < 2021021100) {

        $table = new xmldb_table('capquiz_attempt');

        $key = new xmldb_key('user_id', XMLDB_KEY_FOREIGN, ['user_id'], 'users', ['id']);
        $dbman->drop_key($table, $key);
        $key = new xmldb_key('user_id', XMLDB_KEY_FOREIGN, ['user_id'], 'capquiz_user', ['id']);
        $dbman->add_key($table, $key);

        // Capquiz savepoint reached.
        upgrade_mod_savepoint(true, 2021021100, 'capquiz');
    }
    return true;
}
