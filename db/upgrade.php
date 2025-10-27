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
 * File to keep track of upgrades to the capquiz plugin
 *
 * @package     mod_capquiz
 * @author      Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright   2024 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable PSR2.Methods.FunctionCallSignature.Indent
// phpcs:disable PSR2.Methods.FunctionCallSignature.MultipleArguments
// phpcs:disable PSR2.Methods.FunctionCallSignature.CloseBracketLine

/**
 * Function to upgrade mod_capquiz
 *
 * @package mod_capquiz
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
                $qlist->level_5_rating,
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
        $utable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $utable->add_key('capquiz_user_id', XMLDB_KEY_FOREIGN, ['capquiz_user_id'], 'capquiz_user', ['id']);

        // Adding indexes to table capquiz_user_rating.
        $utable->add_index('timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);

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
        $qtable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $qtable->add_key('capquiz_question_id', XMLDB_KEY_FOREIGN, ['capquiz_question_id'], 'capquiz_question', ['id']);

        // Adding indexes to table capquiz_question_rating.
        $qtable->add_index('timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);

        // Conditionally launch create table for capquiz_question_rating.
        if (!$dbman->table_exists($qtable)) {
            $dbman->create_table($qtable);
        }

        $atable = new xmldb_table('capquiz_attempt');

        $aqrfield = new xmldb_field(
            'question_rating_id', XMLDB_TYPE_INTEGER, 11, null, null, null, null);
        $aqrkey = new xmldb_key(
            'question_rating_id', XMLDB_KEY_FOREIGN, ['question_rating_id'], 'capquiz_question_rating', ['id']);
        $aqprevrfield = new xmldb_field(
            'question_prev_rating_id',
            XMLDB_TYPE_INTEGER, 11, null, null, null, null);
        $aqprevrkey = new xmldb_key(
            'question_prev_rating_id',
            XMLDB_KEY_FOREIGN, ['question_prev_rating_id'], 'capquiz_question_rating', ['id']);

        $aprevqrfield = new xmldb_field(
            'prev_question_rating_id',
            XMLDB_TYPE_INTEGER, 11, null, null, null, null);
        $aprevqrkey = new xmldb_key(
            'prev_question_rating_id',
            XMLDB_KEY_FOREIGN, ['prev_question_rating_id'], 'capquiz_question_rating', ['id']);

        $aprevqprevrfield = new xmldb_field(
            'prev_question_prev_rating_id',
            XMLDB_TYPE_INTEGER, 11, null, null, null, null);
        $aprevqprevrkey = new xmldb_key(
            'prev_question_prev_rating_id',
            XMLDB_KEY_FOREIGN, ['prev_question_prev_rating_id'], 'capquiz_question_rating', ['id']);

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

        $aurfield = new xmldb_field('user_rating_id', XMLDB_TYPE_INTEGER, 11, null, null, null, null);
        $aurkey = new xmldb_key('user_rating_id', XMLDB_KEY_FOREIGN, ['user_rating_id'], 'capquiz_user_rating', ['id']);
        $aprevurfield = new xmldb_field('user_prev_rating_id', XMLDB_TYPE_INTEGER, 11, null, null, null, null);
        $aprevurkey = new xmldb_key(
            'user_prev_rating_id',
            XMLDB_KEY_FOREIGN,
            ['user_prev_rating_id'],
            'capquiz_user_rating',
            ['id'],
        );

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
    if ($oldversion < 2019103100) {
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
    if ($oldversion < 2020091600) {
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
        foreach ($qlists as $qlist) {
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
            foreach ($users as $user) {
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
                    'SELECT DISTINCT qa.id, qa.slot, qa.questionusageid
                       FROM {question_attempts} qa
                       JOIN {question_attempt_steps} qas
                         ON qas.questionattemptid = qa.id
                        AND qas.userid = ?
                      WHERE qa.questionusageid = ?',
                    [$user->user_id, $oldqubaid]
                );

                $slot = 1;
                foreach ($attempts as $attempt) {
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
    if ($oldversion < 2024101300) {
        $table = new xmldb_table('capquiz');
        $field = new xmldb_field('numquestioncandidates', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '10');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('minquestionsuntilreappearance', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('userwinprobability', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '0.75');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('userkfactor', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '32');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('questionkfactor', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '8');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        foreach ($DB->get_records('capquiz') as $capquiz) {
            $selection = $DB->get_record('capquiz_question_selection', ['capquiz_id' => $capquiz->id]);
            if ($selection) {
                $config = json_decode($selection->configuration);
                if ($selection->strategy === 'N-closest') {
                    $capquiz->numquestioncandidates = (int)$config->number_of_questions_to_select;
                    $capquiz->minquestionsuntilreappearance = (int)$config->prevent_same_question_for_turns;
                    $capquiz->userwinprobability = (float)$config->user_win_probability;
                }
                $DB->update_record('capquiz', $capquiz);
                $DB->delete_records('capquiz_question_selection', ['id' => $selection->id]);
            }
            $ratingsystem = $DB->get_record('capquiz_rating_system', ['capquiz_id' => $capquiz->id]);
            if ($ratingsystem) {
                $config = json_decode($ratingsystem->configuration);
                $capquiz->userkfactor = (float)$config->student_k_factor;
                $capquiz->questionkfactor = (float)$config->question_k_factor;
                $DB->update_record('capquiz', $capquiz);
                $DB->delete_records('capquiz_rating_system', ['id' => $ratingsystem->id]);
            }
        }
        $table = new xmldb_table('capquiz_question_selection');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        $table = new xmldb_table('capquiz_rating_system');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        upgrade_mod_savepoint(true, 2024101300, 'capquiz');
    }

    if ($oldversion < 2024102000) {
        // Rename fields to standard names for capquiz_question_list.
        $table = new xmldb_table('capquiz_question_list');
        $field = new xmldb_field('time_modified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $dbman->rename_field($table, $field, 'timemodified');
        $field = new xmldb_field('time_created', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $dbman->rename_field($table, $field, 'timecreated');
        $field = new xmldb_field('default_question_rating', XMLDB_TYPE_FLOAT, '11', null, XMLDB_NOTNULL, null, '600');
        $dbman->rename_field($table, $field, 'defaultquestionrating');

        // Fix default question rating type for capquiz_question_list.
        $field = new xmldb_field('defaultquestionrating');
        $field->setType(XMLDB_TYPE_NUMBER);
        $field->setLength(10);
        $field->setDecimals('2');
        $dbman->change_field_type($table, $field);

        // Add timemodified and timecreated fields to capquiz_question.
        $table = new xmldb_table('capquiz_question');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add timemodified and timecreated fields to capquiz_user.
        $table = new xmldb_table('capquiz_user');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add timemodified and timecreated fields to capquiz_attempt.
        $table = new xmldb_table('capquiz_attempt');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2024102000, 'capquiz');
    }

    if ($oldversion < 2024102100) {
        $table = new xmldb_table('capquiz');
        $field = new xmldb_field('defaultquestionrating', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '600');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        foreach ($DB->get_records('capquiz') as $capquiz) {
            $qlist = $DB->get_record('capquiz_question_list', ['capquiz_id' => $capquiz->id]);
            if ($qlist) {
                $capquiz->defaultquestionrating = $qlist->defaultquestionrating;
                $DB->update_record('capquiz', $capquiz);
            }
        }
        $table = new xmldb_table('capquiz_question_list');
        $field = new xmldb_field('defaultquestionrating');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2024102100, 'capquiz');
    }
    if ($oldversion < 2024102102) {
        $table = new xmldb_table('capquiz');
        $field = new xmldb_field('stars_to_pass', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '3');
        $dbman->rename_field($table, $field, 'starstopass');
        $field = new xmldb_field('starratings', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '1300,1450,1600,1800,2000');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        foreach ($DB->get_records('capquiz') as $capquiz) {
            $qlist = $DB->get_record('capquiz_question_list', ['capquiz_id' => $capquiz->id]);
            if ($qlist) {
                $capquiz->starratings = $qlist->star_ratings;
                $DB->update_record('capquiz', $capquiz);
            }
        }
        $table = new xmldb_table('capquiz_question_list');
        $field = new xmldb_field('star_ratings');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2024102102, 'capquiz');
    }
    if ($oldversion < 2024122900) {
        // Remove question foreign key.
        $table = new xmldb_table('capquiz_question');
        $key = new xmldb_key('question_id', XMLDB_KEY_FOREIGN, ['question_id'], 'question', ['id']);
        $dbman->drop_key($table, $key);

        // Rename question id field to follow guidelines.
        $table = new xmldb_table('capquiz_question');
        $field = new xmldb_field('question_id', XMLDB_TYPE_INTEGER, '11', null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'questionid');
        }

        // Add new question foreign key.
        $table = new xmldb_table('capquiz_question');
        $key = new xmldb_key('questionid', XMLDB_KEY_FOREIGN, ['questionid'], 'question', ['id']);
        $dbman->add_key($table, $key);

        // Add capquiz id field to question table.
        $table = new xmldb_table('capquiz_question');
        $field = new xmldb_field('capquizid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, 0, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Set the capquiz ids for all questions.
        foreach ($DB->get_records('capquiz_question_list') as $qlist) {
            if ($qlist->capquiz_id === null) {
                $questions = $DB->get_records('capquiz_question', ['question_list_id' => $qlist->id]);
                if (!empty($questions)) {
                    $DB->delete_records_list('capquiz_question_rating', 'capquiz_question_id', array_column($questions, 'id'));
                    $DB->delete_records('capquiz_question', ['question_list_id' => $qlist->id]);
                }
                continue;
            }
            foreach ($DB->get_records('capquiz_question', ['question_list_id' => $qlist->id]) as $question) {
                $question->capquizid = $qlist->capquiz_id;
                $DB->update_record('capquiz_question', $question);
            }
        }

        // Remove default value.
        $table = new xmldb_table('capquiz_question');
        $field = new xmldb_field('capquizid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null, null);
        $dbman->change_field_default($table, $field);

        // Add foreign key for capquiz id to the question table.
        $table = new xmldb_table('capquiz_question');
        $key = new xmldb_key('capquizid', XMLDB_KEY_FOREIGN, ['capquizid'], 'capquiz', ['id']);
        $dbman->add_key($table, $key);

        // Remove foreign key to question list from the question table.
        $table = new xmldb_table('capquiz_question');
        $key = new xmldb_key('question_list_id', XMLDB_KEY_FOREIGN, ['question_list_id'], 'capquiz_question_list', ['id']);
        $dbman->drop_key($table, $key);

        // Remove the question list id field from question table.
        $table = new xmldb_table('capquiz_question');
        $field = new xmldb_field('question_list_id');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Drop the question list table.
        $table = new xmldb_table('capquiz_question_list');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Remove timereviewed index from capquiz attempt table.
        $table = new xmldb_table('capquiz_attempt');
        $index = new xmldb_index('timereviewed', XMLDB_INDEX_NOTUNIQUE, ['user_id', 'time_reviewed']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Remove capquiz user foreign key from capquiz attempt.
        $table = new xmldb_table('capquiz_attempt');
        $dbman->drop_key($table, new xmldb_key('user_id', XMLDB_KEY_FOREIGN, ['user_id'], 'capquiz_user', ['id']));

        // Rename capquiz user id field to follow guidelines and make it clear it's a capquiz user.
        $table = new xmldb_table('capquiz_attempt');
        $field = new xmldb_field('user_id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'capquizuserid');
        }

        // Add new capquiz user foreign key to capquiz attempt table.
        $table = new xmldb_table('capquiz_attempt');
        $dbman->add_key($table, new xmldb_key('capquizuserid', XMLDB_KEY_FOREIGN, ['capquizuserid'], 'capquiz_user', ['id']));

        // Remove question foreign key from capquiz attempt.
        $table = new xmldb_table('capquiz_attempt');
        $dbman->drop_key($table, new xmldb_key('question_id', XMLDB_KEY_FOREIGN, ['question_id'], 'capquiz_question', ['id']));

        // Rename capquiz question field to follow guidelines and make it clear it's a capquiz question.
        $table = new xmldb_table('capquiz_attempt');
        $field = new xmldb_field('question_id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'capquizquestionid');
        }

        // Add new capquiz question foreign key to capquiz attempt table.
        $table = new xmldb_table('capquiz_attempt');
        $key = new xmldb_key('capquizquestionid', XMLDB_KEY_FOREIGN, ['capquizquestionid'], 'capquiz_question', ['id']);
        $dbman->add_key($table, $key);

        // Rename capquiz id field in capquiz user.
        $table = new xmldb_table('capquiz_user');
        $key = new xmldb_key('capquiz_id', XMLDB_KEY_FOREIGN, ['capquiz_id'], 'capquiz', ['id']);
        $dbman->drop_key($table, $key);
        $table = new xmldb_table('capquiz_user');
        $field = new xmldb_field('capquiz_id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'capquizid');
        }
        $table = new xmldb_table('capquiz_user');
        $dbman->add_key($table, new xmldb_key('capquizid', XMLDB_KEY_FOREIGN, ['capquizid'], 'capquiz', ['id']));

        // Rename user id field in capquiz user.
        $table = new xmldb_table('capqiz_user');
        $key = new xmldb_key('user_id', XMLDB_KEY_FOREIGN, ['user_id'], 'user', ['id']);
        $dbman->drop_key($table, $key);
        $table = new xmldb_table('capquiz_user');
        $field = new xmldb_field('user_id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'userid');
        }
        $table = new xmldb_table('capquiz_user');
        $dbman->add_key($table, new xmldb_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']));

        // Rename question usage id field in capquiz user.
        $table = new xmldb_table('capquiz_user');
        $key = new xmldb_key('question_usage_id', XMLDB_KEY_FOREIGN_UNIQUE, ['question_usage_id'], 'question_usages', ['id']);
        $dbman->drop_key($table, $key);
        $table = new xmldb_table('capquiz_user');
        $field = new xmldb_field('question_usage_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'questionusageid');
        }
        $table = new xmldb_table('capquiz_user');
        $key = new xmldb_key('questionusageid', XMLDB_KEY_FOREIGN_UNIQUE, ['questionusageid'], 'question_usages', ['id']);
        $dbman->add_key($table, $key);

        // Rename highest level field in capquiz user.
        $table = new xmldb_table('capquiz_user');
        $field = new xmldb_field('highest_level', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, 0);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'higheststars');
        }

        // Rename stars graded field in capquiz user.
        $table = new xmldb_table('capquiz_user');
        $field = new xmldb_field('stars_graded', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'starsgraded');
        }

        // Add timemodified and usermodified fields to capquiz_user_rating.
        $table = new xmldb_table('capquiz_user_rating');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $table = new xmldb_table('capquiz_user_rating');
        $field = new xmldb_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add timemodified and usermodified fields to capquiz_question_rating.
        $table = new xmldb_table('capquiz_question_rating');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $table = new xmldb_table('capquiz_question_rating');
        $field = new xmldb_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add usermodified field to capquiz_attempt.
        $table = new xmldb_table('capquiz_attempt');
        $field = new xmldb_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add usermodified field to capquiz_user.
        $table = new xmldb_table('capquiz_user');
        $field = new xmldb_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add usermodified field to capquiz_question.
        $table = new xmldb_table('capquiz_question');
        $field = new xmldb_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add usermodified field to capquiz.
        $table = new xmldb_table('capquiz');
        $field = new xmldb_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Rename time answered field in capquiz_attempt.
        $table = new xmldb_table('capquiz_attempt');
        $field = new xmldb_field('time_answered', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'timeanswered');
        }

        // Rename time reviewed field in capquiz_attempt.
        $table = new xmldb_table('capquiz_attempt');
        $field = new xmldb_field('time_reviewed', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'timereviewed');
        }

        // Rename capquiz question id field in capquiz question rating.
        $table = new xmldb_table('capquiz_question_rating');
        $key = new xmldb_key('capquiz_question_id', XMLDB_KEY_FOREIGN, ['capquiz_question_id'], 'capquiz_question', ['id']);
        $dbman->drop_key($table, $key);
        $table = new xmldb_table('capquiz_question_rating');
        $field = new xmldb_field('capquiz_question_id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'capquizquestionid');
        }
        $table = new xmldb_table('capquiz_question_rating');
        $key = new xmldb_key('capquizquestionid', XMLDB_KEY_FOREIGN, ['capquizquestionid'], 'capquiz_question', ['id']);
        $dbman->add_key($table, $key);

        // Rename capquiz user id field in capquiz user rating.
        $table = new xmldb_table('capquiz_user_rating');
        $key = new xmldb_key('capquiz_user_id', XMLDB_KEY_FOREIGN, ['capquiz_user_id'], 'capquiz_user', ['id']);
        $dbman->drop_key($table, $key);
        $table = new xmldb_table('capquiz_user_rating');
        $field = new xmldb_field('capquiz_user_id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'capquizuserid');
        }
        $table = new xmldb_table('capquiz_user_rating');
        $key = new xmldb_key('capquizuserid', XMLDB_KEY_FOREIGN, ['capquizuserid'], 'capquiz_user', ['id']);
        $dbman->add_key($table, $key);

        // Add timeopen field to capquiz.
        $table = new xmldb_table('capquiz');
        $field = new xmldb_field('timeopen', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Set timeopen and timedue fields based on published state.
        foreach ($DB->get_records('capquiz') as $capquiz) {
            $capquiz->timeopen = $capquiz->published ? time() : 0;
            $DB->update_record('capquiz', $capquiz);
        }

        // Remove published field from capquiz.
        $table = new xmldb_table('capquiz');
        $field = new xmldb_field('published', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2024122900, 'capquiz');
    }

    if ($oldversion < 2024122901) {
        // Add question behaviour field to capquiz.
        $table = new xmldb_table('capquiz');
        $field = new xmldb_field('questionbehaviour', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, 'immediatefeedback');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2024122901, 'capquiz');
    }

    if ($oldversion < 2024122902) {
        // Rename capquiz question rating id field in capquiz attempt.
        $table = new xmldb_table('capquiz_attempt');
        $key = new xmldb_key('question_rating_id', XMLDB_KEY_FOREIGN, ['question_rating_id'], 'capquiz_question_rating', ['id']);
        $dbman->drop_key($table, $key);
        $field = new xmldb_field('question_rating_id', XMLDB_TYPE_INTEGER, '11', null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'questionratingid');
        }
        $key = new xmldb_key('questionratingid', XMLDB_KEY_FOREIGN, ['questionratingid'], 'capquiz_question_rating', ['id']);
        $dbman->add_key($table, $key);

        // Rename question_prev_rating_id.
        $table = new xmldb_table('capquiz_attempt');
        $key = new xmldb_key(
            'question_prev_rating_id',
            XMLDB_KEY_FOREIGN,
            ['question_prev_rating_id'],
            'capquiz_question_rating',
            ['id'],
        );
        $dbman->drop_key($table, $key);
        $field = new xmldb_field('question_prev_rating_id', XMLDB_TYPE_INTEGER, '11', null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'questionprevratingid');
        }
        $key = new xmldb_key(
            'questionprevratingid',
            XMLDB_KEY_FOREIGN,
            ['questionprevratingid'],
            'capquiz_question_rating',
            ['id'],
        );
        $dbman->add_key($table, $key);

        // Rename prev_question_rating_id.
        $table = new xmldb_table('capquiz_attempt');
        $key = new xmldb_key(
            'prev_question_rating_id',
            XMLDB_KEY_FOREIGN,
            ['prev_question_rating_id'],
            'capquiz_question_rating',
            ['id'],
        );
        $dbman->drop_key($table, $key);
        $field = new xmldb_field('prev_question_rating_id', XMLDB_TYPE_INTEGER, '11', null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'prevquestionratingid');
        }
        $key = new xmldb_key(
            'prevquestionratingid',
            XMLDB_KEY_FOREIGN,
            ['prevquestionratingid'],
            'capquiz_question_rating',
            ['id'],
        );
        $dbman->add_key($table, $key);

        // Rename prev_question_prev_rating_id.
        $table = new xmldb_table('capquiz_attempt');
        $key = new xmldb_key(
            'prev_question_prev_rating_id',
            XMLDB_KEY_FOREIGN,
            ['prev_question_prev_rating_id'],
            'capquiz_question_rating',
            ['id'],
        );
        $dbman->drop_key($table, $key);
        $field = new xmldb_field('prev_question_prev_rating_id', XMLDB_TYPE_INTEGER, '11', null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'prevquestionprevratingid');
        }
        $key = new xmldb_key(
            'prevquestionprevratingid',
            XMLDB_KEY_FOREIGN,
            ['prevquestionprevratingid'],
            'capquiz_question_rating',
            ['id'],
        );
        $dbman->add_key($table, $key);

        // Rename user_rating_id.
        $table = new xmldb_table('capquiz_attempt');
        $key = new xmldb_key('user_rating_id', XMLDB_KEY_FOREIGN, ['user_rating_id'], 'capquiz_user_rating', ['id']);
        $dbman->drop_key($table, $key);
        $field = new xmldb_field('user_rating_id', XMLDB_TYPE_INTEGER, '11', null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'userratingid');
        }
        $table = new xmldb_table('capquiz_attempt');
        $key = new xmldb_key('userratingid', XMLDB_KEY_FOREIGN, ['userratingid'], 'capquiz_user_rating', ['id']);
        $dbman->add_key($table, $key);

        // Rename user_prev_rating_id.
        // The foreign key was wrong in the install.xml, causing new installs to not add the key due to it being a duplicate.
        // This will still try to delete the correct one, but we'll ignore the exception in case it fails.
        try {
            $table = new xmldb_table('capquiz_attempt');
            $key = new xmldb_key(
                'user_prev_rating_id',
                XMLDB_KEY_FOREIGN,
                ['user_prev_rating_id'],
                'capquiz_user_rating',
                ['id'],
            );
            $dbman->drop_key($table, $key);
        } catch (\Exception) {
            // Try to avoid code checker complaining.
            $table = new xmldb_table('capquiz_attempt');
        }
        $field = new xmldb_field('user_prev_rating_id', XMLDB_TYPE_INTEGER, '11', null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'userprevratingid');
        }
        $key = new xmldb_key('userprevratingid', XMLDB_KEY_FOREIGN, ['userprevratingid'], 'capquiz_user_rating', ['id']);
        $dbman->add_key($table, $key);

        upgrade_mod_savepoint(true, 2024122902, 'capquiz');
    }

    if ($oldversion < 2024122903) {
        // Add capquiz id to capquiz attempt table to avoid having to go through capquiz user unnecessarily.
        $table = new xmldb_table('capquiz_attempt');
        $field = new xmldb_field('capquizid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, 0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            $total = $DB->count_records('capquiz_attempt');
            if ($total > 0) {
                $progressbar = new progress_bar('upgradecapquizattempts', 500, true);
                $progress = 0;
                $attempts = $DB->get_recordset('capquiz_attempt');
                // While it's possible to do this with one query, it's probably best to prioritize compatibility here.
                foreach ($attempts as $attempt) {
                    $user = $DB->get_record('capquiz_user', ['id' => $attempt->capquizuserid], '*', MUST_EXIST);
                    $attempt->capquizid = $user->capquizid;
                    $DB->update_record('capquiz_attempt', $attempt);
                    $progress++;
                    $progressbar->update($progress, $total, "Upgrading CAPQuiz question attempts - $progress/$total.");
                }
                $attempts->close();
            }
            $field->setDefault(null);
            $dbman->change_field_default($table, $field);
            $key = new xmldb_key('capquizid', XMLDB_KEY_FOREIGN, ['capquizid'], 'capquiz', ['id']);
            $dbman->add_key($table, $key);
        }
        upgrade_mod_savepoint(true, 2024122903, 'capquiz');
    }

    if ($oldversion < 2024122904) {
        // Drop old keys and rename columns.
        $table = new xmldb_table('capquiz_attempt');
        $key = new xmldb_key('capquizquestionid', XMLDB_KEY_FOREIGN, ['capquizquestionid'], 'capquiz_question', ['id']);
        if ($dbman->find_key_name($table, $key)) {
            $dbman->drop_key($table, $key);
        }
        $field = new xmldb_field('capquizquestionid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $dbman->rename_field($table, $field, 'slotid');

        $table = new xmldb_table('capquiz_question_rating');
        $key = new xmldb_key('capquizquestionid', XMLDB_KEY_FOREIGN, ['capquizquestionid'], 'capquiz_question', ['id']);
        if ($dbman->find_key_name($table, $key)) {
            $dbman->drop_key($table, $key);
        }
        $field = new xmldb_field('capquizquestionid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $dbman->rename_field($table, $field, 'slotid');

        // Rename question table to slot.
        $table = new xmldb_table('capquiz_question');
        $dbman->rename_table($table, 'capquiz_slot');

        // Add new keys.
        $table = new xmldb_table('capquiz_attempt');
        $key = new xmldb_key('slotid', XMLDB_KEY_FOREIGN, ['slotid'], 'capquiz_slot', ['id']);
        $dbman->add_key($table, $key);

        $table = new xmldb_table('capquiz_question_rating');
        $key = new xmldb_key('slotid', XMLDB_KEY_FOREIGN, ['slotid'], 'capquiz_slot', ['id']);
        $dbman->add_key($table, $key);

        // Migrate from questionids to question references, and change all to latest version.
        foreach ($DB->get_records('capquiz') as $capquiz) {
            $cmid = get_coursemodule_from_instance('capquiz', $capquiz->id)->id;
            $context = \core\context\module::instance($cmid);
            foreach ($DB->get_records('capquiz_slot', ['capquizid' => $capquiz->id]) as $slot) {
                $questionbankentry = get_question_bank_entry($slot->questionid);
                $DB->insert_record('question_references', (object)[
                    'usingcontextid' => $context->id,
                    'component' => 'mod_capquiz',
                    'questionarea' => 'slot',
                    'itemid' => $slot->id,
                    'questionbankentryid' => $questionbankentry->id,
                    'version' => null,
                ]);
            }
        }

        // Remove questionid field from slot.
        $table = new xmldb_table('capquiz_slot');
        $key = new xmldb_key('questionid', XMLDB_KEY_FOREIGN, ['questionid'], 'question', ['id']);
        if ($dbman->find_key_name($table, $key)) {
            $dbman->drop_key($table, $key);
        }
        $field = new xmldb_field('questionid', XMLDB_TYPE_INTEGER, 11, null, XMLDB_NOTNULL, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2024122904, 'capquiz');
    }

    if ($oldversion < 2025010500) {
        $table = new xmldb_table('capquiz');
        $field = new xmldb_field('questiondisplayoptions', XMLDB_TYPE_TEXT, null, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            foreach ($DB->get_records('capquiz') as $capquiz) {
                $capquiz->questiondisplayoptions = json_encode([]);
                $DB->update_record('capquiz', $capquiz);
            }
            $field->setNotNull();
            $dbman->change_field_notnull($table, $field);
        }
        upgrade_mod_savepoint(true, 2025010500, 'capquiz');
    }

    if ($oldversion < 2025101400) {
        $DB->execute('DELETE FROM {capquiz_question_rating} WHERE slotid NOT IN (SELECT id FROM {capquiz_slot})');
        upgrade_mod_savepoint(true, 2025101400, 'capquiz');
    }

    if ($oldversion < 2025101401) {
        $table = new xmldb_table('capquiz');
        $field = new xmldb_field('default_user_rating', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1200');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'defaultuserrating');
        }

        $field = new xmldb_field('defaultuserrating');
        $field->setType(XMLDB_TYPE_NUMBER);
        $field->setLength(10);
        $field->setDecimals('2');
        $dbman->change_field_type($table, $field);

        upgrade_mod_savepoint(true, 2025101401, 'capquiz');
    }

    if ($oldversion < 2025101402) {
        $sql = "DELETE
                  FROM {question_references}
                 WHERE component = 'mod_capquiz'
                   AND questionarea = 'slot'
                   AND itemid NOT IN (SELECT id FROM {capquiz_slot})";
        $DB->execute($sql);

        upgrade_mod_savepoint(true, 2025101402, 'capquiz');
    }

    if ($oldversion < 2025101403) {
        $sql = "DELETE
                  FROM {capquiz_question_rating}
                 WHERE slotid NOT IN (SELECT id FROM {capquiz_slot})";
        $DB->execute($sql);

        upgrade_mod_savepoint(true, 2025101403, 'capquiz');
    }

    return true;
}
