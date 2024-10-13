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
 * Redirect to report page, or view page if the user can't see reports.
 *
 * @package     mod_capquiz
 * @author      Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright   2024 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

global $CFG, $DB, $PAGE;

require_once($CFG->dirroot . '/mod/capquiz/locallib.php');

$cmid = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('capquiz', $cmid, 0, false, MUST_EXIST);
require_login($cm->course, false, $cm);
$context = \core\context\module::instance($cm->id);

$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_url('/mod/capquiz/grade.php', ['id' => $cm->id]);

if (!has_capability('mod/capquiz:instructor', $context)) {
    redirect(new \core\url('/mod/capquiz/view.php', ['id' => $cm->id]));
}

redirect(new \core\url('/mod/capquiz/report.php', ['id' => $cm->id]));
