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

use core_renderer;
use mod_capquiz\capquiz;
use mod_capquiz\capquiz_urls;
use moodle_url;
use renderer_base;
use tabobject;

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
     */
    public function output_renderer(): core_renderer|renderer_base {
        return $this->output;
    }

    /**
     * Creates a tab
     *
     * @param string $name Name of the tab
     * @param string $title Title of the tab
     * @param moodle_url $link Link
     */
    private function tab(string $name, string $title, moodle_url $link): tabobject {
        return new tabobject($name, $link, get_string($title, 'capquiz'));
    }

    /**
     * Creates all tabs
     *
     * @param string $activetab The currently active cab
     */
    private function tabs(string $activetab): bool|string {
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
     * @param string $view
     * @param string $activetab
     */
    public function display_tabbed_view(string $view, string $activetab): void {
        echo $this->output->header();
        echo $this->tabs($activetab);
        echo $view;
        echo $this->output->footer();
    }

    /**
     * Display multiple tabbed views
     *
     * @param string[] $views The renderers to render the tabs
     * @param string $activetab The currently active tab
     */
    public function display_tabbed_views(array $views, string $activetab): void {
        echo $this->output->header();
        echo $this->tabs($activetab);
        foreach ($views as $view) {
            echo $view;
        }
        echo $this->output->footer();
    }

    /**
     * Display view.
     *
     * @param string $view
     */
    public function display_view(string $view): void {
        echo $this->output->header();
        echo $view;
        echo $this->output->footer();
    }

    /**
     * Display the question attempt view
     *
     * @param capquiz $capquiz
     */
    public function display_question_attempt_view(capquiz $capquiz): void {
        $renderer = new question_attempt_renderer($capquiz, $this);
        $this->display_view($renderer->render());
    }

    /**
     * Display the instructor dashboard
     *
     * @param capquiz $capquiz
     */
    public function display_instructor_dashboard(capquiz $capquiz): void {
        $renderer = new instructor_dashboard_renderer($capquiz, $this);
        $this->display_tabbed_view($renderer->render(), 'view_dashboard');
    }

    /**
     * Display the question list create view
     *
     * @param capquiz $capquiz
     */
    public function display_question_list_create_view(capquiz $capquiz): void {
        $renderer = new question_list_creator_renderer($capquiz, $this);
        $this->display_view($renderer->render());
    }

    /**
     * Display the choose question list view
     */
    public function display_choose_question_list_view(): void {
        $renderer = new question_list_selection_renderer($this);
        $this->display_view($renderer->render());
    }

    /**
     * Display the unauthorized view
     */
    public function display_unauthorized_view(): void {
        $renderer = new unauthorized_view_renderer($this);
        $this->display_view($renderer->render());
    }

    /**
     * Display the question list view
     *
     * @param capquiz $capquiz
     */
    public function display_question_list_view(capquiz $capquiz): void {
        $r1 = new question_list_renderer($capquiz, $this);
        $r2 = new question_bank_renderer($capquiz);
        $html = '<div>' . $r1->render() . '</div>';
        $html .= '<div>' . $r2->render() . '</div >';
        $this->display_tabbed_view($html, 'view_questions');
    }

    /**
     * Display the rating system configuration
     *
     * @param capquiz $capquiz
     */
    public function display_rating_system_configuration(capquiz $capquiz): void {
        $this->display_tabbed_views([
            (new matchmaking_strategy_selection_renderer($capquiz, $this))->render(),
            (new matchmaking_configuration_renderer($capquiz, $this))->render(),
            (new rating_system_selection_renderer($capquiz, $this))->render(),
            (new rating_system_configuration_renderer($capquiz, $this))->render(),
        ], 'view_rating_system');
    }


    /**
     * Display the leaderboard view
     *
     * @param capquiz $capquiz
     */
    public function display_leaderboard(capquiz $capquiz): void {
        $renderer = new classlist_renderer($capquiz, $this);
        $this->display_tabbed_view($renderer->render(), 'view_classlist');
    }

    /**
     * Display the import view
     *
     * @param capquiz $capquiz
     */
    public function display_import(capquiz $capquiz): void {
        $renderer = new import_renderer($capquiz, $this);
        $this->display_tabbed_view($renderer->render(), 'view_import');
    }

    /**
     * Display the grading configuration view
     *
     * @param capquiz $capquiz
     */
    public function display_grading_configuration(capquiz $capquiz): void {
        $renderer = new grading_configuration_renderer($capquiz, $this);
        $this->display_tabbed_view($renderer->render(), 'view_grading');
    }

    /**
     * Display the report view
     *
     * @param capquiz $capquiz
     */
    public function display_report(capquiz $capquiz): void {
        $renderer = new report_renderer($capquiz);
        $this->display_tabbed_view($renderer->render(), 'view_report');
    }
}
