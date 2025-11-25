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
use core\output\action_link;
use core\output\pix_icon;

/**
 * Question bank column for adding questions to CAPQuiz.
 *
 * @package     mod_capquiz
 * @author      Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright   2024 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_question_column extends \core_question\local\bank\column_base {
    /** @var int[] Question IDs that have already been added to the CAPQuiz */
    private array $addedquestionids = [];

    /**
     * Load IDs of questions already added to the quiz. This lets us only show an add action for unadded questions.
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
        global $OUTPUT;
        if (!question_has_capability_on($question, 'use')) {
            return;
        }
        if (in_array($question->id, $this->addedquestionids)) {
            echo $OUTPUT->render(new pix_icon('t/check', ''));
            return;
        }
        $url = new \core\url('/mod/capquiz/edit.php', [
            'id' => $this->qbank instanceof question_bank_view ? $this->qbank->quizcmid : $this->qbank->cm->id,
            'action' => 'addquestion',
            'questionid' => $question->id,
        ]);
        $title = get_string('add_to_quiz', 'capquiz');
        $link = new action_link($url, '', null, ['title' => $title], new pix_icon('t/add', $title));
        echo $OUTPUT->render($link);
    }

    /**
     * Return the default column width in pixels.
     *
     * @return int
     */
    public function get_default_width(): int {
        return 24;
    }

    /**
     * Get the internal name for this column. Used as a CSS class name, and to store information about the current sort.
     * Must match PARAM_ALPHA.
     *
     * @return string
     */
    public function get_name(): string {
        return 'capquiz_add_question';
    }

    /**
     * Extra class names to apply to every cell in this column.
     *
     * @return string[]
     */
    public function get_extra_classes(): array {
        return ['iconcol'];
    }

    /**
     * Get title for this column. Not used if is_sortable returns an array.
     *
     * @return string
     */
    public function get_title(): string {
        return '';
    }
}
