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
 * Base class for capquiz report plugins.
 *
 * @package     mod_capquiz
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz\report;

defined('MOODLE_INTERNAL') || die();

use context_module;
use mod_capquiz\capquiz;
use stdClass;

/**
 * Base class for capquiz report plugins.
 *
 * Doesn't do anything on it's own -- it needs to be extended.
 * This class displays capquiz reports.
 *
 * This file can refer to itself as report.php to pass variables
 * to itself - all these will also be globally available.
 *
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report {
    /**
     * displays the full report
     * @param capquiz $capquiz capquiz object
     * @param stdClass $cm - course_module object
     * @param stdClass $course - course object
     * @param string $download - type of download being requested
     */
    public function display($capquiz, $cm, $course, $download) {
        // This function renders the html for the report.
        return true;
    }

    /**
     * allows the plugin to control who can see this plugin.
     * @return boolean
     */
    public function canview($contextmodule) {
        return true;
    }

    /**
     * Initialise some parts of $PAGE and start output.
     *
     * @param object $cm the course_module information.
     * @param object $coures the course settings.
     * @param object $capquiz the capquiz settings.
     * @param string $reportmode the report name.
     */
    public function print_header_and_tabs($cm, $course, $capquiz, $reportmode = 'attempts') {
        global $PAGE, $OUTPUT;
        // Print the page header.
        $PAGE->set_title($capquiz->name());
        $PAGE->set_heading($course->fullname);
        $context = context_module::instance($cm->id);
        echo $OUTPUT->heading(format_string(get_string('pluginname', 'capquizreport_' . $reportmode) . ' ' . get_string('report', 'capquiz'), true, array('context' => $context)));
    }
}
