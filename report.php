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
 * Show a report.
 *
 * @package     mod_capquiz
 * @author      Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright   2024 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_capquiz\capquiz;
use mod_capquiz\local\reports\report;

require_once(__DIR__ . '/../../config.php');

global $CFG, $OUTPUT, $PAGE;

require_once($CFG->dirroot . '/mod/capquiz/report/reportlib.php');

$cmid = required_param('id', PARAM_INT);
$download = optional_param('download', '', PARAM_RAW);
$reporttype = optional_param('reporttype', '', PARAM_ALPHA);

$cm = get_coursemodule_from_id('capquiz', $cmid, 0, false, MUST_EXIST);
require_login($cm->course, false, $cm);
$context = \core\context\module::instance($cmid);
require_capability('mod/capquiz:instructor', $context);

$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_pagelayout('report');
$PAGE->set_url(new \core\url('/mod/capquiz/report.php', ['id' => $cmid]));

$capquiz = new capquiz($cm->instance);
$course = get_course($cm->course);

$title = get_string('results', 'quiz');
$title .= moodle_page::TITLE_SEPARATOR;
$title .= format_string($capquiz->get('name'));
$title .= moodle_page::TITLE_SEPARATOR;
$title .= $course->shortname;
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);

$PAGE->activityheader->disable();

$availablereporttypes = capquiz_report_list();

if (empty($reporttype)) {
    $reporttype = reset($availablereporttypes);
    $PAGE->url->param('reporttype', $reporttype);
}

if (!in_array($reporttype, $availablereporttypes)) {
    throw new moodle_exception('erroraccessingreport', 'capquiz');
}

$filepath = "$CFG->dirroot/mod/capquiz/report/$reporttype/classes/report.php";
if (!is_readable($filepath)) {
    throw new moodle_exception("report type '$reporttype' doesn't provide expected file '$filepath'");
}
require_once($filepath);
$classname = "capquizreport_$reporttype\\report";
if (!class_exists($classname)) {
    throw new moodle_exception("report type '$reporttype' doesn't define expected class '$classname' in '$filepath'");
}

/** @var report $report */
$report = new $classname();

echo $OUTPUT->header();
$report->display($capquiz, $PAGE->cm, $PAGE->course, $download);
echo $OUTPUT->footer();
