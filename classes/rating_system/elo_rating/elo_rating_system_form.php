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
 * This file defines a class used to represent an elo rating system form
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz;

use mod_capquiz\capquiz;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Class elo_rating_system_form
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class elo_rating_system_form extends \moodleform {

    /** @var \stdClass $configuration */
    private $configuration;

    /**
     * elo_rating_system_form constructor.
     * @param \stdClass $configuration
     * @param \moodle_url $url
     */
    public function __construct(\stdClass $configuration, \moodle_url $url) {
        $this->configuration = $configuration;
        parent::__construct($url);
    }

    /**
     * Defines rating system form
     *
     * @throws \coding_exception
     */
    public function definition() /*: void*/ {
        $form = $this->_form;

        $form->addElement('text', 'student_k_factor', get_string('student_k_factor', 'capquiz'));
        $form->setType('student_k_factor', PARAM_INT);
        $form->addRule('student_k_factor', get_string('student_k_factor_specified_rule', 'capquiz'), 'required', null, 'client');
        $form->addRule('student_k_factor', get_string('k_factor_numeric_rule', 'capquiz'), 'numeric', null, 'client');
        $form->setDefault('student_k_factor', $this->configuration->student_k_factor);
        $form->addHelpButton('student_k_factor', 'student_k_factor', 'capquiz');

        $form->addElement('text', 'question_k_factor', get_string('question_k_factor', 'capquiz'));
        $form->setType('question_k_factor', PARAM_INT);
        $form->addRule('question_k_factor', get_string('question_k_factor_specified_rule', 'capquiz'), 'required', null, 'client');
        $form->addRule('question_k_factor', get_string('k_factor_numeric_rule', 'capquiz'), 'numeric', null, 'client');
        $form->setDefault('question_k_factor', $this->configuration->question_k_factor);
        $form->addHelpButton('question_k_factor', 'question_k_factor', 'capquiz');

        $this->add_action_buttons(false);
    }
}
