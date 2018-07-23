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

namespace mod_capquiz\form\view;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class create_question_set_form extends \moodleform {

    public function definition() {
        $form = $this->_form;
        $form->addElement('text', 'title', get_string('title', 'capquiz'));
        $form->setType('title', PARAM_TEXT);
        $form->addRule('title', get_string('title_required', 'capquiz'), 'required', null, 'client');

        $form->addElement('textarea', 'description', get_string('description', 'capquiz'));
        $form->setType('description', PARAM_TEXT);
        $form->addRule('description', get_string('description_required', 'capquiz'), 'required', null, 'client');

        $ratings = [1300, 1450, 1600, 1800, 2000];
        for ($level = 1; $level < 6; $level++) {
            $element = "level_{$level}_rating";
            $text = get_string('level_rating', 'capquiz', $level);
            $requiredtext = get_string('level_rating_required', 'capquiz', $level);
            $form->addElement('text', $element, $text);
            $form->setType($element, PARAM_INT);
            $form->addRule($element, $requiredtext, 'required', null, 'client');
            $form->setDefault($element, $ratings[$level - 1]);
        }

        $form->addElement('submit', 'submitbutton', get_string('create_question_list', 'capquiz'));
    }

    public function validations($data, $files) {
        $validation_errors = [];
        if (empty($data['title'])) {
            $validation_errors['title'] = get_string('title_required', 'capquiz');
        }
        if (empty($data['description'])) {
            $validation_errors['description'] = get_string('description_required', 'capquiz');
        }
        for ($level = 1; $level < 6; $level++) {
            $element = "level_{$level}_rating";
            if (empty($data[$element])) {
                $requiredtext = get_string('level_rating_required', 'capquiz', $level);
                $validation_errors[$element] = $requiredtext;
            }
        }
        return $validation_errors;
    }

}
