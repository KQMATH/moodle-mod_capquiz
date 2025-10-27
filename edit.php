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
 * Edit question list.
 *
 * @package     mod_capquiz
 * @author      Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright   2024 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_capquiz\capquiz;
use mod_capquiz\capquiz_slot;

require_once(__DIR__ . '/../../config.php');

global $CFG, $OUTPUT, $PAGE, $USER, $DB;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/mod/capquiz/lib.php');

$cmid = optional_param('id', 0, PARAM_INT);
if (!$cmid) {
    $cmid = required_param('cmid', PARAM_INT);
}
$cm = get_coursemodule_from_id('capquiz', $cmid, 0, false, MUST_EXIST);
require_login($cm->course, false, $cm);
$context = \core\context\module::instance($cmid);
require_capability('mod/capquiz:instructor', $context);

$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_pagelayout('incourse');
$PAGE->set_url(new \core\url('/mod/capquiz/edit.php', ['id' => $cmid]));

$capquiz = new capquiz($cm->instance);
$course = get_course($cm->course);

$title = get_string('questions', 'capquiz');
$title .= moodle_page::TITLE_SEPARATOR;
$title .= format_string($capquiz->get('name'));
$title .= moodle_page::TITLE_SEPARATOR;
$title .= $course->shortname;
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);

$PAGE->activityheader->disable();

if (optional_param('addselectedquestions', false, PARAM_BOOL)) {
    // Question IDs are submitted in input names starting with q, and ending with the question ID.
    foreach ((array)data_submitted() as $key => $value) {
        $matches = [];
        if (preg_match('!^q([0-9]+)$!', $key, $matches)) {
            $questionid = (int)$matches[1];
            $capquiz->create_slot($questionid, $capquiz->get('defaultquestionrating'));
        }
    }
    redirect($PAGE->url);
}

$action = optional_param('action', null, PARAM_ALPHA);
switch ($action) {
    case 'addquestion':
        $questionid = required_param('questionid', PARAM_INT);
        $capquiz->create_slot($questionid, $capquiz->get('defaultquestionrating'));
        redirect($PAGE->url);
        // Unreachable.

    case 'deleteslot':
        $slotid = required_param('slotid', PARAM_INT);
        $slot = new capquiz_slot($slotid);
        // We have already confirmed capability on this quiz, so it's enough to check the slot belongs to it.
        if ($slot->get('capquizid') === $capquiz->get('id')) {
            $slot->delete();
        }
        redirect($PAGE->url);
        // Unreachable.

    case 'regradeall':
        capquiz_update_grades($capquiz->to_record());
        redirect($PAGE->url);
        // Unreachable.

    default:
        break;
}

echo $OUTPUT->header();
/** @var \mod_capquiz\output\renderer $renderer */
$renderer = $PAGE->get_renderer('mod_capquiz');
echo $renderer->render(new \mod_capquiz\output\edit_questions($capquiz));
echo $OUTPUT->footer();
