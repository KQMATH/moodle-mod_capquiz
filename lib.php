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
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_capquiz\capquiz;

defined('MOODLE_INTERNAL') || die();

function capquiz_add_instance(stdClass $modformdata) {
    global $DB;
    $modformdata->time_modified = time();
    $modformdata->time_created = time();
    $modformdata->published = false;
    $modformdata->question_list_id = null;
    $modformdata->question_usage_id = null;
    return $DB->insert_record('capquiz', $modformdata);
}

function capquiz_update_instance(stdClass $capquiz) {
    global $DB;
    $capquiz->id = $capquiz->instance;
    $DB->update_record('capquiz', $capquiz);
    return true;
}

function capquiz_delete_instance(int $id) {
    $capquiz = capquiz::create_from_id($id);
    if ($capquiz) {
        $quba = $capquiz->question_usage();
        \question_engine::delete_questions_usage_by_activity($quba->get_id());
    }
}

function capquiz_cron() {
    return true;
}

function capquiz_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_USES_QUESTIONS:
            return true;
        default:
            return false;
    }
}
