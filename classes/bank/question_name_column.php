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
 * @package   mod_capquiz
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

    /**
     * Get the internal name for this column. Used as a CSS class name,
     * and to store information about the current sort. Must match PARAM_ALPHA.
     *
     * @return string column name.
     */
    public function get_name(): string {
        return 'questionname';
    }

    /**
     * Title for this column. Not used if is_sortable returns an array.
     *
     * @return string column title.
     */
    public function get_title(): string {
        return get_string('question');
    }

    /**
     * Lable for this column.
     *
     * @param object $question the row from the $question table, augmented with extra information.
     * @return string column label.
     */
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

    /**
     * Output the contents of this column.
     * @param object $question the row from the $question table, augmented with extra information.
     * @param string $rowclasses CSS class names that should be applied to this row of output.
     */
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

    /**
     * Use table alias 'q' for the question table, or one of the
     * ones from get_extra_joins. Every field requested must specify a table prefix.
     *
     * @return array fields required.
     */
    public function get_required_fields(): array {
        return ['q.id', 'q.name'];
    }

    /**
     * Can this column be sorted on? You can return either:
     *  + false for no (the default),
     *  + a field name, if sorting this column corresponds to sorting on that datbase field.
     *  + an array of subnames to sort on as follows
     *  return [
     *      'firstname' => ['field' => 'uc.firstname', 'title' => get_string('firstname')],
     *      'lastname' => ['field' => 'uc.lastname', 'title' => get_string('lastname')],
     *  ];
     * As well as field, and field, you can also add 'revers' => 1 if you want the default sort
     * order to be DESC.
     * @return mixed as above.
     */
    public function is_sortable() {
        return 'q.name';
    }
}
