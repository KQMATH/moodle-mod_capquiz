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

    /**
     * @param string $name
     * @param \moodle_url $link
     * @return \tabobject
     * @throws \coding_exception
     */
    private function tab(string $name, \moodle_url $link) {
        $text = get_string("tab_$name", 'capquiz');
        return new \tabobject($name, $link, $text);
    }
    /**
     * @param string $activetab
     * @return string html
     * @throws \coding_exception
     */
    private function tabs(string $activetab) {
        $tabs = [
            $this->tab('view_dashboard', capquiz_urls::view_url()),
            $this->tab('view_question_list', capquiz_urls::view_question_list_url()),
            $this->tab('view_leaderboard', capquiz_urls::view_leaderboard_url()),
            $this->tab('view_configuration', capquiz_urls::view_configuration_url())
        ];
        return print_tabs([$tabs], $activetab, null, null, true);
    }

    public function display_tabbed_view($view, string $activetab) {
        echo $this->output->header();
        echo $this->tabs($activetab);
        echo $view->render();
        echo $this->output->footer();
    }

    public function display_tabbed_views(array $views, string $activetab) {
        echo $this->output->header();
        echo $this->tabs($activetab);
        foreach ($views as $view)
            echo $view->render();
        echo $this->output->footer();
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

    public function display_question_attempt_view(capquiz $capquiz) {
        $this->display_view(new question_attempt_renderer($capquiz, $this));
    }

    public function display_instructor_dashboard(capquiz $capquiz) {
        $this->display_tabbed_view(new instructor_dashboard_renderer($capquiz, $this), 'view_dashboard');
    }

    public function display_question_list_create_view(capquiz $capquiz) {
        $this->display_view(new create_question_list_renderer($capquiz, $this));
    }

    public function display_unauthorized_view() {
        $this->display_view(new unauthorized_view_renderer($this));
    }

    public function display_question_list_view(capquiz $capquiz) {
        $this->display_tabbed_views([
            new question_list_renderer($capquiz, $this),
            new question_bank_renderer($capquiz, $this)
        ], 'view_question_list');
    }

    public function display_leaderboard(capquiz $capquiz) {
        $this->display_tabbed_view(new leaderboard_renderer($capquiz, $this), 'view_leaderboard');
    }

    public function display_configuration(capquiz $capquiz) {
        $this->display_tabbed_view(new configuration_renderer($capquiz, $this), 'view_configuration');
    }
}
