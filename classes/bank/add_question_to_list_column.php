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
 * This file defines a class for an icon that lets you add a question to a list column
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
class add_question_to_list_column extends \core_question\local\bank\action_column_base {

    /**
     * Get the internal name for this column. Used as a CSS class name,
     * and to store information about the current sort. Must match PARAM_ALPHA.
     *     *
     * @return string
     */
    public function get_name() {
        return 'question_include';
    }

    /**
     * Returns the required fields
     *
     * @return array fields required. use table alias 'q' for the question table, or one of the
     * ones from get_extra_joins. Every field requested must specify a table prefix.
     */
    public function get_required_fields() {
        return ['q.id'];
    }

    /**
     * Output the contents of this column.
     * @param object $question the row from the $question table, augmented with extra information.
     * @param string $rowclasses CSS class names that should be applied to this row of output.
     */
    protected function display_content($question, $rowclasses) {
        $this->print_icon($this->icon_id(), $this->icon_hover_text(), $this->icon_action_url($question));
    }

    /**
     * Returns the id of the icon
     *
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
