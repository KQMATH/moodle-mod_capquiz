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

use cm_info;
use mod_capquiz\capquiz;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/capquiz/classes/local/reports/options.php');

/**
 * Options for the questions report table.
 *
 * @package     capquizreport_attempts
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class options extends \mod_capquiz\local\reports\options {
    /** @var bool whether to show the question answer state (correct or wrong) columns. */
    public bool $showansstate = true;

    /** @var bool whether to show the previous question rating columns. */
    public bool $showqprevrating = true;

    /** @var bool whether to show the user rating columns. */
    public bool $showurating = true;

    /** @var bool whether to show the previous user rating columns. */
    public bool $showuprevrating = true;

    /** @var bool whether to show the question text columns. */
    public bool $showqtext = false;

    /** @var bool whether to show the students' response columns. */
    public bool $showresponses = false;

    /** @var bool whether to show the correct response columns. */
    public bool $showright = false;

    /**
     * Constructor.
     *
     * @param capquiz $capquiz
     * @param cm_info $cm
     * @param \stdClass $course
     */
    public function __construct(capquiz $capquiz, cm_info $cm, \stdClass $course) {
        parent::__construct($capquiz, $cm, $course);
        $this->reporttype = 'attempts';
    }

    /**
     * Get the current value of the settings to pass to the settings form.
     */
    public function get_initial_form_data(): \stdClass {
        $toform = parent::get_initial_form_data();
        $toform->ansstate = $this->showansstate;
        $toform->urating = $this->showurating;
        $toform->uprevrating = $this->showuprevrating;
        $toform->qprevrating = $this->showqprevrating;
        $toform->qtext = $this->showqtext;
        $toform->resp = $this->showresponses;
        $toform->right = $this->showright;
        return $toform;
    }

    /**
     * Set the fields of this object from the form data.
     * @param object $fromform The data from $mform->get_data() from the settings form.
     */
    public function setup_from_form_data($fromform): void {
        parent::setup_from_form_data($fromform);
        $this->showansstate = (bool)$fromform->ansstate;
        $this->showurating = (bool)$fromform->urating;
        $this->showuprevrating = (bool)$fromform->uprevrating;
        $this->showqprevrating = (bool)$fromform->qprevrating;
        $this->showqtext = (bool)$fromform->qtext;
        $this->showresponses = (bool)$fromform->resp;
        $this->showright = (bool)$fromform->right;
    }

    /**
     * Set the fields of this object from the URL parameters.
     */
    public function setup_from_params(): void {
        parent::setup_from_params();
        $this->showansstate = optional_param('ansstate', $this->showansstate, PARAM_BOOL);
        $this->showurating = optional_param('urating', $this->showurating, PARAM_BOOL);
        $this->showuprevrating = optional_param('uprevrating', $this->showuprevrating, PARAM_BOOL);
        $this->showqprevrating = optional_param('qprevrating', $this->showqprevrating, PARAM_BOOL);
        $this->showqtext = optional_param('qtext', $this->showqtext, PARAM_BOOL);
        $this->showresponses = optional_param('resp', $this->showresponses, PARAM_BOOL);
        $this->showright = optional_param('right', $this->showright, PARAM_BOOL);
    }

    /**
     * Set the fields of this object from the user's preferences.
     * (For those settings that are backed by user-preferences).
     */
    public function setup_from_user_preferences(): void {
        parent::setup_from_user_preferences();
        $this->showansstate = (bool)get_user_preferences('capquizreport_attempts_ansstate', $this->showansstate);
        $this->showurating = (bool)get_user_preferences('capquizreport_attempts_urating', $this->showurating);
        $this->showuprevrating = (bool)get_user_preferences('capquizreport_attempts_uprevrating', $this->showuprevrating);
        $this->showqprevrating = (bool)get_user_preferences('capquizreport_attempts_qprevrating', $this->showqprevrating);
        $this->showqtext = (bool)get_user_preferences('capquizreport_attempts_qtext', $this->showqtext);
        $this->showresponses = (bool)get_user_preferences('capquizreport_attempts_resp', $this->showresponses);
        $this->showright = (bool)get_user_preferences('capquizreport_attempts_right', $this->showright);
    }

    /**
     * Update the user preferences so they match the settings in this object.
     * (For those settings that are backed by user-preferences).
     */
    public function update_user_preferences(): void {
        parent::update_user_preferences();
        set_user_preference('capquizreport_attempts_ansstate', $this->showansstate);
        set_user_preference('capquizreport_attempts_urating', $this->showurating);
        set_user_preference('capquizreport_attempts_uprevrating', $this->showuprevrating);
        set_user_preference('capquizreport_attempts_qprevrating', $this->showqprevrating);
        set_user_preference('capquizreport_attempts_qtext', $this->showqtext);
        set_user_preference('capquizreport_attempts_resp', $this->showresponses);
        set_user_preference('capquizreport_attempts_right', $this->showright);
    }

    /**
     * Check the settings, and remove any 'impossible' combinations.
     */
    public function resolve_dependencies(): void {
        parent::resolve_dependencies();

        $showanything = $this->showansstate
            || $this->showurating
            || $this->showqprevrating
            || $this->showuprevrating
            || $this->showqtext
            || $this->showresponses
            || $this->showright;

        if (!$showanything) {
            // We have to show at least something.
            $this->showansstate = true;
            $this->showurating = true;
            $this->showqprevrating = true;
        }

        // We only want to show the checkbox to delete attempts if the user has permissions,
        // and if the report type is showing attempts.
        $candeleteattempts = has_capability('mod/capquiz:deleteattempts', \core\context\module::instance($this->cm->id));
        $this->checkboxcolumn = $candeleteattempts && $this->attempts !== self::ENROLLED_WITHOUT;
    }

    /**
     * Get the URL parameters required to show the report with these options.
     *
     * @return array URL parameter name => value.
     */
    protected function get_url_params(): array {
        $params = parent::get_url_params();
        $params['ansstate'] = $this->showansstate;
        $params['urating'] = $this->showurating;
        $params['uprevrating'] = $this->showuprevrating;
        $params['qprevrating'] = $this->showqprevrating;
        $params['qtext'] = $this->showqtext;
        $params['resp'] = $this->showresponses;
        $params['right'] = $this->showright;
        return $params;
    }
}
