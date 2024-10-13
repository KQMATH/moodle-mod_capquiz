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

use core\output\html_writer;
use core_question\local\bank\column_base;
use core_question\local\bank\column_manager_base;
use core_question\local\bank\question_edit_contexts;
use mod_quiz\question\bank\question_name_text_column;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/locallib.php');

/**
 * Question bank view for CAPQuiz.
 *
 * @package     mod_capquiz
 * @author      Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright   2024 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_bank_view extends \core_question\local\bank\view {
    /**
     * Constructor.
     *
     * @param question_edit_contexts $contexts
     * @param \core\url $pageurl
     * @param \stdClass $course course settings
     * @param \stdClass $cm activity settings.
     * @param array $params
     * @param array $extraparams
     */
    public function __construct($contexts, $pageurl, $course, $cm, $params, $extraparams) {
        for ($i = 1; $i <= \core_question\local\bank\view::MAX_SORTS; $i++) {
            $sort = optional_param("qbs$i", '', PARAM_TEXT);
            if ($sort) {
                $params["qbs$i"] = $sort;
            } else {
                break;
            }
        }
        parent::__construct($contexts, $pageurl, $course, $cm, $params, $extraparams);
    }

    /**
     * Get question bank plugins.
     *
     * @return array
     */
    protected function get_question_bank_plugins(): array {
        $questionbankclasscolumns = [];
        $customviewcolumns = [
            'mod_capquiz\question\bank\add_question_column' . column_base::ID_SEPARATOR . 'add_question_column',
            'mod_capquiz\question\bank\checkbox_column' . column_base::ID_SEPARATOR . 'checkbox_column',
            'qbank_viewquestiontype\question_type_column' . column_base::ID_SEPARATOR . 'question_type_column',
            'mod_quiz\question\bank\question_name_text_column' . column_base::ID_SEPARATOR . 'question_name_text_column',
            'mod_capquiz\question\bank\preview_question_column' . column_base::ID_SEPARATOR . 'preview_question_column',
        ];
        foreach ($customviewcolumns as $columnid) {
            [$columnclass, $columnname] = explode(column_base::ID_SEPARATOR, $columnid, 2);
            if (class_exists($columnclass)) {
                $questionbankclasscolumns[$columnid] = $columnclass::from_column_name($this, $columnname);
            }
        }
        return $questionbankclasscolumns;
    }

    /**
     * Don't print the header.
     */
    protected function display_question_bank_header(): void {
    }

    /**
     * Just use the base column manager in this view.
     *
     * @return void
     */
    protected function init_column_manager(): void {
        $this->columnmanager = new column_manager_base();
    }

    /**
     * Don't display plugin controls.
     *
     * @param \core\context $context
     * @param int $categoryid
     * @return string
     */
    protected function get_plugin_controls(\core\context $context, int $categoryid): string {
        return '';
    }

    /**
     * Specify the column heading.
     */
    protected function heading_column(): string {
        return question_name_text_column::class;
    }

    /**
     * Display button to add selected questions to the quiz.
     *
     * @param \core\context $catcontext
     */
    protected function display_bottom_controls(\core\context $catcontext): void {
        echo '<div class="pt-2">';
        if (has_capability('moodle/question:useall', $catcontext)) {
            echo html_writer::empty_tag('input', [
                'type' => 'submit',
                'name' => 'addselectedquestions',
                'class' => 'btn btn-primary',
                'value' => get_string('add_to_quiz', 'capquiz'),
                'data-action' => 'toggle',
                'data-togglegroup' => 'qbank',
                'data-toggle' => 'action',
                'disabled' => true,
            ]);
        }
        echo '</div>';
    }
}
