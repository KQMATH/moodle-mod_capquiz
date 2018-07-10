<?php

namespace mod_capquiz;

require_once($CFG->dirroot . '/mod/capquiz/classes/capquiz_question_attempt.php');

class question_attempt_view {

    private $capquiz;
    private $attempt;

    public function __construct(capquiz $capquiz, capquiz_question_attempt $attempt) {
        $this->capquiz = $capquiz;
        $this->attempt = $attempt;
    }

    public function show() {
        global $PAGE;
        $PAGE->requires->js_module('core_question_engine');
        $this->render_question();
    }

    private function render_question() {
        echo '<form id="responseform" method="post" action="' . $this->response_submit_url() . '" enctype="multipart/form-data" accept-charset="utf-8">', "\n<div>\n";
        echo '<input type="hidden" name="slots" value="' . implode(',', 1) . "\" />\n";
        echo '<input type="hidden" name="scrollpos" value="" />';
        echo $this->capquiz->question_usage()->render_question($this->attempt->question_slot(), $this->display_options(), $this->attempt->question_id());
    }

    private function response_submit_url() {
        return capquiz_urls::create_response_submit_url($this->capquiz, $this->attempt);
    }

    private function display_options() {
        $options = new \question_display_options();
        $options->context = $this->capquiz->context();
        $options->flags = \question_display_options::HIDDEN;
        $options->marks = \question_display_options::HIDDEN;
        $options->rightanswer = \question_display_options::VISIBLE;
        $options->numpartscorrect = \question_display_options::HIDDEN;
        $options->manualcomment = \question_display_options::HIDDEN;
        $options->manualcommentlink = \question_display_options::HIDDEN;
        return $options;
    }

}