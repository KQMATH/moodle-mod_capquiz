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
 * This file defines a class used to render the grading configuration view
 *
 * @package     mod_capquiz
 * @author      Sebastian S. Gundersen <sebastian@sgundersen.com>
 * @copyright   2019 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz\output;

use mod_capquiz\capquiz;
use mod_capquiz\capquiz_urls;
use mod_capquiz\form\view\grading_configuration_form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/editlib.php');

/**
 * Class grading_configuration_renderer
 *
 * @package     mod_capquiz
 * @author      Sebastian S. Gundersen <sebastian@sgundersen.com>
 * @copyright   2019 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grading_configuration_renderer {

    /** @var capquiz $capquiz */
    private $capquiz;

    /** @var renderer $renderer */
    private $renderer;

    /**
     * grading_configuration_renderer constructor.
     * @param capquiz $capquiz
     * @param renderer $renderer
     */
    public function __construct(capquiz $capquiz, renderer $renderer) {
        $this->capquiz = $capquiz;
        $this->renderer = $renderer;
    }

    /**
     * Render grading configuration view
     *
     * @return bool|string
     * @throws \moodle_exception
     */
    public function render() {
        return $this->renderer->render_from_template('capquiz/configure_grading', [
            'rating_form' => $this->get_rating_configuration()
        ]);
    }

    /**
     * Returns rating configuration form
     *
     * @return string
     */
    private function get_rating_configuration() {
        $PAGE = $this->capquiz->get_page();
        $url = $PAGE->url;
        $form = new grading_configuration_form($this->capquiz, $url);
        $formdata = $form->get_data();
        if ($formdata) {
            $this->process_rating_configuration($formdata);
        }
        return $form->render();
    }

    /**
     * Processes the rating configuration formdata
     *
     * @param object $formdata
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    private function process_rating_configuration($formdata) {
        $star = 1;
        $ratings = [];
        while (isset($formdata->{"star_rating_$star"})) {
            if (!isset($formdata->{"delstarbutton$star"})) {
                $ratings[] = (int)$formdata->{"star_rating_$star"};
            }
            $star++;
        }
        if (isset($formdata->addstarbutton)) {
            $ratings[] = end($ratings) + 100;
        }
        if ($formdata->default_user_rating) {
            $this->capquiz->set_default_user_rating($formdata->default_user_rating);
        }
        $this->capquiz->question_list()->set_star_ratings($ratings);
        if ($formdata->starstopass) {
            $this->capquiz->set_stars_to_pass($formdata->starstopass);
        }
        if ($formdata->timedue) {
            $this->capquiz->set_time_due($formdata->timedue);
        }
        redirect(capquiz_urls::view_grading_url());
    }

}
