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

/**
 * This file defines a class used to render a question attempt
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz\output;

use core\context\module;
use mod_capquiz\capquiz;
use mod_capquiz\capquiz_question_list;
use mod_capquiz\capquiz_user;
use mod_capquiz\capquiz_urls;
use mod_capquiz\capquiz_question;
use mod_capquiz\capquiz_question_attempt;
use moodle_page;
use question_display_options;
use renderer_base;

/**
 * Class question_attempt_renderer
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_attempt_renderer {

    /** @var capquiz $capquiz */
    private capquiz $capquiz;

    /** @var renderer_base $renderer */
    private renderer_base $renderer;

    /** @var moodle_page $page */
    private moodle_page $page;

    /**
     * Constructor.
     *
     * @param capquiz $capquiz
     * @param renderer_base $renderer
     */
    public function __construct(capquiz $capquiz, renderer_base $renderer) {
        $this->capquiz = $capquiz;
        $this->renderer = $renderer;
        $this->render_question_head_html();
        $this->page = $capquiz->get_page();
    }

    /**
     * Renders the question head
     */
    private function render_question_head_html(): void {
        $user = $this->capquiz->user();
        $qengine = $this->capquiz->question_engine($user);
        if ($qengine === null) {
            return;
        }
        $attempt = $qengine->attempt_for_user($user);
        if ($attempt !== null) {
            $user->question_usage()->render_question_head_html($attempt->question_slot());
        }
    }

    /**
     * Renders the question attempt view
     */
    public function render(): string {
        if (!$this->capquiz->is_published()) {
            return get_string('nothing_here_yet', 'capquiz');
        }
        $this->page->requires->js_call_amd('mod_capquiz/attempt', 'initialize', []);
        $user = $this->capquiz->user();
        $qengine = $this->capquiz->question_engine($user);
        $attempt = $qengine->attempt_for_user($user);
        if ($attempt) {
            if ($attempt->is_answered()) {
                return $this->render_review($attempt);
            }
            if ($attempt->is_pending()) {
                return $this->render_attempt($attempt, self::attempt_display_options($this->capquiz->context()));
            }
        }
        return get_string('you_finished_capquiz', 'capquiz');
    }

    /**
     * Render the attempt
     *
     * @param capquiz_question_attempt $attempt
     * @param question_display_options $options
     */
    private function render_attempt(capquiz_question_attempt $attempt, question_display_options $options): string {
        $user = $this->capquiz->user();
        $html = $this->render_progress($user);
        $html .= $this->render_question_attempt($attempt, $options);
        return $html;
    }

    /**
     * Render the attempt review
     *
     * @param capquiz_question_attempt $attempt
     */
    private function render_review(capquiz_question_attempt $attempt): string {
        $html = $this->render_attempt($attempt, self::review_display_options($this->capquiz->context()));
        $html .= $this->render_review_next_button($attempt);
        return $html;
    }

    /**
     * Render the review next button
     *
     * @param capquiz_question_attempt $attempt
     */
    public function render_review_next_button(capquiz_question_attempt $attempt): string {
        $url = capquiz_urls::response_reviewed_url($attempt);
        $label = get_string('next', 'capquiz');
        return basic_renderer::render_action_button($this->renderer, $url, $label, id: 'capquiz_review_next');
    }

    /**
     * Render a users progress
     *
     * @param capquiz_user $user
     */
    private function render_progress(capquiz_user $user): string {
        $qlist = $this->capquiz->question_list();
        $percent = $qlist->next_level_percent($this->capquiz, $user->rating());
        list($stars, $blankstars, $nostars) = $this->user_star_progress($user, $qlist);
        $student = [
            'up' => $percent >= 0 ? ['percent' => $percent] : false,
            'down' => $percent < 0 ? ['percent' => -$percent] : false,
            'stars' => $stars,
            'blankstars' => $blankstars,
            'nostars' => $nostars,
        ];
        return $this->renderer->render_from_template('capquiz/student_progress', [
            'progress' => ['student' => $student],
        ]);
    }

    /**
     * Render the question attempt
     *
     * @param capquiz_question_attempt $attempt
     * @param question_display_options $options
     */
    public function render_question_attempt(capquiz_question_attempt $attempt, question_display_options $options): string {
        $user = $this->capquiz->user();
        $quba = $user->question_usage();
        $this->page->requires->js_module('core_question_engine');
        return $this->renderer->render_from_template('capquiz/student_question_attempt', [
            'attempt' => [
                'url' => capquiz_urls::response_submit_url($attempt)->out(false),
                'body' => $quba->render_question($attempt->question_slot(), $options, $attempt->question_id()),
                'slots' => '',
            ],
            'gradingdone' => $this->capquiz->is_grading_completed(),
            'finalgrade' => $user->highest_stars_graded(),
            'gradingpass' => $user->highest_stars_graded() >= $this->capquiz->stars_to_pass(),
            'duedate' => userdate($this->capquiz->time_due(), get_string('strftimedatetime', 'langconfig')),
        ]);
    }

    /**
     * Render question attempts metainfo
     *
     * @param capquiz_user $user
     * @param capquiz_question_attempt $attempt
     */
    public function render_metainfo(capquiz_user $user, capquiz_question_attempt $attempt): string {
        $question = capquiz_question::load($attempt->question_id());
        if ($question == null) {
            return 'Question was not found.';
        }
        return $this->renderer->render_from_template('capquiz/student_question_metainfo', [
            'metainfo' => [
                'rating' => [
                    'student' => $user->rating(),
                    'question' => $question->rating(),
                ],
                'question' => [
                    'capquiz_id' => $question->id(),
                    'moodle_id' => $question->question_id(),
                ],
            ],
        ]);
    }

    /**
     * Checks a users star progress
     *
     * @param capquiz_user $user
     * @param capquiz_question_list $qlist
     * @return array[]
     */
    private function user_star_progress(capquiz_user $user, capquiz_question_list $qlist): array {
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

    /**
     * Returns the display options for the attempt review
     *
     * @param module $context
     */
    public static function review_display_options(module $context): question_display_options {
        $options = new question_display_options();
        $options->context = $context;
        $options->readonly = true;
        $options->flags = question_display_options::VISIBLE;
        $options->marks = question_display_options::VISIBLE;
        $options->rightanswer = question_display_options::VISIBLE;
        $options->numpartscorrect = question_display_options::VISIBLE;
        $options->manualcomment = question_display_options::HIDDEN;
        return $options;
    }

    /**
     * Returns the display options for the attempt
     *
     * @param module $context
     */
    public static function attempt_display_options(module $context): question_display_options {
        $options = new question_display_options();
        $options->context = $context;
        $options->flags = question_display_options::HIDDEN;
        $options->marks = question_display_options::HIDDEN;
        $options->rightanswer = question_display_options::HIDDEN;
        $options->numpartscorrect = question_display_options::HIDDEN;
        $options->manualcomment = question_display_options::HIDDEN;
        return $options;
    }

}
