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

namespace mod_capquiz;

require_once("../../config.php");

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/mod/capquiz/lib.php');
require_once($CFG->dirroot . '/mod/capquiz/utility.php');

function redirect_to(capquiz $capquiz) {
    if ($target_url = optional_param(capquiz_urls::$param_target_url, null, PARAM_TEXT)) {
        redirect_to_url(new \moodle_url($target_url));
    }
}

function assign_question_list(capquiz $capquiz) {
    if ($question_list_id = optional_param(capquiz_urls::$param_question_list_id, 0, PARAM_TEXT)) {
        $capquiz->assign_question_list($question_list_id);
    }
}

function create_capquiz_question(int $question_id, capquiz_question_list $list, float $rating = 600) {
    global $DB;
    $rated_question = new \stdClass();
    $rated_question->question_list_id = $list->id();
    $rated_question->question_id = $question_id;
    $rated_question->rating = $rating;
    $DB->insert_record(database_meta::$table_capquiz_question, $rated_question);
}

function add_question_to_list(capquiz $capquiz) {
    if ($question_id = optional_param(capquiz_urls::$param_question_id, 0, PARAM_TEXT)) {
        if (!$capquiz->has_question_list()) {
            if ($question_list_id = optional_param(capquiz_urls::$param_question_list_id, 0, PARAM_TEXT)) {
                create_capquiz_question($question_id, $capquiz->question_registry()->question_list($question_list_id), $capquiz->default_question_rating());
            }
        } else {
            create_capquiz_question($question_id, $capquiz->question_list());
        }
        redirect_to_url(capquiz_urls::view_question_list_url());
    }
}

function publish_question_list(capquiz $capquiz) {
    if (!$capquiz->publish()) {
        throw new \Exception("Unable to publish question list for CAPQuiz " . $capquiz->name());
    }
}

function set_question_rating(capquiz $capquiz) {
    $question_id = required_param(capquiz_urls::$param_question_id, PARAM_INT);
    if ($question = $capquiz->question_list()->question($question_id)) {
        if ($rating = optional_param(capquiz_urls::$param_rating, null, PARAM_FLOAT)) {
            $question->set_rating($rating);
        } else if ($rating = $_POST[capquiz_urls::$param_rating]) {
            $question->set_rating($rating);
        }
        redirect_to_url(capquiz_urls::view_question_list_url());
    } else {
        throw new \Exception("The specified question does not exist");
    }
}

function determine_action(capquiz $capquiz, string $action_type) {
    $capquiz->require_instructor_capability();
    if ($action_type == capquiz_actions::$redirect) {
        redirect_to($capquiz);
    } else if ($action_type == capquiz_actions::$set_question_list) {
        assign_question_list($capquiz);
    } else if ($action_type == capquiz_actions::$add_question_to_list) {
        add_question_to_list($capquiz);
    } else if ($action_type == capquiz_actions::$publish_question_list) {
        publish_question_list($capquiz);
    } else if ($action_type == capquiz_actions::$set_question_rating) {
        set_question_rating($capquiz);
    }
    redirect_to_dashboard($capquiz);
}

function capquiz_action() {
    $action_type = required_param(capquiz_actions::$parameter, PARAM_TEXT);
    if ($capquiz = capquiz::create()) {
        determine_action($capquiz, $action_type);
    } else {
        redirect_to_front_page();
    }
}

capquiz_action();