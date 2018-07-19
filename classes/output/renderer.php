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

use mod_capquiz\bank\question_bank_view;

require_once($CFG->dirroot . '/mod/capquiz/classes/output/basic_renderer.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/leaderboard_renderer.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/configuration_renderer.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/question_list_renderer.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/question_bank_renderer.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/question_attempt_renderer.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/unauthorized_view_renderer.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/create_question_list_renderer.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/instructor_dashboard_renderer.php');

defined('MOODLE_INTERNAL') || die();

class renderer extends \plugin_renderer_base {

    public function output_renderer() {
        return $this->output;
    }

    public function display_view($view) {
        echo $this->output->header();
        echo $view->render();
        echo $this->output->footer();
    }

    public function display_views(array $views) {
        echo $this->output->header();
        foreach ($views as $view)
            echo $view->render();
        echo $this->output->footer();
    }

    public function display_question_attempt_view(\mod_capquiz\capquiz $capquiz) {
        $this->display_view(new question_attempt_renderer($capquiz, $this));
    }

    public function display_instructor_dashboard(\mod_capquiz\capquiz $capquiz) {
        $this->display_view(new instructor_dashboard_renderer($capquiz, $this));
    }

    public function display_question_list_create_view(\mod_capquiz\capquiz $capquiz) {
        $this->display_view(new create_question_list_renderer($capquiz, $this));
    }

    public function display_unauthorized_view(\mod_capquiz\capquiz $capquiz) {
        $this->display_view(new unauthorized_view_renderer($capquiz, $this));
    }

    public function display_question_list_view(\mod_capquiz\capquiz $capquiz) {
        $this->display_views([new question_list_renderer($capquiz, $this), new question_bank_renderer($capquiz, $this)]);
    }

    public function display_leaderboard(\mod_capquiz\capquiz $capquiz) {
        $this->display_view(new leaderboard_renderer($capquiz, $this));
    }

    public function display_configuration(\mod_capquiz\capquiz $capquiz) {
        $this->display_view(new configuration_renderer($capquiz, $this));
    }
}
