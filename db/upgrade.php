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
 * @package     mod_capquiz
 * @author      Sebastian S. Gundersen <sebastian@sgundersen.com>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function xmldb_capquiz_upgrade($oldversion) {
    global $DB;
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
    return true;
}
