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

namespace mod_capquiz\output;

use mod_capquiz\capquiz;
use mod_capquiz\capquiz_urls;
use mod_capquiz\capquiz_question;
use mod_capquiz\capquiz_question_attempt;

defined('MOODLE_INTERNAL') || die();

class question_attempt_renderer {

    private $capquiz;
    private $renderer;

    public function __construct(capquiz $capquiz, renderer $renderer) {
        $this->capquiz = $capquiz;
        $this->renderer = $renderer;
    }

    public function render() {
        if (!$this->capquiz->is_published())
            return "Nothing here yet";
        $question_engine = $this->capquiz->question_engine();
        if ($attempt = $question_engine->attempt_for_user($this->capquiz->user())) {
            if ($attempt->is_answered())
                return $this->render_question_review($attempt);
            else if ($attempt->is_pending())
                return $this->render_question_attempt($attempt);
        } else {
            return $this->render_quiz_finished();
        }
    }

    private function render_question_attempt(capquiz_question_attempt $attempt) {
        global $PAGE;
        $question_usage = $this->capquiz->question_usage();
        $context = $this->capquiz->context();
        $question = capquiz_question::load($attempt->question_id());
        $displayoptions = $this->summary_display_options($context);

        $PAGE->requires->js_module('core_question_engine');
        return $this->renderer->render_from_template('capquiz/student_question_attempt', [
                'question' => [
                    'student' => [
                        'rating' => $this->capquiz->user()->rating()
                    ],
                    'question' => [
                        'id' => $question->id(),
                        'rating' => $question->rating()
                    ],
                    'attempt' => [
                        'url' => capquiz_urls::response_submit_url($attempt)->out_as_local_url(false),
                        'body' => $question_usage->render_question($attempt->question_slot(), $displayoptions, $attempt->question_id()),
                        'slots' => ''
                    ]
                ]
            ]
        );
    }

    private function render_question_review(capquiz_question_attempt $attempt) {
        global $PAGE;
        $question_usage = $this->capquiz->question_usage();
        $displayoptions = $this->attempt_display_options($this->capquiz->context());
        $moodle_question = $question_usage->get_question($attempt->question_slot());
        $questionrenderer = $moodle_question->get_renderer($PAGE);
        $questionattempt = $question_usage->get_question_attempt($attempt->question_slot());

        return $this->renderer->render_from_template('capquiz/student_question_review', [
                'summary' => [
                    'response' => $question_usage->get_response_summary($attempt->question_slot()),
                    'feedback' => $questionrenderer->feedback($questionattempt, $displayoptions),
                    'next' => [
                        'primary' => true,
                        'method' => 'post',
                        'url' => capquiz_urls::response_reviewed_url($attempt)->out_as_local_url(false),
                        'label' => get_string('next', 'capquiz')
                    ]
                ]
            ]
        );
    }

    private function render_quiz_finished() {
        return $this->renderer->render_from_template('capquiz/student_quiz_finished', []);
    }

    private function summary_display_options($context) {
        $options = new \question_display_options();
        $options->context = $context;
        $options->flags = \question_display_options::VISIBLE;
        $options->marks = \question_display_options::VISIBLE;
        $options->rightanswer = \question_display_options::VISIBLE;
        $options->numpartscorrect = \question_display_options::VISIBLE;
        $options->manualcomment = \question_display_options::VISIBLE;
        $options->manualcommentlink = \question_display_options::VISIBLE;
        return $options;
    }

    private function attempt_display_options($context) {
        $options = new \question_display_options();
        $options->context = $context;
        $options->flags = \question_display_options::HIDDEN;
        $options->marks = \question_display_options::HIDDEN;
        $options->rightanswer = \question_display_options::VISIBLE;
        $options->numpartscorrect = \question_display_options::HIDDEN;
        $options->manualcomment = \question_display_options::HIDDEN;
        $options->manualcommentlink = \question_display_options::HIDDEN;
        return $options;
    }

}
