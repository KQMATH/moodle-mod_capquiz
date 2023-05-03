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
 * Base class for the settings form for {@see capquiz_attempts_report}s.
 *
 * @package     mod_capquiz
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz\report;

use moodleform;
use MoodleQuickForm;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');


/**
 * Base class for the settings form for {@see capquiz_attempts_report}s.
 *
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class capquiz_attempts_report_form extends moodleform {

    /**
     * Validate the data from the form.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }

    /**
     * Defines the form
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'preferencespage',
            get_string('reportwhattoinclude', 'quiz'));

        $this->standard_attempt_fields($mform);
        $this->other_attempt_fields($mform);

        $mform->addElement('header', 'preferencesuser',
            get_string('reportdisplayoptions', 'quiz'));

        $this->standard_preference_fields($mform);
        $this->other_preference_fields($mform);

        $mform->addElement('submit', 'submitbutton',
            get_string('showreport', 'quiz'));
    }

    /**
     * Adds the standard attempt fields to form
     *
     * @param MoodleQuickForm $mform the form to add attempt fields to
     */
    protected function standard_attempt_fields(MoodleQuickForm $mform) {

        $mform->addElement('select', 'attempts', get_string('reportattemptsfrom', 'quiz'), array(
            capquiz_attempts_report::ENROLLED_WITH => get_string('reportuserswith', 'quiz'),
            // phpcs:disable
            // capquiz_attempts_report::ENROLLED_WITHOUT => get_string('reportuserswithout', 'quiz'),
            // capquiz_attempts_report::ENROLLED_ALL     => get_string('reportuserswithorwithout', 'quiz'),
            // phpcs:enable
            capquiz_attempts_report::ALL_WITH => get_string('reportusersall', 'quiz'),
        ));

        $mform->addElement('advcheckbox', 'onlyanswered', '',
            get_string('reportshowonlyanswered', 'capquiz'));
        $mform->addHelpButton('onlyanswered', 'reportshowonlyanswered', 'capquiz');
    }

    /**
     * Adds any additional attempt fields to form
     *
     * @param MoodleQuickForm $mform the form to add attempt fields to
     */
    protected function other_attempt_fields(MoodleQuickForm $mform) {
    }

    /**
     * Adds the standard preference fields to form
     *
     * @param MoodleQuickForm $mform the form to add preference fields to
     */
    protected function standard_preference_fields(MoodleQuickForm $mform) {
        $mform->addElement('text', 'pagesize', get_string('pagesize', 'quiz'));
        $mform->setType('pagesize', PARAM_INT);
    }

    /**
     * Adds any additional preference fields to form
     *
     * @param MoodleQuickForm $mform the form to add preference fields to
     */
    protected function other_preference_fields(MoodleQuickForm $mform) {
    }
}
