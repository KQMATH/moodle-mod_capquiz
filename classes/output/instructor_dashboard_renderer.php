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
use mod_capquiz\capquiz_user;

defined('MOODLE_INTERNAL') || die();

require_once('../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');

/**
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instructor_dashboard_renderer {

    private $capquiz;
    private $renderer;

    public function __construct(capquiz $capquiz, renderer $renderer) {
        $this->capquiz = $capquiz;
        $this->renderer = $renderer;
    }

    public function render() {
        $html = $this->render_summary();
        $html .= $this->render_publish();
        $html .= $this->render_template();
        return $html;
    }

    private function render_summary() {
        $question_list = $this->capquiz->question_list();
        if (!$question_list) {
            return 'question list error';
        }
        return $this->renderer->render_from_template('capquiz/instructor_dashboard_summary', [
            'published_status' => $this->capquiz->is_published() ? get_string('published', 'capquiz') : get_string('not_published', 'capquiz'),
            'question_list_title' => $question_list ? $question_list->title() : get_string('no_question_list_assigned', 'capquiz'),
            'question_count' => $question_list ? $question_list->question_count() : get_string('no_questions', 'capquiz'),
            'enrolled_student_count' => capquiz_user::user_count($this->capquiz)
        ]);
    }

    private function render_publish() {
        $is_published = $this->capquiz->is_published();
        $can_publish = $this->capquiz->can_publish();
        $question_list = $this->capquiz->question_list();
        if (!$question_list) {
            return 'question list error';
        }
        $message = null;
        if (!$can_publish) {
            if ($question_list->question_count() === 0) {
                $message = get_string('publish_no_questions_in_list', 'capquiz');
            } else if ($is_published) {
                $message = get_string('publish_already_published', 'capquiz');
            }
        }
        return $this->renderer->render_from_template('capquiz/instructor_dashboard_publish', [
            'publish' => $this->publish_button(),
            'message' => $message ? $message : false
        ]);
    }

    private function render_template() {
        $question_list = $this->capquiz->question_list();
        if (!$question_list) {
            return 'question list error';
        }
        $message = null;
        if (!$question_list->can_create_template()) {
            $message = get_string('template_no_questions_in_list', 'capquiz');
        }
        return $this->renderer->render_from_template('capquiz/instructor_dashboard_template', [
            'crate_template' => $this->create_template_button(),
            'message' => $message ? $message : false
        ]);
    }

    private function publish_button() {
        return [
            'primary' => true,
            'method' => 'post',
            'url' => capquiz_urls::question_list_publish_url($this->capquiz->question_list())->out(false),
            'label' => get_string('publish', 'capquiz'),
            'disabled' => !$this->capquiz->can_publish() ? true : false
        ];
    }

    private function create_template_button() {
        return [
            'primary' => true,
            'method' => 'post',
            'url' => capquiz_urls::question_list_create_template_url($this->capquiz->question_list())->out(false),
            'label' => get_string('create_template', 'capquiz'),
            'disabled' => !$this->capquiz->question_list()->can_create_template() ? true : false
        ];
    }
}
