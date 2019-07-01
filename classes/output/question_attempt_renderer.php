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

    /** @var capquiz $capquiz */
    private $capquiz;

    /** @var renderer $renderer */
    private $renderer;

    public function __construct(capquiz $capquiz, renderer $renderer) {
        $this->capquiz = $capquiz;
        $this->renderer = $renderer;
        $this->render_question_head_html();
    }

    private function render_question_head_html() {
        $qengine = $this->capquiz->question_engine();
        if ($qengine === null) {
            return;
        }
        $attempt = $qengine->attempt_for_user($this->capquiz->user());
        if ($attempt !== null) {
            $this->capquiz->question_usage()->render_question_head_html($attempt->question_slot());
        }
    }

    public function render() : string {
        global $PAGE;
        if (!$this->capquiz->is_published()) {
            return get_string('nothing_here_yet', 'capquiz');
        }
        $PAGE->requires->js_call_amd('mod_capquiz/attempt', 'initialize', []);
        $qengine = $this->capquiz->question_engine();
        $attempt = $qengine->attempt_for_user($this->capquiz->user());
        if ($attempt) {
            if ($attempt->is_answered()) {
                return $this->render_review($attempt);
            } else if ($attempt->is_pending()) {
                return $this->render_attempt($attempt, $this->attempt_display_options());
            }
        }
        return 'You have finished this quiz!';
    }

    private function render_attempt(capquiz_question_attempt $attempt, \question_display_options $options) : string {
        $user = $this->capquiz->user();
        $html = $this->render_progress($user);
        $html .= $this->render_question_attempt($attempt, $options);
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
            get_string('next', 'capquiz'),
            'post',
            [],
            'capquiz_review_next'
        );
    }

    private function render_progress(capquiz_user $user) : string {
        $qlist = $this->capquiz->question_list();
        $percent = $qlist->next_level_percent($this->capquiz, $user->rating());
        list($stars, $blankstars, $nostars) = $this->user_star_progress($user, $qlist);
        $student = [
            'up' => $percent >= 0 ? ['percent' => $percent] : false,
            'down' => $percent < 0 ? ['percent' => -$percent] : false,
            'stars' => $stars,
            'blankstars' => $blankstars,
            'nostars' => $nostars
        ];
        return $this->renderer->render_from_template('capquiz/student_progress', [
            'progress' => ['student' => $student]
        ]);
    }

    public function render_question_attempt(capquiz_question_attempt $attempt, \question_display_options $options) : string {
        global $PAGE;
        $quba = $this->capquiz->question_usage();
        $PAGE->requires->js_module('core_question_engine');
        return $this->renderer->render_from_template('capquiz/student_question_attempt', [
            'attempt' => [
                'url' => capquiz_urls::response_submit_url($attempt)->out(false),
                'body' => $quba->render_question($attempt->question_slot(), $options, $attempt->question_id()),
                'slots' => '',
                'comment' => $attempt->student_comment()
            ],
            'gradingdone' => $this->capquiz->is_grading_completed(),
            'finalgrade' => $this->capquiz->user()->highest_stars_graded(),
            'gradingpass' => $this->capquiz->user()->highest_stars_graded() >= $this->capquiz->stars_to_pass()
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

    private function user_star_progress(capquiz_user $user, capquiz_question_list $qlist) : array {
        $stars = [];
        $blankstars = [];
        $nostars = [];
        for ($star = 1; $star <= $qlist->max_stars(); $star++) {
            if ($user->highest_stars_achieved() >= $star) {
                if ($user->rating() >= $qlist->star_rating($star)) {
                    $stars[] = true;
                } else {
                    $blankstars[] = true;
                }
            } else {
                $nostars[] = true;
            }
        }
        return [$stars, $blankstars, $nostars];
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
        return $options;
    }

}
