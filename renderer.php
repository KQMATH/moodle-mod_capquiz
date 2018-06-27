<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/capquiz/classes/output/student_view.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/instructor_view.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/unauthorized_view.php');

class mod_capquiz_renderer extends \plugin_renderer_base
{
    public function output_renderer()
    {
        return $this->output;
    }

    public function show_student_view(\mod_capquiz\capquiz $capquiz)
    {
        $renderer = new mod_capquiz\student_view($capquiz, $this->output);
        $renderer->show();
    }

    public function show_instructor_view(\mod_capquiz\capquiz $capquiz)
    {
        $renderer = new mod_capquiz\instructor_view($capquiz, $this->output);
        $renderer->show();
    }

    public function show_unauthorized_view(\mod_capquiz\capquiz $capquiz)
    {
        $renderer = new mod_capquiz\unauthorized_view($capquiz, $this->output);
        $renderer->show();
    }
}
