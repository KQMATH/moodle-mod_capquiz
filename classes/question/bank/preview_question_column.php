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

use core\output\action_link;
use core\output\actions\popup_action;
use core\output\pix_icon;
use qbank_previewquestion\question_preview_options;

/**
 * Question bank column for previewing a question.
 *
 * @package     mod_capquiz
 * @author      Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright   2024 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class preview_question_column extends \core_question\local\bank\column_base {
    /**
     * Display content.
     *
     * @param \stdClass $question
     * @param string $rowclasses
     * @return void
     */
    protected function display_content($question, $rowclasses): void {
        global $OUTPUT;
        if (!\question_bank::is_qtype_installed($question->qtype)) {
            return;
        }
        if (!question_has_capability_on($question, 'use')) {
            return;
        }
        $context = $this->qbank->get_most_specific_context();
        $version = $this->qbank->is_listing_specific_versions() ? $question->version : question_preview_options::ALWAYS_LATEST;
        $url = \qbank_previewquestion\helper::question_preview_url(
            questionid: $question->id,
            context: $context,
            returnurl: $this->qbank->returnurl,
            restartversion: $version,
        );
        $title = get_string('preview');
        $action = new popup_action('click', $url, \qbank_previewquestion\helper::question_preview_popup_params());
        echo $OUTPUT->render(new action_link($url, '', $action, ['title' => $title], new pix_icon('t/preview', $title)));
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
        return 'capquiz_preview_question';
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
