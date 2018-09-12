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

use mod_capquiz\capquiz;

defined('MOODLE_INTERNAL') || die();

/**
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function capquiz($capquiz) {
    global $DB;
    $capquiz->id = $DB->insert_record('capquiz', $capquiz);
    return $capquiz->id;
}

function capquiz_add_instance(stdClass $capquiz_mod_form_data) {
    global $DB;
    $capquiz_mod_form_data->time_modified = time();
    $capquiz_mod_form_data->time_created = time();
    $capquiz_mod_form_data->published = false;
    $capquiz_mod_form_data->question_list_id = null;
    $capquiz_mod_form_data->question_usage_id = null;
    return $DB->insert_record('capquiz', $capquiz_mod_form_data);
}

function capquiz_update_instance(stdClass $capquiz) {
    global $DB;
    $capquiz->id = $capquiz->instance;
    $DB->update_record('capquiz', $capquiz);
    return true;
}

function capquiz_delete_instance(int $id) {
    if ($capquiz = capquiz::create_from_id($id)) {
        $question_usage = $capquiz->question_usage();
        \question_engine::delete_questions_usage_by_activity($question_usage->get_id());
    }
}

function capquiz_cron() {
    return true;
}

function capquiz_supports($feature) {
    return false;
}
