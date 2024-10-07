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
use stdClass;

/**
 * Question bank action to add question to quiz.
 *
 * @package    mod_capquiz
 * @copyright  2024 NTNU
 * @author     2024 Sebastian Gundersen
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_to_quiz_action extends \core_question\local\bank\question_action_base {
    /**
     * Get the information required to display this action either as a menu item or a separate action column.
     *
     * @param stdClass $question the row from the $question table, augmented with extra information.
     * @return array with three elements.
     *      $url - the URL to perform the action.
     *      $icon - the icon for this action. E.g. 't/delete'.
     *      $label - text label to display in the UI (either in the menu, or as a tool-tip on the icon)
     */
    protected function get_url_icon_and_label(stdClass $question): array {
        if (!question_has_capability_on($question, 'use')) {
            return [null, null, null];
        }
        return [capquiz_urls::add_question_to_list_url($question->id), 't/add', get_string('addtoquiz', 'quiz')];
    }
}
