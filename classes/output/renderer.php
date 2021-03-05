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
 * This file defines a class used as a superclass to different renderers
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz\output;

use mod_capquiz\capquiz;
use mod_capquiz\capquiz_urls;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/capquiz/classes/output/basic_renderer.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/classlist_renderer.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/question_list_renderer.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/question_bank_renderer.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/question_attempt_renderer.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/unauthorized_view_renderer.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/question_list_creator_renderer.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/instructor_dashboard_renderer.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/matchmaking_configuration_renderer.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/grading_configuration_renderer.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/matchmaking_strategy_selection_renderer.php');

/**
 * Main plugin renderer for capquiz
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {

    /**
     * Returns a reference to the current renderer
     *
     * @return \core_renderer|\renderer_base
     */
    public function output_renderer() {
        return $this->output;
    }

    /**
     * Creates a tab
     *
     * @param string $name Name of the tab
     * @param string $title Title of the tab
     * @param \moodle_url $link Link
     * @return \tabobject
     * @throws \coding_exception
     */
    private function tab(string $name, string $title, \moodle_url $link) {
        $title = get_string($title, 'capquiz');
        return new \tabobject($name, $link, $title);
    }

    /**
     * Creates all tabs
     *
     * @param string $activetab The currently active cab
     * @return bool|string
     * @throws \coding_exception
     */
    private function tabs(string $activetab) {
        $tabs = [
            $this->tab('view_dashboard', 'dashboard', capquiz_urls::view_url()),
            $this->tab('view_rating_system', 'rating_system', capquiz_urls::view_rating_system_url()),
            $this->tab('view_questions', 'questions', capquiz_urls::view_question_list_url()),
            $this->tab('view_grading', 'grading', capquiz_urls::view_grading_url()),
            $this->tab('view_classlist', 'classlist', capquiz_urls::view_classlist_url()),
            $this->tab('view_import', 'other_question_lists', capquiz_urls::view_import_url()),
            $this->tab('view_report', 'reports', capquiz_urls::view_report_url()),
        ];
        return print_tabs([$tabs], $activetab, null, null, true);
    }

    /**
     * Display a tabbed view
     *
     * @param renderer $renderer The renderer to render the tab
     * @param string $activetab The currently active tab
     * @throws \coding_exception
     */
    public function display_tabbed_view($renderer, string $activetab) {
        $html = $this->output->header();
        $html .= $this->tabs($activetab);
        $html .= $renderer->render();
        $html .= $this->output->footer();
        echo $html;
    }

    /**
     * Display multiple tabbed views
     *
     * @param renderer[] $renderers The renderers to render the tabs
     * @param string $activetab The currently active tab
     * @throws \coding_exception
     */
    public function display_tabbed_views(array $renderers, string $activetab) {
        $html = $this->output->header();
        $html .= $this->tabs($activetab);
        foreach ($renderers as $renderer) {
            $html .= $renderer->render();
        }
        $html .= $this->output->footer();
        echo $html;
    }

    /**
     * Display view
     *
     * @param renderer $renderer renderer to render the view
     * @throws \coding_exception
     */
    public function display_view($renderer) {
        $html = $this->output->header();
        $html .= $renderer->render();
        $html .= $this->output->footer();
        echo $html;
    }

    /**
     * Display multiple views
     *
     * @param renderer[] $renderers renderers to render the views
     * @throws \coding_exception
     */
    public function display_views(array $renderers) {
        $html = $this->output->header();
        foreach ($renderers as $renderer) {
            $html .= $renderer->render();
        }
        $html .= $this->output->footer();
        echo $html;
    }

    /**
     * Display the question attempt view
     *
     * @param capquiz $capquiz The current capquiz
     * @throws \coding_exception
     */
    public function display_question_attempt_view(capquiz $capquiz) {
        $this->display_view(new question_attempt_renderer($capquiz, $this));
    }

    /**
     * Display the instructor dashboard
     *
     * @param capquiz $capquiz The current capquiz
     * @throws \coding_exception
     */
    public function display_instructor_dashboard(capquiz $capquiz) {
        $this->display_tabbed_view(new instructor_dashboard_renderer($capquiz, $this), 'view_dashboard');
    }

    /**
     * Display the question list create view
     *
     * @param capquiz $capquiz The current capquiz
     * @throws \coding_exception
     */
    public function display_question_list_create_view(capquiz $capquiz) {
        $this->display_view(new question_list_creator_renderer($capquiz, $this));
    }

    /**
     * Display the choose question list view
     *
     * @param capquiz $capquiz The current capquiz
     * @throws \coding_exception
     */
    public function display_choose_question_list_view(capquiz $capquiz) {
        $this->display_view(new question_list_selection_renderer($this, $capquiz->context()));
    }

    /**
     * Display the unauthorized view
     *
     * @throws \coding_exception
     */
    public function display_unauthorized_view() {
        $this->display_view(new unauthorized_view_renderer($this));
    }

    /**
     * Display the question list view
     *
     * @param capquiz $capquiz The current capquiz
     * @throws \coding_exception
     */
    public function display_question_list_view(capquiz $capquiz) {
        /**
         * @var \stdClass $render
         */
        $render = new class($capquiz, $this) {
            private $capquiz;
            private $renderer;

            /**
             *  constructor.
             * @param capquiz $capquiz
             * @param renderer $renderer
             */
            public function __construct(capquiz $capquiz, renderer $renderer) {
                $this->capquiz = $capquiz;
                $this->renderer = $renderer;
            }

            /**
             * Renders question list
             *
             * @return string
             */
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

    /**
     * Display the rating system configuration
     *
     * @param capquiz $capquiz The current capquiz
     * @throws \coding_exception
     */
    public function display_rating_system_configuration(capquiz $capquiz) {
        $this->display_tabbed_views([
            new matchmaking_strategy_selection_renderer($capquiz, $this),
            new matchmaking_configuration_renderer($capquiz, $this),
            new rating_system_selection_renderer($capquiz, $this),
            new rating_system_configuration_renderer($capquiz, $this)
        ], 'view_rating_system');
    }


    /**
     * Display the leaderboard view
     *
     * @param capquiz $capquiz The current capquiz
     * @throws \coding_exception
     */
    public function display_leaderboard(capquiz $capquiz) {
        $this->display_tabbed_view(new classlist_renderer($capquiz, $this), 'view_classlist');
    }

    /**
     * Display the import view
     *
     * @param capquiz $capquiz The current capquiz
     * @throws \coding_exception
     */
    public function display_import(capquiz $capquiz) {
        $this->display_tabbed_view(new import_renderer($capquiz, $this), 'view_import');
    }

    /**
     * Display the grading configuration view
     *
     * @param capquiz $capquiz The current capquiz
     * @throws \coding_exception
     */
    public function display_grading_configuration(capquiz $capquiz) {
        $this->display_tabbed_view(new grading_configuration_renderer($capquiz, $this), 'view_grading');
    }

    /**
     * Display the report view
     *
     * @param capquiz $capquiz The current capquiz
     * @throws \coding_exception
     */
    public function display_report(capquiz $capquiz) {
        $this->display_tabbed_view(new report_renderer($capquiz, $this), 'view_report');
    }
}
