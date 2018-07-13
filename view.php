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

require_once('../../config.php');

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/mod/capquiz/lib.php');

function return_to_previous() {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

function set_view_url(capquiz $capquiz) {
    global $PAGE;
    $PAGE->set_context($capquiz->context());
    $PAGE->set_cm($capquiz->course_module());
    $PAGE->set_pagelayout('incourse');
    $url = new \moodle_url(capquiz_urls::$url_view);
    $url->param(capquiz_urls::$param_id, $capquiz->course_module_id());
    $PAGE->set_url($url);
}

function set_create_question_list_url(capquiz $capquiz) {
    global $PAGE;
    $PAGE->set_context($capquiz->context());
    $PAGE->set_cm($capquiz->course_module());
    $PAGE->set_pagelayout('incourse');
    $url = new \moodle_url(capquiz_urls::$url_view);
    $url->param(capquiz_urls::$param_id, $capquiz->course_module_id());
    $url->param(capquiz_actions::$parameter, capquiz_actions::$create_question_list);
    $PAGE->set_url($url);
}

function create_question_set_view() {
    $course_module_id = 0;
    if (isset($_POST[capquiz_urls::$param_id])) {
        $course_module_id = $_POST[capquiz_urls::$param_id];
    } else if ($id = optional_param(capquiz_urls::$param_id, 0, PARAM_INT)) {
        $course_module_id = $id;
    }
    if ($course_module_id === 0) {
        header('Location: /');
        exit;
    } else {
        $capquiz = new capquiz($course_module_id);
        set_create_question_list_url($capquiz);
    }
}

function capquiz_view() {
    $cmid = optional_param(capquiz_urls::$param_id, false, PARAM_INT);
    if (!$cmid) {
        header('Location: /');
        exit;
    }
    $capquiz = new capquiz($cmid);
    set_view_url($capquiz);
    $renderer = $capquiz->renderer();
    if ($capquiz->is_instructor()) {
        $renderer->display_instructor_view($capquiz);
    } else if ($capquiz->is_student()) {
        $renderer->display_student_view($capquiz);
    } else {
        $renderer->display_unauthorized_view();
    }
}

capquiz_view();
