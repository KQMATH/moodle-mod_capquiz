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

defined('MOODLE_INTERNAL') || die();

class student_view {

    private $capquiz;

    public function __construct(capquiz $capquiz) {
        $this->capquiz = $capquiz;
    }

    public function render(renderer $renderer) {
        global $PAGE;
        $capquiz = $this->capquiz;
        $user = $capquiz->user();
        $quba = $capquiz->question_usage();
        $context = $capquiz->context();
        $qengine = $capquiz->question_engine();
        if (!$qengine) {
            return 'No question engine.';
        }
        $attempt = $qengine->attempt_for_user($user);

        $question = [
            'attempt' => [
                'url' => '',
                'slots' => '',
                'body' => ''
            ],
            'summary' => [
                'response' => '',
                'feedback' => '',
                'next' => [
                    'primary' => true,
                    'method' => 'post',
                    'url' => '',
                    'label' => get_string('next', 'capquiz')
                ]
            ]
        ];

        if ($attempt && $attempt->is_pending()) {
            if ($attempt->is_answered()) {
                $displayoptions = $this->attempt_display_options($context);
                $moodle_question = $quba->get_question($attempt->question_slot());
                $questionrenderer = $moodle_question->get_renderer($PAGE);
                $questionattempt = $quba->get_question_attempt($attempt->question_slot());
                $question['summary']['response'] = $quba->get_response_summary($attempt->question_slot());
                $question['summary']['feedback'] = $questionrenderer->feedback($questionattempt, $displayoptions);
            } else {
                $displayoptions = $this->summary_display_options($context);
                $PAGE->requires->js_module('core_question_engine');
                $question['attempt'] = [
                    'url' => capquiz_urls::create_response_submit_url($capquiz, $attempt),
                    'body' => $quba->render_question($attempt->question_slot(), $displayoptions, $attempt->question_id()),
                    'slots' => ''
                ];
            }
        }

        return $renderer->render_from_template('capquiz/student_quiz', [
            'published' => $capquiz->is_published(),
            'completed' => $qengine->user_is_completed($user),
            'question' => $question
        ]);
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
