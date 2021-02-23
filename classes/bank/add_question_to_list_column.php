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
 * This file defines a class represeting a capquiz question engine
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz\bank;

use mod_capquiz\capquiz_urls;

defined('MOODLE_INTERNAL') || die();

/**
 * Class add_question_to_list_column
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_question_to_list_column extends \core_question\bank\action_column_base {

    /**
     * @return string
     */
    public function get_name() {
        return 'question_include';
    }

    /**
     * @return string[]
     */
    public function get_required_fields() {
        return ['q.id'];
    }

    /**
     * @param object $question
     * @param string $rowclasses
     */
    protected function display_content($question, $rowclasses) {
        $this->print_icon($this->icon_id(), $this->icon_hover_text(), $this->icon_action_url($question));
    }

    /**
     * @return string
     */
    private function icon_id() {
        return 't/add';
    }

    /**
     * Creates the text to show on hover
     *
     * @return \lang_string|string
     * @throws \coding_exception
     */
    private function icon_hover_text() {
        return get_string('add_the_quiz_question', 'capquiz');
    }

    /**
     * Creates a url for adding question to list
     *
     * @param \stdClass $question
     * @return \moodle_url
     */
    private function icon_action_url(\stdClass $question) {
        return capquiz_urls::add_question_to_list_url($question->id);
    }

}
