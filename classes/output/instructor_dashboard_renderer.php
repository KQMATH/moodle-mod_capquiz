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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/editlib.php');

/**
 * Class  instructor_dashboard_renderer
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instructor_dashboard_renderer {

    /** @var capquiz $capquiz */
    private $capquiz;

    /** @var renderer $renderer */
    private $renderer;

    /**
     * instructor_dashboard_renderer constructor.
     * @param capquiz $capquiz
     * @param renderer $renderer
     */
    public function __construct(capquiz $capquiz, renderer $renderer) {
        $this->capquiz = $capquiz;
        $this->renderer = $renderer;
    }

    /**
     * Render instructor dashboard
     *
     * @return string
     */
    public function render() {
        $html = $this->render_summary();
        $html .= $this->render_publish();
        $html .= $this->render_template();
        return $html;
    }

    /**
     * Render the instructor dashboard summary
     *
     * @return bool|string
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    private function render_summary() {
        $qlist = $this->capquiz->question_list();
        if (!$qlist) {
            return 'question list error';
        }
        $strpublished = get_string('published', 'capquiz');
        $strnotpublished = get_string('not_published', 'capquiz');
        $strnoqlistassigned = get_string('no_question_list_assigned', 'capquiz');
        $strnoquestions = get_string('no_questions', 'capquiz');
        return $this->renderer->render_from_template('capquiz/instructor_dashboard_summary', [
            'published_status' => $this->capquiz->is_published() ? $strpublished : $strnotpublished,
            'question_list_title' => $qlist ? $qlist->title() : $strnoqlistassigned,
            'question_count' => $qlist ? $qlist->question_count() : $strnoquestions,
            'enrolled_student_count' => capquiz_user::user_count($this->capquiz->id())
        ]);
    }

    /**
     * Renders publish button
     *
     * @return bool|string
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    private function render_publish() {
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
            'message' => $message ? $message : false
        ]);
    }

    /**
     * Renders template
     *
     * @return bool|string
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    private function render_template() {
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
            'message' => $message ? $message : false
        ]);
    }

    /**
     * Creates publish button
     *
     * @return array
     * @throws \coding_exception
     */
    private function publish_button() {
        return [
            'primary' => true,
            'method' => 'post',
            'url' => capquiz_urls::question_list_publish_url($this->capquiz->question_list())->out(false),
            'label' => get_string('publish', 'capquiz'),
            'disabled' => !$this->capquiz->can_publish() ? true : false
        ];
    }

    /**
     * Creates create template button
     *
     * @return array
     * @throws \coding_exception
     */
    private function create_template_button() {
        return [
            'primary' => true,
            'method' => 'post',
            'url' => capquiz_urls::question_list_create_template_url($this->capquiz->question_list())->out(false),
            'label' => get_string('create_template', 'capquiz'),
            'disabled' => !$this->capquiz->question_list()->has_questions() ? true : false
        ];
    }
}
