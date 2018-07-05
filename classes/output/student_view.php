<?php

namespace mod_capquiz;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/capquiz/classes/output/student/question_attempt_view.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/student/question_summary_view.php');

class student_view {

    private $capquiz;
    private $renderer;

    public function __construct(capquiz $capquiz, \core_renderer $renderer) {
        $this->capquiz = $capquiz;
        $this->renderer = $renderer;
    }

    public function show() {
        $user = $this->capquiz->user();
        echo $this->renderer->header();
        if (!$this->capquiz->is_published()) {
            echo '<h3>Nothing here yet.</h3>';
        } else if ($attempt = $this->capquiz->question_engine()->attempt_for_user($user)) {
            $this->show_attempt($attempt);
        } else if ($this->capquiz->question_engine()->user_is_completed($user)) {
            echo '<h3>You have completed this quiz</h3>';
        } else {
            echo '<h3>Something went wrong</h3>';
        }
        echo $this->renderer->footer();
    }

    private function show_attempt(capquiz_question_attempt $attempt) {
        if ($attempt->is_pending()) {
            $view = null;
            if ($attempt->is_answered()) {
                $view = new question_summary_view($this->capquiz, $attempt);
            } else {
                $view = new question_attempt_view($this->capquiz, $attempt);
            }
            $view->show();
        }
    }

}
