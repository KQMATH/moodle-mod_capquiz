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
 * @author      Sebastian S. Gundersen <sebastsg@stud.ntnu.no>
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
    return true;
}
