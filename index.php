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
 * List all instances of CAPQuiz in a course.
 *
 * @package     mod_capquiz
 * @author      Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright   2024 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_capquiz\capquiz;
use mod_capquiz\output\index_table;
use mod_capquiz\output\renderer;

require_once('../../config.php');

global $DB, $PAGE, $OUTPUT;

$courseid = required_param('id', PARAM_INT);
$course = get_course($courseid);
$context = \core\context\course::instance($courseid);
require_login($course);
require_capability('mod/capquiz:instructor', $context);

$capquizplural = get_string('modulenameplural', 'capquiz');

$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_url(new \core\url('/mod/capquiz/index.php', ['id' => $courseid]));
$PAGE->set_title("$course->fullname: $capquizplural");
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

/** @var renderer $renderer */
$renderer = $PAGE->get_renderer('mod_capquiz');
echo $renderer->render(new index_table(capquiz::get_records(['course' => $courseid])));

echo $OUTPUT->footer();
