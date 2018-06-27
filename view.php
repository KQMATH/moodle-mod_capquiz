<?php

namespace mod_capquiz;

require_once("../../config.php");

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/mod/capquiz/lib.php');
require_once($CFG->dirroot . '/mod/capquiz/urls.php');

namespace mod_capquiz;

function redirect_to_front_page()
{
    header('Location: /');
    exit;
}

function set_view_url(capquiz $capquiz)
{
    global $PAGE;

    $PAGE->set_context($capquiz->context());
    $PAGE->set_cm($capquiz->course_module());
    $PAGE->set_pagelayout('incourse');
    $url = new \moodle_url(capquiz_urls::$url_view);
    $url->param(capquiz_urls::$param_id, $capquiz->course_module_id());
    $PAGE->set_url($url);
}

function render_view(capquiz $capquiz, \mod_capquiz_renderer $renderer)
{
    if ($capquiz->is_instructor()) {
        $renderer->show_instructor_view($capquiz);
    } else if ($capquiz->is_student()) {
        $renderer->show_student_view($capquiz);
    } else {
        $renderer->show_unauthorized_view($capquiz);
    }
}

function capquiz_view()
{
    $course_module_id = optional_param(capquiz_urls::$param_id, false, PARAM_INT);
    if ($course_module_id) {
        $capquiz = new capquiz($course_module_id);
        set_view_url($capquiz);
        render_view($capquiz, $capquiz->renderer());
    } else {
        redirect_to_front_page();
    }
}

capquiz_view();
