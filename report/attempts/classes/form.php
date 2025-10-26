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

declare(strict_types=1);

namespace capquizreport_attempts;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * This is the settings form for the capquiz attempts report.
 *
 * @package     capquizreport_attempts
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class form extends \moodleform {
    /**
     * Defines the form
     */
    protected function definition(): void {
        $mform = $this->_form;

        $mform->addElement('select', 'attempts', get_string('reportattemptsfrom', 'quiz'), [
            \mod_capquiz\local\reports\options::ENROLLED_WITH => get_string('reportuserswith', 'quiz'),
            \mod_capquiz\local\reports\options::ENROLLED_WITHOUT => get_string('reportuserswithout', 'quiz'),
            \mod_capquiz\local\reports\options::ENROLLED_ALL => get_string('reportuserswithorwithout', 'quiz'),
            \mod_capquiz\local\reports\options::ALL_WITH => get_string('reportusersall', 'quiz'),
        ]);

        $mform->addElement('advcheckbox', 'onlyanswered', '', get_string('reportshowonlyanswered', 'capquiz'));
        $mform->addHelpButton('onlyanswered', 'reportshowonlyanswered', 'capquiz');

        $mform->addElement('text', 'pagesize', get_string('pagesize', 'quiz'));
        $mform->setType('pagesize', PARAM_INT);

        $mform->addGroup([
            $mform->createElement('advcheckbox', 'ansstate', '', get_string('ansstate', 'capquizreport_attempts')),
            $mform->createElement('advcheckbox', 'urating', '', get_string('urating', 'capquizreport_attempts')),
            $mform->createElement('advcheckbox', 'uprevrating', '', get_string('uprevrating', 'capquizreport_attempts')),
            $mform->createElement('advcheckbox', 'qprevrating', '', get_string('qprevrating', 'capquizreport_attempts')),
            $mform->createElement('advcheckbox', 'qtext', '', get_string('questiontext', 'quiz_responses')),
            $mform->createElement('advcheckbox', 'resp', '', get_string('response', 'quiz_responses')),
            $mform->createElement('advcheckbox', 'right', '', get_string('rightanswer', 'quiz_responses')),
        ], 'coloptions', get_string('showthe', 'quiz_responses'), [' '], false);

        $mform->addElement('submit', 'submitbutton', get_string('showreport', 'quiz'));
    }

    /**
     * Validate the data from the form.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $options = ['urating', 'uprevrating', 'qprevrating', 'ansstate', 'qtext', 'resp', 'right'];
        if (empty(array_filter($options, fn(string $key) => isset($data[$key]) && $data[$key]))) {
            $errors['coloptions'] = get_string('reportmustselectstate', 'quiz');
        }
        return $errors;
    }
}
