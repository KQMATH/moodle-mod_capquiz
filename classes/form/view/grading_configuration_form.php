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

use mod_capquiz\capquiz;
use mod_capquiz\capquiz_question_list;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grading_configuration_form extends \moodleform {

    /** @var capquiz $capquiz */
    private $capquiz;

    public function __construct(capquiz $capquiz, \moodle_url $url) {
        $this->capquiz = $capquiz;
        parent::__construct($url);
    }

    public function definition() {
        $qlist = $this->capquiz->question_list();
        $form = $this->_form;
        $form->addElement('text', 'default_user_rating', get_string('default_user_rating', 'capquiz'));
        $form->setType('default_user_rating', PARAM_INT);
        $form->setDefault('default_user_rating', $this->capquiz->default_user_rating());
        $form->addRule('default_user_rating', get_string('default_user_rating_required', 'capquiz'), 'required', null, 'client');
        for ($i = 0; $i < $qlist->level_count(); $i++) {
            $level = $i + $qlist->first_level();
            $element = "level_{$level}_rating";
            $text = get_string('level_rating', 'capquiz', $level);
            $requiredtext = get_string('level_rating_required', 'capquiz', $level);
            $form->addElement('text', $element, $text);
            $form->setType($element, PARAM_INT);
            $form->addRule($element, $requiredtext, 'required', null, 'client');
            $form->setDefault($element, $qlist->required_rating_for_level($level));
        }

        $strstarstopass = get_string('stars_to_pass', 'capquiz');
        $strstarstopassrequired = get_string('stars_to_pass_required', 'capquiz');
        $form->addElement('text', 'starstopass', $strstarstopass);
        $form->setType('starstopass', PARAM_INT);
        $form->setDefault('starstopass', $this->capquiz->stars_to_pass());
        $form->addRule('starstopass', $strstarstopassrequired, 'required', null, 'client');

        $strduedate = get_string('due_time_grading', 'capquiz');
        $form->addElement('date_time_selector', 'timedue', $strduedate);
        $form->setType('timedue', PARAM_INT);
        $form->setDefault('timedue', $this->capquiz->time_due());

        $form->addElement('submit', 'submitbutton', get_string('savechanges'));
    }

    public function validations($data, $files) {
        $errors = [];
        if (empty($data['default_user_rating'])) {
            $errors['default_user_rating'] = get_string('default_user_rating_required', 'capquiz');
        }
        $qlist = $this->capquiz->question_list();
        for ($i = 0; $i < $qlist->level_count(); $i++) {
            $level = $i + $qlist->first_level();
            $element = "level_{$level}_rating";
            if (empty($data[$element])) {
                $requiredtext = get_string('level_rating_required', 'capquiz', $level);
                $errors[$element] = $requiredtext;
            }
        }
        if (empty($data['starstopass']) || $data['starstopass'] < 0 || $data['starstopass'] > 5) {
            $errors['starstopass'] = get_string('stars_to_pass_required', 'capquiz');
        }
        return $errors;
    }

}
