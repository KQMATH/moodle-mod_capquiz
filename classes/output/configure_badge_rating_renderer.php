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
use mod_capquiz\form\view\configure_badge_rating_form;

defined('MOODLE_INTERNAL') || die();

require_once('../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');

class configure_badge_rating_renderer {
    private $capquiz;
    private $renderer;

    public function __construct(capquiz $capquiz, renderer $renderer) {
        $this->capquiz = $capquiz;
        $this->renderer = $renderer;
    }

    public function render() {
        global $PAGE;
        $url = $PAGE->url;
        $question_list = $this->capquiz->question_list();
        $form = new configure_badge_rating_form($question_list, $url);
        if ($form_data = $form->get_data()) {
            $ratings = [
                $form_data->level_1_rating,
                $form_data->level_2_rating,
                $form_data->level_3_rating,
                $form_data->level_4_rating,
                $form_data->level_5_rating
            ];
            $question_list->set_level_ratings($ratings);
            $url = new \moodle_url(capquiz_urls::$url_view_question_list);
            $url->param(capquiz_urls::$param_id, $this->capquiz->course_module_id());
            redirect($url);
        }
        return $this->renderer->render_from_template('capquiz/configure_badge_rating', [
            'form' => $form->render()
        ]);
    }
}
