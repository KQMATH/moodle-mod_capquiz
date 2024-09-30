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
 * This file defines a class represeting a question bank view.
 *
 * It is based on similar implementations from the Core Quiz,
 * but intended to run in a pane rather than a modal overlay,
 * some differences are needed. It includes legacy code from
 * different versions of moodle, and should have been refactored.
 *
 * @package     mod_capquiz
 * @author      Hans Georg Schaathun <hasc@ntnu.no>
 * @copyright   2018/2022 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz\bank;

use context;

/**
 * Class question_bank_view
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_bank_view extends \core_question\local\bank\view {

    /**
     * Specify the column heading
     */
    protected function heading_column(): string {
        return question_name_text_column::class;
    }

    /**
     * Display button to add selected questions to the quiz.
     *
     * @param context $catcontext
     */
    protected function display_bottom_controls(context $catcontext): void {
        echo '<div class="pt-2">';
        if (has_capability('moodle/question:useall', $catcontext)) {
            echo '<button class="btn btn-primary capquiz-add-selected-questions">';
            echo get_string('add_to_quiz', 'capquiz');
            echo '</button>';
        }
        echo '</div>';
    }

}
