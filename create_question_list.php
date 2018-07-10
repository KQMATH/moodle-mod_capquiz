<?php

namespace mod_capquiz;

require_once("../../config.php");

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/mod/capquiz/lib.php');
require_once($CFG->dirroot . '/mod/capquiz/urls.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/instructor/question_list_create_view.php');

function return_to_previous() {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

function return_to_frontpage() {
    header('Location: /');
    exit;
}

function set_create_question_list_url(capquiz $capquiz) {
    global $PAGE;
    $PAGE->set_context($capquiz->context());
    $PAGE->set_cm($capquiz->course_module());
    $PAGE->set_pagelayout('incourse');
    $url = new \moodle_url(capquiz_urls::$url_create_question_list);
    $url->param('id', $capquiz->course_module_id());
    $PAGE->set_url($url);
}

function render_create_question_list_view(capquiz $capquiz) {
    $assign_set_when_created = optional_param('assign-created', false, PARAM_BOOL);
    $create_view = new question_list_create_view($capquiz, $capquiz->output());
    if ($assign_set_when_created) {
        $create_view->assign_question_set_when_created();
    }
    $create_view->show();
}

function create_question_set_view() {
    $course_module_id = 0;
    if (isset($_POST[capquiz_urls::$param_id])) {
        $course_module_id = $_POST[capquiz_urls::$param_id];
    } else if ($id = optional_param(capquiz_urls::$param_id, 0, PARAM_INT)) {
        $course_module_id = $id;
    }
    if ($course_module_id === 0) {
        return_to_frontpage();
    } else {
        $capquiz = new capquiz($course_module_id);
        set_create_question_list_url($capquiz);
        render_create_question_list_view($capquiz);
    }
}

create_question_set_view();
