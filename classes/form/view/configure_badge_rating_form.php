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

use mod_capquiz\capquiz_question_list;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class configure_badge_rating_form extends \moodleform {

    private $question_list;

    public function __construct(capquiz_question_list $question_list, \moodle_url $url) {
        $this->question_list = $question_list;
        parent::__construct($url);
    }

    public function definition() {
        $form = $this->_form;
        for ($i = 0; $i < $this->question_list->level_count(); $i++) {
            $level = $i + $this->question_list->first_level();
            $element = "level_{$level}_rating";
            $text = get_string('level_rating', 'capquiz', $level);
            $requiredtext = get_string('level_rating_required', 'capquiz', $level);
            $form->addElement('text', $element, $text);
            $form->setType($element, PARAM_INT);
            $form->addRule($element, $requiredtext, 'required', null, 'client');
            $form->setDefault($element, $this->question_list->level_rating($level));
        }

        $form->addElement('submit', 'submitbutton', get_string('configure', 'capquiz'));
    }

    public function validations($data, $files) {
        $validation_errors = [];
        if (empty($data['title'])) {
            $validation_errors['title'] = get_string('title_required', 'capquiz');
        }
        if (empty($data['description'])) {
            $validation_errors['description'] = get_string('description_required', 'capquiz');
        }
        for ($i = 0; $i < $this->question_list->level_count(); $i++) {
            $level = $i + $this->question_list->first_level();
            $element = "level_{$level}_rating";
            if (empty($data[$element])) {
                $requiredtext = get_string('level_rating_required', 'capquiz', $level);
                $validation_errors[$element] = $requiredtext;
            }
        }
        return $validation_errors;
    }

}
