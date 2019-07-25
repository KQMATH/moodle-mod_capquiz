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
 * Class to store the options for a {@link capquiz_questions_report}.
 *
 * @package     capquizreport_questions
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace capquizreport_questions;

use mod_capquiz\report\capquiz_attempts_report;
use mod_capquiz\report\capquiz_attempts_report_options;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/capquiz/report/attemptsreport_options.php');


/**
 * Class to store the options for a {@link capquiz_questions_report}.
 *
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquizreport_questions_options extends capquiz_attempts_report_options {

    /** @var bool whether to show the question text columns. */
    public $showqtext = false;

    public $attempts = capquiz_attempts_report::ALL_WITH;

    public function get_initial_form_data() {
        $toform = parent::get_initial_form_data();

        $toform->qtext = $this->showqtext;

        return $toform;
    }

    public function setup_from_form_data($fromform) {
        parent::setup_from_form_data($fromform);

        $this->showqtext = $fromform->qtext;
    }

    public function setup_from_params() {
        parent::setup_from_params();

        $this->showqtext = optional_param('qtext', $this->showqtext, PARAM_BOOL);
    }

    public function setup_from_user_preferences() {
        parent::setup_from_user_preferences();

        $this->showqtext = get_user_preferences('capquizreport_questions_qtext', $this->showqtext);
    }

    public function update_user_preferences() {
        parent::update_user_preferences();

        set_user_preference('capquizreport_questions_qtext', $this->showqtext);
    }

    protected function get_url_params() {
        $params = parent::get_url_params();
        $params['qtext'] = $this->showqtext;
        return $params;
    }
}
