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
use mod_capquiz\capquiz_question_list;
use mod_capquiz\form\view\question_list_create_form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/editlib.php');

/**
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_list_creator_renderer {

    /** @var capquiz $capquiz */
    private $capquiz;

    /** @var renderer $renderer */
    private $renderer;

    public function __construct(capquiz $capquiz, renderer $renderer) {
        $this->capquiz = $capquiz;
        $this->renderer = $renderer;
    }

    public function render() {
        global $PAGE;
        $url = $PAGE->url;
        $form = new question_list_create_form($url);
        $formdata = $form->get_data();
        if ($formdata) {
            $ratings = [
                $formdata->level_1_rating,
                $formdata->level_2_rating,
                $formdata->level_3_rating,
                $formdata->level_4_rating,
                $formdata->level_5_rating
            ];
            $title = $formdata->title;
            $description = $formdata->description;
            $qlist = capquiz_question_list::create_new_instance($this->capquiz, $title, $description, $ratings);
            if ($qlist) {
                redirect(capquiz_urls::create_view_url(capquiz_urls::$urlview));
            }
            header('Location: /');
            exit;
        }
        return $this->renderer->render_from_template('capquiz/create_question_list', [
            'form' => $form->render()
        ]);
    }

}
