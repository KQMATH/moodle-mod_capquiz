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
 * Class to store the options for a {@link capquiz_attempts_report}.
 *
 * @package     capquizreport_attempts
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace capquizreport_attempts;

use context_module;
use mod_capquiz\report\capquiz_attempts_report;
use mod_capquiz\report\capquiz_attempts_report_options;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/capquiz/report/attemptsreport_options.php');


/**
 * Class to store the options for a {@link capquiz_attempts_report}.
 *
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquizreport_attempts_options extends capquiz_attempts_report_options {

    /** @var bool whether to show the question answer state (correct or wrong) columns. */
    public $showansstate = true;

    /** @var bool whether to show the question rating columns. */
    public $showqrating = true;

    /** @var bool whether to show the previous question rating columns. */
    public $showqprevrating = true;

    /** @var bool whether to show the user rating columns. */
    public $showurating = true;

    /** @var bool whether to show the previous user rating columns. */
    public $showuprevrating = true;

    /** @var bool whether to show the question text columns. */
    public $showqtext = false;

    /** @var bool whether to show the students' response columns. */
    public $showresponses = false;

    /** @var bool whether to show the correct response columns. */
    public $showright = false;

    public function get_initial_form_data() {
        $toform = parent::get_initial_form_data();
        $toform->ansstate = $this->showansstate;
        $toform->urating = $this->showurating;
        $toform->uprevrating = $this->showuprevrating;
        $toform->qrating = $this->showqrating;
        $toform->qprevrating = $this->showqprevrating;
        $toform->qtext = $this->showqtext;
        $toform->resp = $this->showresponses;
        $toform->right = $this->showright;

        return $toform;
    }

    public function setup_from_form_data($fromform) {
        parent::setup_from_form_data($fromform);

        $this->showansstate = $fromform->ansstate;
        $this->showurating = $fromform->urating;
        $this->showuprevrating = $fromform->uprevrating;
        $this->showqrating = $fromform->qrating;
        $this->showqprevrating = $fromform->qprevrating;
        $this->showqtext = $fromform->qtext;
        $this->showresponses = $fromform->resp;
        $this->showright = $fromform->right;
    }

    public function setup_from_params() {
        parent::setup_from_params();

        $this->showansstate = optional_param('ansstate', $this->showansstate, PARAM_BOOL);
        $this->showurating = optional_param('urating', $this->showurating, PARAM_BOOL);
        $this->showuprevrating = optional_param('uprevrating', $this->showuprevrating, PARAM_BOOL);
        $this->showqrating = optional_param('qrating', $this->showqrating, PARAM_BOOL);
        $this->showqprevrating = optional_param('qprevrating', $this->showqprevrating, PARAM_BOOL);
        $this->showqtext = optional_param('qtext', $this->showqtext, PARAM_BOOL);
        $this->showresponses = optional_param('resp', $this->showresponses, PARAM_BOOL);
        $this->showright = optional_param('right', $this->showright, PARAM_BOOL);
    }

    public function setup_from_user_preferences() {
        parent::setup_from_user_preferences();

        $this->showansstate = get_user_preferences('capquizreport_attempts_ansstate', $this->showansstate);
        $this->showurating = get_user_preferences('capquizreport_attempts_urating', $this->showurating);
        $this->showuprevrating = get_user_preferences('capquizreport_attempts_uprevrating', $this->showuprevrating);
        $this->showqrating = get_user_preferences('capquizreport_attempts_qrating', $this->showqrating);
        $this->showqprevrating = get_user_preferences('capquizreport_attempts_qprevrating', $this->showqprevrating);
        $this->showqtext = get_user_preferences('capquizreport_attempts_qtext', $this->showqtext);
        $this->showresponses = get_user_preferences('capquizreport_attempts_resp', $this->showresponses);
        $this->showright = get_user_preferences('capquizreport_attempts_right', $this->showright);
    }

    public function update_user_preferences() {
        parent::update_user_preferences();

        set_user_preference('capquizreport_attempts_ansstate', $this->showansstate);
        set_user_preference('capquizreport_attempts_urating', $this->showurating);
        set_user_preference('capquizreport_attempts_uprevrating', $this->showuprevrating);
        set_user_preference('capquizreport_attempts_qrating', $this->showqrating);
        set_user_preference('capquizreport_attempts_qprevrating', $this->showqprevrating);
        set_user_preference('capquizreport_attempts_qtext', $this->showqtext);
        set_user_preference('capquizreport_attempts_resp', $this->showresponses);
        set_user_preference('capquizreport_attempts_right', $this->showright);
    }

    public function resolve_dependencies() {
        parent::resolve_dependencies();

        if (!$this->showansstate
            && !$this->showurating
            && !$this->showuprevrating
            && !$this->showqrating
            && !$this->showqprevrating
            && !$this->showqtext
            && !$this->showresponses
            && !$this->showright) {
            // We have to show at least something.
            $this->showansstate = true;
            $this->showurating = true;
            $this->showqrating = true;
        }

        // We only want to show the checkbox to delete attempts
        // if the user has permissions and if the report mode is showing attempts.
        $this->checkboxcolumn = has_capability('mod/capquiz:deleteattempts', context_module::instance($this->cm->id))
            && ($this->attempts != capquiz_attempts_report::ENROLLED_WITHOUT);
    }

    protected function get_url_params() {
        $params = parent::get_url_params();
        $params['ansstate'] = $this->showansstate;
        $params['urating'] = $this->showurating;
        $params['uprevrating'] = $this->showuprevrating;
        $params['qrating'] = $this->showqrating;
        $params['qprevrating'] = $this->showqprevrating;
        $params['qtext'] = $this->showqtext;
        $params['resp'] = $this->showresponses;
        $params['right'] = $this->showright;
        return $params;
    }
}
