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

namespace capquizreport_questions;

use cm_info;
use mod_capquiz\capquiz;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/capquiz/classes/local/reports/options.php');

/**
 *  Options for the attempts report table.
 *
 * @package     capquizreport_questions
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class options extends \mod_capquiz\local\reports\options {
    /** @var bool whether to show the question text columns. */
    public bool $showqtext = false;

    /**
     * Constructor.
     *
     * @param capquiz $capquiz
     * @param cm_info $cm
     * @param stdClass $course
     */
    public function __construct(capquiz $capquiz, cm_info $cm, stdClass $course) {
        parent::__construct($capquiz, $cm, $course);
        $this->reporttype = 'questions';
        $this->attempts = self::ALL_WITH;
    }

    /**
     * Get the current value of the settings to pass to the settings form.
     *
     * @return stdClass
     */
    public function get_initial_form_data(): stdClass {
        $toform = parent::get_initial_form_data();
        $toform->qtext = $this->showqtext;
        return $toform;
    }

    /**
     * Set the fields of this object from the form data.
     *
     * @param stdClass $fromform The data from $mform->get_data() from the settings form.
     */
    public function setup_from_form_data(stdClass $fromform): void {
        parent::setup_from_form_data($fromform);
        $this->showqtext = (bool)$fromform->qtext;
    }

    /**
     * Set the fields of this object from the URL parameters.
     */
    public function setup_from_params(): void {
        parent::setup_from_params();
        $this->showqtext = (bool)optional_param('qtext', $this->showqtext, PARAM_BOOL);
    }

    /**
     * Set the fields of this object from the user's preferences.
     * (For those settings that are backed by user-preferences).
     */
    public function setup_from_user_preferences(): void {
        parent::setup_from_user_preferences();
        $this->showqtext = (bool)get_user_preferences('capquizreport_questions_qtext', $this->showqtext);
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
