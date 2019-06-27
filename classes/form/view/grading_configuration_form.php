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
        for ($star = 1; $star <= $qlist->max_stars(); $star++) {
            $groupname = "star_group_$star";
            $input = "star_rating_$star";
            $text = get_string('level_rating', 'capquiz', $star);
            $elements = [];
            $elements[] = $form->createElement('text', $input, $text);
            if ($star > 1) {
                $elements[] = $form->createElement('submit', "delstarbutton$star", 'Delete star');
            }
            $form->addGroup($elements, $groupname, $text, [''], false);
            $form->setType($input, PARAM_INT);
            $form->setDefault($input, $qlist->star_rating($star));
        }

        $form->addElement('submit', 'addstarbutton', 'Add star');

        $strstarstopass = get_string('stars_to_pass', 'capquiz');
        $strstarstopassrequired = get_string('stars_to_pass_required', 'capquiz');
        $form->addElement('text', 'starstopass', $strstarstopass);
        $form->setType('starstopass', PARAM_INT);
        $form->setDefault('starstopass', $this->capquiz->stars_to_pass());
        $form->addRule('starstopass', $strstarstopassrequired, 'required', null, 'client');

        $strduedate = get_string('due_time_grading', 'capquiz');
        $form->addElement('date_time_selector', 'timedue', $strduedate);
        $form->setType('timedue', PARAM_INT);
        $timedue =  $this->capquiz->time_due();
        $oneweek = 60 * 60 * 24 * 7;
        $form->setDefault('timedue', $timedue ? $timedue : time() + $oneweek);

        $form->addElement('submit', 'submitbutton', get_string('savechanges'));
    }

    public function validations($data, $files) {
        $errors = [];
        if (empty($data['default_user_rating'])) {
            $errors['default_user_rating'] = get_string('default_user_rating_required', 'capquiz');
        }
        if (empty($data['starstopass']) || $data['starstopass'] < 0 || $data['starstopass'] > 5) {
            $errors['starstopass'] = get_string('stars_to_pass_required', 'capquiz');
        }
        return $errors;
    }

}
