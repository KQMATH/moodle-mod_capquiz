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
use mod_capquiz\capquiz_question_list;
use mod_capquiz\capquiz_question_registry;
use mod_capquiz\capquiz_urls;

defined('MOODLE_INTERNAL') || die();

require_once('../../config.php');

/**
 * @package     mod_capquiz
 * @author      Sebastian S. Gundersen <sebastsg@stud.ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_list_selection_renderer {

    private $renderer;

    public function __construct(renderer $renderer) {
        $this->renderer = $renderer;
    }

    public function render() {
        $templates = capquiz_question_list::load_question_list_templates();
        $lists = [];
        foreach ($templates as $template) {
            $lists[] = [
                'title' => $template->title(),
                'description' => $template->description(),
                'author' => $template->author()->username,
                'created' => date("Y-m-d H:i:s", substr($template->time_created(), 0, 10)),
                'url' => capquiz_urls::question_list_select_url($template)
            ];
        }

        $createurl = capquiz_urls::view_create_question_list_url();
        $params = $createurl->params();
        $createurl->remove_all_params();
        $createlabel = get_string('create_question_list', 'capquiz');
        $create = basic_renderer::render_action_button($this->renderer, $createurl, $createlabel, 'get', $params);

        return $this->renderer->render_from_template('capquiz/question_list_selection', [
            'lists' => $lists,
            'create' => $create
        ]);
    }
}
