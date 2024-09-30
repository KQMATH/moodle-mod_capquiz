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
 * CAPQuiz questions settings form definition.
 *
 * @package     capquizreport_questions
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace capquizreport_questions;

use mod_capquiz\report\capquiz_attempts_report;
use mod_capquiz\report\capquiz_attempts_report_form;
use MoodleQuickForm;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/capquiz/report/attemptsreport_form.php');

/**
 * This is the settings form for the capquiz questions report.
 *
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquizreport_questions_settings_form extends capquiz_attempts_report_form {

    /**
     * Defines the form
     */
    protected function definition(): void {
        $mform = $this->_form;

        $this->standard_attempt_fields($mform);
        $this->other_attempt_fields($mform);
        $mform->addElement('header', 'preferencesuser', get_string('reportdisplayoptions', 'quiz'));

        $this->standard_preference_fields($mform);
        $this->other_preference_fields($mform);
        $mform->addElement('submit', 'submitbutton', get_string('showreport', 'quiz'));
    }

    /**
     * Adds the standard attempt fields to form
     *
     * @param MoodleQuickForm $mform the form to add attempt fields to
     */
    protected function standard_attempt_fields(MoodleQuickForm $mform): void {
        $mform->addElement('hidden', 'attempts', capquiz_attempts_report::ALL_WITH);
        $mform->setType('attempts', PARAM_ALPHAEXT);

        $mform->addElement('hidden', 'onlyanswered', 1);
        $mform->setType('onlyanswered', PARAM_INT);
    }

    /**
     * Adds any additional preference fields to form
     *
     * @param MoodleQuickForm $mform the form to add preference fields to
     */
    protected function other_preference_fields(MoodleQuickForm $mform): void {
        $mform->addGroup([
            $mform->createElement('advcheckbox', 'qtext', '', get_string('questiontext', 'quiz_responses')),
        ], 'coloptions', get_string('showthe', 'quiz_responses'), [' '], false);
    }
}
