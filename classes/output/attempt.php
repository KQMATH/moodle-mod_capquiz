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

use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use mod_capquiz\capquiz;
use mod_capquiz\capquiz_user;
use mod_capquiz\capquiz_attempt;
use mod_capquiz\local\helpers\questions;
use mod_capquiz\local\helpers\stars;
use question_engine;

/**
 * Render question attempt.
 *
 * @package     mod_capquiz
 * @author      Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright   2024 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt implements renderable, templatable {
    /**
     * Constructor.
     *
     * @param capquiz_attempt $attempt
     * @param capquiz_user $user
     * @param capquiz $capquiz
     */
    public function __construct(
        /** @var capquiz_attempt Attempt */
        private readonly capquiz_attempt $attempt,
        /** @var capquiz_user User */
        private readonly capquiz_user $user,
        /** @var capquiz CAPQuiz */
        private readonly capquiz $capquiz,
    ) {
    }

    /**
     * Render the question attempt.
     *
     * @param renderer_base $output
     * @return bool|string
     */
    public function render(renderer_base $output): bool|string {
        return $output->render_from_template('capquiz/attempt', $this->export_for_template($output));
    }

    /**
     * Export parameters for template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $output->get_page()->requires->js_module('core_question_engine');
        $qubaslot = $this->attempt->get('slot');
        $quba = $this->user->get_question_usage();
        // This adds the necessary JS and CSS for the question type.
        $quba->render_question_head_html($qubaslot);
        question_engine::initialise_js();
        $cm = $this->capquiz->get_cm();
        return [
            'header' => $output->render(new attempt_header($this->user)),
            'attempt' => [
                'url' => (new \core\url('/mod/capquiz/attempt.php', ['id' => $cm->id, 'action' => 'submit']))->out(false),
                'body' => $quba->render_question($qubaslot, questions::get_question_display_options($this->capquiz)),
                'slots' => $this->attempt->get('slot'),
            ],
            'gradingdone' => $this->capquiz->is_past_due_time(),
            'finalgrade' => $this->user->get('starsgraded'),
            'gradingpass' => stars::is_user_passing($this->user, $this->capquiz),
            'duedate' => userdate($this->capquiz->get('timedue'), get_string('strftimedatetime', 'langconfig')),
            'reviewbutton' => $this->attempt->get('answered') ? [
                'type' => 'primary',
                'method' => 'post',
                'url' => (new \core\url('/mod/capquiz/attempt.php', ['id' => $cm->id, 'action' => 'review']))->out(false),
                'label' => get_string('next'),
            ] : [],
        ];
    }
}
