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

use \html_writer;

/**
 * A column type for the name of the question name.
 *
 * This class is copied to CAPQuiz from the Core Quiz, without
 * modification (as of Fri  9 Sep 08:29:48 UTC 2022  ).
 * 
 * @package   mod_capquiz/mod_quiz
 * @category  question
 * @copyright 2009 Tim Hunt
 * @author    2021 Safat Shahin <safatshahin@catalyst-au.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_name_column extends \core_question\local\bank\column_base {

    /**
     * @var null $checkboxespresent
     */
    protected $checkboxespresent = null;

    public function get_name(): string {
        return 'questionname';
    }

    public function get_title(): string {
        return get_string('question');
    }

    protected function label_for($question): string {
        if (is_null($this->checkboxespresent)) {
            $this->checkboxespresent = $this->qbank->has_column('core_question\local\bank\checkbox_column');
        }
        if ($this->checkboxespresent) {
            return 'checkq' . $question->id;
        } else {
            return '';
        }
    }

    protected function display_content($question, $rowclasses): void {
        $labelfor = $this->label_for($question);
        if ($labelfor) {
            echo \html_writer::start_tag('label', ['for' => $labelfor]);
        }
        echo format_string($question->name);
        if ($labelfor) {
            echo \html_writer::end_tag('label');
        }
    }

    public function get_required_fields(): array {
        return ['q.id', 'q.name'];
    }

    public function is_sortable() {
        return 'q.name';
    }
}