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

function determine_action(capquiz $capquiz, string $action_type) {
    $capquiz->require_instructor_capability();
    if ($action_type == capquiz_actions::$redirect) {
        redirect_to($capquiz);
    }
    else if ($action_type == capquiz_actions::$set_question_list) {
        assign_question_list($capquiz);
    }
    else if ($action_type == capquiz_actions::$add_question_to_list) {
        add_question_to_list($capquiz);
    }
    else if ($action_type == capquiz_actions::$remove_question_from_list) {
        remove_question_from_list($capquiz);
    }
    else if ($action_type == capquiz_actions::$publish_question_list) {
        publish_question_list($capquiz);
    }
    else if ($action_type == capquiz_actions::$set_question_rating) {
        set_question_rating($capquiz);
    }
    redirect_to_dashboard($capquiz);
}

$question_page = optional_param(capquiz_urls::$param_question_page, 0, PARAM_INT);
if ($capquiz = capquiz::create()) {
    redirect_to_url(capquiz_urls::view_question_list_url($question_page));
}

redirect_to_front_page();