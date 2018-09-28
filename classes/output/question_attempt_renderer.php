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
use mod_capquiz\capquiz_question_list;
use mod_capquiz\capquiz_user;
use mod_capquiz\capquiz_urls;
use mod_capquiz\capquiz_question;
use mod_capquiz\capquiz_question_attempt;

defined('MOODLE_INTERNAL') || die();

/**
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_attempt_renderer {

    private $capquiz;
    private $renderer;

    public function __construct(capquiz $capquiz, renderer $renderer) {
        $this->capquiz = $capquiz;
        $this->renderer = $renderer;
        $this->render_question_head_html();
    }

    private function render_question_head_html() {
        $attempt = $this->capquiz->question_engine()->attempt_for_user($this->capquiz->user());
        $this->capquiz->question_usage()->render_question_head_html($attempt->question_slot());
    }

    public function render() : string {
        if (!$this->capquiz->is_published()) {
            return 'Nothing here yet';
        }
        $question_engine = $this->capquiz->question_engine();
        if ($attempt = $question_engine->attempt_for_user($this->capquiz->user())) {
            if ($attempt->is_answered()) {
                return $this->render_review($attempt);
            } else {
                if ($attempt->is_pending()) {
                    return $this->render_attempt($attempt, $this->attempt_display_options());
                }
            }
        } else {
            return 'You have finished this quiz!';
        }
    }

    private function render_attempt(capquiz_question_attempt $attempt, \question_display_options $displayoptions) : string {
        $user = $this->capquiz->user();
        $html = $this->render_progress($user);
        $html .= $this->render_question_attempt($attempt, $displayoptions);
        //$html .= $this->render_metainfo($user, $attempt);
        return $html;
    }

    private function render_review(capquiz_question_attempt $attempt) : string {
        $html = $this->render_attempt($attempt, $this->review_display_options());
        $html .= $this->render_review_next_button($attempt);
        return $html;
    }

    public function render_review_next_button(capquiz_question_attempt $attempt) : string {
        return basic_renderer::render_action_button(
            $this->renderer,
            capquiz_urls::response_reviewed_url($attempt),
            get_string('next', 'capquiz')
        );
    }

    private function render_progress(capquiz_user $user) : string {
        $questionlist = $this->capquiz->question_list();
        $percent = $questionlist->next_level_percent($user->rating());
        if ($percent >= 0) {
            $student = [
                'up' => ['percent' => $percent],
                'stars' => $this->user_star_progress($user, $questionlist)
            ];
        } else {
            $student = [
                'down' => ['percent' => -$percent],
                'stars' => $this->user_star_progress($user, $questionlist)
            ];
        }
        return $this->renderer->render_from_template('capquiz/student_progress', [
	        'progress' => ['student' => $student]
        ]);
    }

    public function render_question_attempt(capquiz_question_attempt $attempt, \question_display_options $displayoptions) : string {
        global $PAGE;
        $question_usage = $this->capquiz->question_usage();
        $PAGE->requires->js_module('core_question_engine');
        return $this->renderer->render_from_template('capquiz/student_question_attempt', [
            'attempt' => [
                'url' => capquiz_urls::response_submit_url($attempt)->out(false),
                'body' => $question_usage->render_question($attempt->question_slot(), $displayoptions, $attempt->question_id()),
                'slots' => ''
            ]
        ]);
    }

    public function render_metainfo(capquiz_user $user, capquiz_question_attempt $attempt) : string {
        $question = capquiz_question::load($attempt->question_id());
        if ($question == null) {
            return 'Question was not found.';
        }
        return $this->renderer->render_from_template('capquiz/student_question_metainfo', [
            'metainfo' => [
                'rating' => [
                    'student' => $user->rating(),
                    'question' => $question->rating()
                ],
                'question' => [
                    'capquiz_id' => $question->id(),
                    'moodle_id' => $question->question_id()
                ]
            ]
        ]);
    }

    private function user_star_progress(capquiz_user $user, capquiz_question_list $list) : array {
        $result = [];
        for ($star = 1; $star < $list->level_count() + 1; $star++) {
            $result[] = $user->highest_level() >= $star;
        }
        return $result;
    }

    private function review_display_options() : \question_display_options {
        $options = new \question_display_options();
        $options->context = $this->capquiz->context();
        $options->readonly = true;
        $options->flags = \question_display_options::VISIBLE;
        $options->marks = \question_display_options::VISIBLE;
        $options->rightanswer = \question_display_options::VISIBLE;
        $options->numpartscorrect = \question_display_options::VISIBLE;
        $options->manualcomment = \question_display_options::HIDDEN;
        //$options->manualcommentlink = 'insert the link to solve issue #44';
        return $options;
    }

    private function attempt_display_options() : \question_display_options {
        $options = new \question_display_options();
        $options->context = $this->capquiz->context();
        $options->flags = \question_display_options::HIDDEN;
        $options->marks = \question_display_options::HIDDEN;
        $options->rightanswer = \question_display_options::HIDDEN;
        $options->numpartscorrect = \question_display_options::HIDDEN;
        $options->manualcomment = \question_display_options::HIDDEN;
        //$options->manualcommentlink = 'insert the link to solve issue #44';
        return $options;
    }

}
