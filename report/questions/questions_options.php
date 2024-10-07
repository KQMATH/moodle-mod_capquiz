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
 * Class to store the options for a {@see capquiz_questions_report}.
 *
 * @package     capquizreport_questions
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace capquizreport_questions;

use mod_capquiz\report\capquiz_attempts_report;
use mod_capquiz\report\capquiz_attempts_report_options;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/capquiz/report/attemptsreport_options.php');

/**
 * Class to store the options for a {@see capquiz_questions_report}.
 *
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquizreport_questions_options extends capquiz_attempts_report_options {

    /** @var bool whether to show the question text columns. */
    public $showqtext = false;

    /**
     * @var string quiz_attempts_report::ALL_WITH or quiz_attempts_report::ENROLLED_WITH
     *      quiz_attempts_report::ENROLLED_WITHOUT or quiz_attempts_report::ENROLLED_ALL
     */
    public $attempts = capquiz_attempts_report::ALL_WITH;

    /**
     * Get the current value of the settings to pass to the settings form.
     */
    public function get_initial_form_data(): stdClass {
        $toform = parent::get_initial_form_data();
        $toform->qtext = $this->showqtext;
        return $toform;
    }

    /**
     * Set the fields of this object from the form data.
     * @param stdClass $fromform The data from $mform->get_data() from the settings form.
     */
    public function setup_from_form_data(stdClass $fromform): void {
        parent::setup_from_form_data($fromform);
        $this->showqtext = $fromform->qtext;
    }

    /**
     * Set the fields of this object from the URL parameters.
     */
    public function setup_from_params(): void {
        parent::setup_from_params();
        $this->showqtext = optional_param('qtext', $this->showqtext, PARAM_BOOL);
    }

    /**
     * Set the fields of this object from the user's preferences.
     * (For those settings that are backed by user-preferences).
     */
    public function setup_from_user_preferences(): void {
        parent::setup_from_user_preferences();
        $this->showqtext = get_user_preferences('capquizreport_questions_qtext', $this->showqtext);
    }

    /**
     * Update the user preferences so they match the settings in this object.
     * (For those settings that are backed by user-preferences).
     */
    public function update_user_preferences(): void {
        parent::update_user_preferences();
        set_user_preference('capquizreport_questions_qtext', $this->showqtext);
    }

    /**
     * Get the URL parameters required to show the report with these options.
     *
     * @return array URL parameter name => value.
     */
    protected function get_url_params(): array {
        $params = parent::get_url_params();
        $params['qtext'] = $this->showqtext;
        return $params;
    }
}
