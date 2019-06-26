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
use mod_capquiz\form\view\user_configuration_form;
use mod_capquiz\form\view\star_configuration_form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/editlib.php');

/**
 * @package     mod_capquiz
 * @author      Sebastian S. Gundersen <sebastsg@stud.ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grading_configuration_renderer {

    /** @var capquiz $capquiz */
    private $capquiz;

    /** @var renderer $renderer */
    private $renderer;

    public function __construct(capquiz $capquiz, renderer $renderer) {
        $this->capquiz = $capquiz;
        $this->renderer = $renderer;
    }

    public function render() {
        return $this->renderer->render_from_template('capquiz/configure_grading', [
            'rating_form' => $this->get_rating_configuration()
        ]);
    }

    private function get_rating_configuration() {
        global $PAGE;
        $url = $PAGE->url;
        $form = new star_configuration_form($this->capquiz, $url);
        $formdata = $form->get_data();
        if ($formdata) {
            if ($formdata->default_user_rating) {
                $this->capquiz->set_default_user_rating($formdata->default_user_rating);
            }
            $ratings = [
                $formdata->level_1_rating,
                $formdata->level_2_rating,
                $formdata->level_3_rating,
                $formdata->level_4_rating,
                $formdata->level_5_rating
            ];
            $this->capquiz->question_list()->set_level_ratings($ratings);
            redirect(capquiz_urls::view_grading_url());
        }
        return $form->render();
    }

}
