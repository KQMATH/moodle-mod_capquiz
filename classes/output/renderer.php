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
require_once($CFG->dirroot . '/mod/capquiz/classes/output/classlist_renderer.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/question_list_renderer.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/question_bank_renderer.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/question_attempt_renderer.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/unauthorized_view_renderer.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/capquiz_configuration_renderer.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/question_list_creator_renderer.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/instructor_dashboard_renderer.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/matchmaking_configuration_renderer.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/badge_rating_configuration_renderer.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/matchmaking_strategy_selection_renderer.php');

defined('MOODLE_INTERNAL') || die();

/**
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {

    public function output_renderer() {
        return $this->output;
    }

    private function tab(string $name, string $title, \moodle_url $link) {
        return new \tabobject($name, $link, $title);
    }

    private function tabs(string $activetab) {
        $tabs = [
            $this->tab('view_dashboard', get_string('dashboard', 'capquiz'), capquiz_urls::view_url()),
            $this->tab('view_matchmaking', get_string('matchmaking', 'capquiz'), capquiz_urls::view_matchmaking_configuration_url()),
            $this->tab('view_rating_system', get_string('rating_system', 'capquiz'), capquiz_urls::view_rating_system_configuration_url()),
            $this->tab('view_questions', get_string('questions', 'capquiz'), capquiz_urls::view_question_list_url()),
            $this->tab('view_badges', get_string('badges', 'capquiz'), capquiz_urls::view_badge_configuration_url()),
            $this->tab('view_capquiz', get_string('pluginname', 'capquiz'), capquiz_urls::view_configuration_url()),
            $this->tab('view_classlist', get_string('classlist', 'capquiz'), capquiz_urls::view_classlist_url())
        ];
        return print_tabs([$tabs], $activetab, null, null, true);
    }

    public function display_tabbed_view($renderer, string $activetab) {
        $html = $this->output->header();
        $html .= $this->tabs($activetab);
        $html .= $renderer->render();
        $html .= $this->output->footer();
        echo $html;
    }

    public function display_tabbed_views(array $renderers, string $activetab) {
        $html = $this->output->header();
        $html .= $this->tabs($activetab);
        foreach ($renderers as $renderer) {
            $html .= $renderer->render();
        }
        $html .= $this->output->footer();
        echo $html;
    }

    public function display_view($renderer) {
        $html = $this->output->header();
        $html .= $renderer->render();
        $html .= $this->output->footer();
        echo $html;
    }

    public function display_views(array $renderers) {
        $html = $this->output->header();
        foreach ($renderers as $renderer) {
            $html .= $renderer->render();
        }
        $html .= $this->output->footer();
        echo $html;
    }

    public function display_question_attempt_view(capquiz $capquiz) {
        $this->display_view(new question_attempt_renderer($capquiz, $this));
    }

    public function display_instructor_dashboard(capquiz $capquiz) {
        $this->display_tabbed_view(new instructor_dashboard_renderer($capquiz, $this), 'view_dashboard');
    }

    public function display_question_list_create_view(capquiz $capquiz) {
        $this->display_view(new question_list_creator_renderer($capquiz, $this));
    }

    public function display_choose_question_list_view() {
        $this->display_view(new question_list_selection_renderer($this));
    }

    public function display_unauthorized_view() {
        $this->display_view(new unauthorized_view_renderer($this));
    }

    public function display_question_list_view(capquiz $capquiz) {
        $render = new class($capquiz, $this) {
            private $capquiz;
            private $renderer;

            public function __construct(capquiz $capquiz, renderer $renderer) {
                $this->capquiz = $capquiz;
                $this->renderer = $renderer;
            }

            public function render() {
                $html = '<div class="capquiz-flex">';
                $r1 = new question_list_renderer($this->capquiz, $this->renderer);
                $r2 = new question_bank_renderer($this->capquiz, $this->renderer);
                $html .= '<div class="capquiz-flex-item">' . $r1->render() . '</div>';
                $html .= '<div class="capquiz-flex-item">' . $r2->render() . '</div >';
                return $html . '</div>';
            }
        };
        $this->display_tabbed_view($render, 'view_questions');
    }

    public function display_matchmaking_configuration(capquiz $capquiz) {
        $this->display_tabbed_views([
            new matchmaking_strategy_selection_renderer($capquiz, $this),
            new matchmaking_configuration_renderer($capquiz, $this)
        ], 'view_matchmaking');
    }

    public function display_rating_system_configuration(capquiz $capquiz) {
        $this->display_tabbed_views([
            new rating_system_selection_renderer($capquiz, $this),
            new rating_system_configuration_renderer($capquiz, $this)
        ], 'view_rating_system');
    }

    public function display_leaderboard(capquiz $capquiz) {
        $this->display_tabbed_view(new classlist_renderer($capquiz, $this), 'view_classlist');
    }

    public function display_capquiz_configuration(capquiz $capquiz) {
        $this->display_tabbed_view(new capquiz_configuration_renderer($capquiz, $this), 'view_capquiz');
    }

    public function display_badge_configuration(capquiz $capquiz) {
        $this->display_tabbed_view(new badge_rating_configuration_renderer($capquiz, $this), 'view_badges');
    }

}
