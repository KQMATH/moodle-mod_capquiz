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
 * CAPQuiz question list form definition.
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz\form\view;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * question_list_create_form class
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_list_create_form extends \moodleform {

    /**
     * Defines form
     */
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

    /**
     * Validate the data from the form.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validations($data, $files) {
        $errors = [];
        if (empty($data['title'])) {
            $errors['title'] = get_string('title_required', 'capquiz');
        }
        if (empty($data['description'])) {
            $errors['description'] = get_string('description_required', 'capquiz');
        }
        for ($level = 1; $level < 6; $level++) {
            $element = "level_{$level}_rating";
            if (empty($data[$element])) {
                $requiredtext = get_string('level_rating_required', 'capquiz', $level);
                $errors[$element] = $requiredtext;
            }
        }
        return $errors;
    }

}
