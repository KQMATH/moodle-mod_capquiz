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
     * Displays the full report.
     *
     * @param capquiz $capquiz
     * @param stdClass $cm
     * @param stdClass $course
     * @param string $download type of download being requested
     */
    public function display(capquiz $capquiz, stdClass $cm, stdClass $course, string $download): bool {
        // This function renders the html for the report.
        return true;
    }

    /**
     * Allows the plugin to control who can see this plugin.
     *
     * @param context_module $contextmodule
     */
    public function canview(context_module $contextmodule): bool {
        return true;
    }

    /**
     * Initialise some parts of $PAGE and start output.
     *
     * @param stdClass $cm the course_module information.
     * @param stdClass $course the course settings.
     * @param capquiz $capquiz the capquiz settings.
     * @param string $reportmode the report name.
     */
    public function print_header_and_tabs(stdClass $cm, stdClass $course, capquiz $capquiz, string $reportmode = 'attempts'): void {
        global $PAGE, $OUTPUT;
        $PAGE->set_title($capquiz->name());
        $PAGE->set_heading($course->fullname);
        $context = context_module::instance($cm->id);
        echo $OUTPUT->heading(format_string(
            get_string('pluginname', 'capquizreport_' . $reportmode) . ' ' . get_string('report', 'capquiz'),
            true, ['context' => $context]));
    }
}
