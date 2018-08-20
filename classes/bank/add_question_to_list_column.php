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

namespace mod_capquiz\bank;

use mod_capquiz\capquiz_urls;

defined('MOODLE_INTERNAL') || die();

/**
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_question_to_list_column extends \core_question\bank\action_column_base {

    public function get_name() {
        return 'question_include';
    }

    public function get_required_fields() {
        return ['q.id'];
    }

    protected function display_content($question, $css_row_classes) {
        $this->print_icon($this->icon_id(), $this->icon_hover_text(), $this->icon_action_url($question));
    }

    private function icon_id() {
        return 't/add';
    }

    private function icon_hover_text() {
        return get_string('add_the_quiz_question', 'capquiz');
    }

    private function icon_action_url(\stdClass $question) {
        return capquiz_urls::add_question_to_list_url($question->id);
    }

}
