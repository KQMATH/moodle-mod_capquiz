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

declare(strict_types=1);

namespace mod_capquiz\question\bank;

use mod_capquiz\local\helpers\questions;

/**
 * Checkbox column which only displays for questions that haven't been added.
 *
 * @package     mod_capquiz
 * @author      Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright   2024 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class checkbox_column extends \core_question\local\bank\checkbox_column {
    /** @var int[] Question IDs that have already been added to the CAPQuiz */
    private array $addedquestionids = [];

    /**
     * Load IDs of questions already added to the quiz.
     */
    protected function init(): void {
        $quizcmid = $this->qbank instanceof question_bank_view ? $this->qbank->quizcmid : $this->qbank->cm->id;
        $context = \core\context\module::instance($quizcmid);
        $questions = questions::get_all_questions_by_references($context->id, 'slot');
        $this->addedquestionids = array_column($questions, 'id');
    }

    /**
     * Display content.
     *
     * @param \stdClass $question
     * @param string $rowclasses
     */
    protected function display_content($question, $rowclasses): void {
        if (!in_array($question->id, $this->addedquestionids)) {
            parent::display_content($question, $rowclasses);
        }
    }
}
