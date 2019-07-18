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
 * CAPQuiz attempts settings form definition.
 *
 * @package     capquizreport_attempts
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace capquizreport_attempts;

use mod_capquiz\report\capquiz_attempts_report_form;
use MoodleQuickForm;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/capquiz/report/attemptsreport_form.php');

/**
 * This is the settings form for the capquiz attempts report.
 *
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquizreport_attempts_settings_form extends capquiz_attempts_report_form {

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!($data['urating']
            || $data['uprevrating']
            || $data['qrating']
            || $data['qprevrating']
            || $data['ansstate']
            || $data['qtext']
            || $data['resp']
            || $data['right'])) {
            $errors['coloptions'] = get_string('reportmustselectstate', 'quiz');
        }

        return $errors;
    }

    protected function other_preference_fields(MoodleQuickForm $mform) {
        $mform->addGroup(array(
            $mform->createElement('advcheckbox', 'ansstate', '',
                get_string('ansstate', 'capquizreport_attempts')),
            $mform->createElement('advcheckbox', 'urating', '',
                get_string('urating', 'capquizreport_attempts')),
            $mform->createElement('advcheckbox', 'uprevrating', '',
                get_string('uprevrating', 'capquizreport_attempts')),
            $mform->createElement('advcheckbox', 'qrating', '',
                get_string('qrating', 'capquizreport_attempts')),
            $mform->createElement('advcheckbox', 'qprevrating', '',
                get_string('qprevrating', 'capquizreport_attempts')),
            $mform->createElement('advcheckbox', 'qtext', '',
                get_string('questiontext', 'quiz_responses')),
            $mform->createElement('advcheckbox', 'resp', '',
                get_string('response', 'quiz_responses')),
            $mform->createElement('advcheckbox', 'right', '',
                get_string('rightanswer', 'quiz_responses')),
        ), 'coloptions', get_string('showthe', 'quiz_responses'), array(' '), false);
    }
}
