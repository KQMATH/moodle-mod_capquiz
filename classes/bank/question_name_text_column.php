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
 * A column type for the name followed by the start of the question text.
 *
 * This class is copied to CAPQuiz from the Core Quiz, with the addition of 
 * the `quiz_question_tostring` method copied from Core Quiz' locallib.
 * 
 * @package    mod_capquiz
 * @package    mod_capquiz
 * @category   question
 * @copyright  2009 Tim Hunt
 * @author     2022 Hans Georg Schaathun <hasc@ntnu.no>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_name_text_column extends question_name_column {

    public function get_name(): string {
        return 'questionnametext';
    }


    /**
     * Creates a textual representation of a question for display.
     *
     * @param object $question A question object from the database questions table
     * @param bool $showicon If true, show the question's icon with the question. False by default.
     * @param bool $showquestiontext If true (default), show question text after question name.
     *       If false, show only question name.
     * @param bool $showidnumber If true, show the question's idnumber, if any. False by default.
     * @param core_tag_tag[]|bool $showtags if array passed, show those tags. Else, if true, get and show tags,
     *       else, don't show tags (which is the default).
     * @return string HTML fragment.
     */
    protected function quiz_question_tostring($question, $showicon = false, $showquestiontext = true,
            $showidnumber = false, $showtags = false) {
        global $OUTPUT;
        $result = '';

        // Question name.
        $name = shorten_text(format_string($question->name), 200);
        if ($showicon) {
            $name .= print_question_icon($question) . ' ' . $name;
        }
        $result .= html_writer::span($name, 'questionname');

        // Question idnumber.
        if ($showidnumber && $question->idnumber !== null && $question->idnumber !== '') {
            $result .= ' ' . html_writer::span(
                    html_writer::span(get_string('idnumber', 'question'), 'accesshide') .
                    ' ' . s($question->idnumber), 'badge badge-primary');
        }
    
        // Question tags.
        if (is_array($showtags)) {
            $tags = $showtags;
        } else if ($showtags) {
            $tags = core_tag_tag::get_item_tags('core_question', 'question', $question->id);
        } else {
            $tags = [];
        }
        if ($tags) {
            $result .= $OUTPUT->tag_list($tags, null, 'd-inline', 0, null, true);
        }
    
        // Question text.
        if ($showquestiontext) {
            $questiontext = \question_utils::to_plain_text($question->questiontext,
                    $question->questiontextformat, array('noclean' => true, 'para' => false));
            $questiontext = shorten_text($questiontext, 50);
            if ($questiontext) {
                $result .= ' ' . html_writer::span(s($questiontext), 'questiontext');
            }
        }
    
        return $result;
    }
    protected function display_content($question, $rowclasses): void {
        echo \html_writer::start_tag('div');
        $labelfor = $this->label_for($question);
        if ($labelfor) {
            echo \html_writer::start_tag('label', ['for' => $labelfor]);
        }
        echo $this->quiz_question_tostring($question, false, true, true, $question->tags);
        if ($labelfor) {
            echo \html_writer::end_tag('label');
        }
        echo \html_writer::end_tag('div');
    }

    public function get_required_fields(): array {
        $fields = parent::get_required_fields();
        $fields[] = 'q.questiontext';
        $fields[] = 'q.questiontextformat';
        $fields[] = 'qbe.idnumber';
        return $fields;
    }

    public function load_additional_data(array $questions) {
        parent::load_additional_data($questions);
        parent::load_question_tags($questions);
    }
}
