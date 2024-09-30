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
 * This file defines a class used to render the instructor dashboard
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz\output;

use mod_capquiz\capquiz;
use mod_capquiz\capquiz_urls;
use mod_capquiz\capquiz_user;
use renderer_base;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/editlib.php');

/**
 * Class instructor_dashboard_renderer
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instructor_dashboard_renderer {

    /** @var capquiz $capquiz */
    private capquiz $capquiz;

    /** @var renderer_base $renderer */
    private renderer_base $renderer;

    /**
     * Constructor.
     *
     * @param capquiz $capquiz
     * @param renderer_base $renderer
     */
    public function __construct(capquiz $capquiz, renderer_base $renderer) {
        $this->capquiz = $capquiz;
        $this->renderer = $renderer;
    }

    /**
     * Render instructor dashboard
     */
    public function render(): string {
        $html = $this->render_summary();
        $html .= $this->render_publish();
        $html .= $this->render_template();
        return $html;
    }

    /**
     * Render the instructor dashboard summary
     */
    private function render_summary(): bool|string {
        $qlist = $this->capquiz->question_list();
        if (!$qlist) {
            return 'question list error';
        }
        return $this->renderer->render_from_template('capquiz/instructor_dashboard_summary', [
            'published_status' => get_string($this->capquiz->is_published() ? 'published' : 'not_published', 'capquiz'),
            'question_list_title' => $qlist->title(),
            'question_count' => $qlist->question_count(),
            'enrolled_student_count' => capquiz_user::user_count($this->capquiz->id()),
        ]);
    }

    /**
     * Renders publish button
     */
    private function render_publish(): bool|string {
        $published = $this->capquiz->is_published();
        $canpublish = $this->capquiz->can_publish();
        $qlist = $this->capquiz->question_list();
        if (!$qlist) {
            return 'question list error';
        }
        $message = null;
        if (!$canpublish) {
            if ($qlist->question_count() === 0) {
                $message = get_string('publish_no_questions_in_list', 'capquiz');
            } else if ($published) {
                $message = get_string('publish_already_published', 'capquiz');
            }
        }
        return $this->renderer->render_from_template('capquiz/instructor_dashboard_publish', [
            'publish' => $this->publish_button(),
            'message' => $message ?: false,
        ]);
    }

    /**
     * Renders template
     */
    private function render_template(): bool|string {
        $qlist = $this->capquiz->question_list();
        if (!$qlist) {
            return 'question list error';
        }
        $message = null;
        if (!$qlist->has_questions()) {
            $message = get_string('template_no_questions_in_list', 'capquiz');
        }
        return $this->renderer->render_from_template('capquiz/instructor_dashboard_template', [
            'create_template' => $this->create_template_button(),
            'message' => $message ?: false,
        ]);
    }

    /**
     * Creates publish button
     */
    private function publish_button(): array {
        return [
            'type' => 'primary',
            'method' => 'post',
            'url' => capquiz_urls::question_list_publish_url($this->capquiz->question_list())->out(false),
            'label' => get_string('publish', 'capquiz'),
            'disabled' => !$this->capquiz->can_publish(),
        ];
    }

    /**
     * Creates create template button
     */
    private function create_template_button(): array {
        return [
            'type' => 'primary',
            'method' => 'post',
            'url' => capquiz_urls::question_list_create_template_url($this->capquiz->question_list())->out(false),
            'label' => get_string('create_template', 'capquiz'),
            'disabled' => !$this->capquiz->question_list()->has_questions(),
        ];
    }
}
