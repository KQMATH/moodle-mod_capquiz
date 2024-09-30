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
 * Base class for the options that control what is visible in an {@see quiz_attempts_report}.
 *
 * @package     mod_capquiz
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz\report;

use mod_capquiz\capquiz;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Base class for the options that control what is visible in an {@see  quiz_attempts_report}.
 *
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_attempts_report_options {

    /** @var string the report mode. */
    public $mode;

    /** @var object the settings for the capquiz being reported on. */
    public $capquiz;

    /** @var object the course module objects for the capquiz being reported on. */
    public $cm;

    /** @var object the course settings for the course the capquiz is in. */
    public $course;

    /**
     * @var string capquiz_attempts_report::ALL_WITH or capquiz_attempts_report::ENROLLED_WITH
     *      capquiz_attempts_report::ENROLLED_WITHOUT or capquiz_attempts_report::ENROLLED_ALL
     */
    public $attempts = capquiz_attempts_report::ENROLLED_WITH;

    /**
     * @var bool whether to show all attempts, or just the ones that are answered.
     */
    public $onlyanswered = true;

    /** @var int Number of attempts to show per page. */
    public $pagesize = capquiz_attempts_report::DEFAULT_PAGE_SIZE;

    /** @var string whether the data should be downloaded in some format, or '' to display it. */
    public $download = '';

    /** @var bool whether the report table should have a column of checkboxes. */
    public $checkboxcolumn = false;

    /**
     * Constructor.
     * @param string $mode which report these options are for.
     * @param capquiz $capquiz the settings for the capquiz being reported on.
     * @param object $cm the course module objects for the capquiz being reported on.
     * @param object $course the course settings for the coures this capquiz is in.
     */
    public function __construct(string $mode, capquiz $capquiz, object $cm, object $course) {
        $this->mode = $mode;
        $this->capquiz = $capquiz;
        $this->cm = $cm;
        $this->course = $course;
    }

    /**
     * Get the URL parameters required to show the report with these options.
     *
     * @return array URL parameter name => value.
     */
    protected function get_url_params(): array {
        return [
            'id' => $this->cm->id,
            'mode' => $this->mode,
            'attempts' => $this->attempts,
            'onlyanswered' => $this->onlyanswered,
        ];
    }

    /**
     * Get the URL to show the report with these options.
     */
    public function get_url(): moodle_url {
        return new moodle_url('/mod/capquiz/view_report.php', $this->get_url_params());
    }

    /**
     * Process the data we get when the settings form is submitted. This includes
     * updating the fields of this class, and updating the user preferences where appropriate.
     *
     * @param stdClass $fromform The data from $mform->get_data() from the settings form.
     */
    public function process_settings_from_form(stdClass $fromform): void {
        $this->setup_from_form_data($fromform);
        $this->resolve_dependencies();
        $this->update_user_preferences();
    }

    /**
     * Set up this preferences object using optional_param (using user_preferences
     * to set anything not specified by the params.
     */
    public function process_settings_from_params(): void {
        $this->setup_from_user_preferences();
        $this->setup_from_params();
        $this->resolve_dependencies();
    }

    /**
     * Get the current value of the settings to pass to the settings form.
     */
    public function get_initial_form_data(): stdClass {
        $toform = new stdClass();
        $toform->attempts = $this->attempts;
        $toform->onlyanswered = $this->onlyanswered;
        $toform->pagesize = $this->pagesize;
        return $toform;
    }

    /**
     * Set the fields of this object from the form data.
     * @param stdClass $fromform The data from $mform->get_data() from the settings form.
     */
    public function setup_from_form_data(stdClass $fromform): void {
        $this->attempts = $fromform->attempts;
        $this->onlyanswered = !empty($fromform->onlyanswered);
        $this->pagesize = $fromform->pagesize;
    }

    /**
     * Set the fields of this object from the URL parameters.
     */
    public function setup_from_params(): void {
        $this->attempts = optional_param('attempts', $this->attempts, PARAM_ALPHAEXT);
        $this->onlyanswered = optional_param('onlyanswered', $this->onlyanswered, PARAM_BOOL);
        $this->pagesize = optional_param('pagesize', $this->pagesize, PARAM_INT);
        $this->download = optional_param('download', $this->download, PARAM_ALPHA);
    }

    /**
     * Set the fields of this object from the user's preferences.
     * (For those settings that are backed by user-preferences).
     */
    public function setup_from_user_preferences(): void {
        $this->pagesize = get_user_preferences('capquiz_report_pagesize', $this->pagesize);
    }

    /**
     * Update the user preferences, so they match the settings in this object.
     * (For those settings that are backed by user-preferences).
     */
    public function update_user_preferences(): void {
        set_user_preference('capquiz_report_pagesize', $this->pagesize);
    }

    /**
     * Check the settings, and remove any 'impossible' combinations.
     */
    public function resolve_dependencies(): void {
        if ($this->pagesize < 1) {
            $this->pagesize = capquiz_attempts_report::DEFAULT_PAGE_SIZE;
        }
    }

}
