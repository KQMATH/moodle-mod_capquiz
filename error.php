<?php

namespace mod_capquiz;

require_once("../../config.php");

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/mod/capquiz/lib.php');
require_once($CFG->dirroot . '/mod/capquiz/urls.php');

function redirect_to_front_page() {
    header('Location: /');
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

function render_error_view(capquiz $capquiz, \mod_capquiz_renderer $renderer) {
    echo $renderer->output_renderer()->header();
    if ($capquiz->is_instructor()) {
        echo 'Something went wrong(instructor)';
    } else if ($capquiz->is_student()) {
        echo 'Something went wrong(student)';
    }
    echo $renderer->output_renderer()->footer();
}

function error_view() {
    $course_module_id = optional_param(capquiz_urls::$param_id, false, PARAM_INT);
    if ($course_module_id) {
        $capquiz = new capquiz($course_module_id);
        set_view_url($capquiz);
        render_error_view($capquiz, $capquiz->renderer());
    } else {
        redirect_to_front_page();
    }
}

error_view();
