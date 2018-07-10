<?php

namespace mod_capquiz;

require_once($CFG->dirroot . '/mod/capquiz/classes/capquiz_question_attempt.php');

class question_summary_view {

    private $capquiz;
    private $attempt;

    public function __construct(capquiz $capquiz, capquiz_question_attempt $attempt) {
        $this->capquiz = $capquiz;
        $this->attempt = $attempt;
    }

    public function show() {
        $this->render_feedback();
        $this->show_next_button();
    }

    private function render_feedback() {
        global $PAGE;
        $question_usage = $this->capquiz->question_usage();
        $moodle_question = $question_usage->get_question($this->attempt->question_slot());
        $renderer = $moodle_question->get_renderer($PAGE);
        $moodle_attempt = $question_usage->get_question_attempt($this->attempt->question_slot());
        $response_summary = $question_usage->get_response_summary($this->attempt->question_slot());

        echo 'Your answer: ' . ($response_summary === null ? ' not registered' : $response_summary);
        echo $renderer->feedback($moodle_attempt, $this->display_options());
    }

    private function show_next_button() {
        $url = capquiz_urls::create_response_reviewed_url($this->capquiz, $this->attempt);
        echo \html_writer::div($this->capquiz->renderer()->single_button($url, get_string('next', 'capquiz')));
    }

    private function display_options() {
        $options = new \question_display_options();
        $options->context = $this->capquiz->context();
        $options->flags = \question_display_options::VISIBLE;
        $options->marks = \question_display_options::VISIBLE;
        $options->rightanswer = \question_display_options::VISIBLE;
        $options->numpartscorrect = \question_display_options::VISIBLE;
        $options->manualcomment = \question_display_options::VISIBLE;
        $options->manualcommentlink = \question_display_options::VISIBLE;
        return $options;
    }
    
}