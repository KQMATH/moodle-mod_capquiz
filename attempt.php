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
 * Show current question attempt for user.
 *
 * @package     mod_capquiz
 * @author      Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright   2024 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_capquiz\capquiz;
use mod_capquiz\capquiz_slot;
use mod_capquiz\capquiz_user;
use mod_capquiz\output\attempt;

require_once(__DIR__ . '/../../config.php');

global $CFG, $OUTPUT, $PAGE, $USER;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/mod/capquiz/lib.php');

$cmid = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('capquiz', $cmid, 0, false, MUST_EXIST);
require_login($cm->course, false, $cm);
$context = \core\context\module::instance($cmid);
if (!has_any_capability(['mod/capquiz:student', 'mod/capquiz:instructor'], $context)) {
    redirect(new \core\url('/'));
}

$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_pagelayout('incourse');
$PAGE->set_url(new \core\url('/mod/capquiz/attempt.php', ['id' => $cmid]));
$PAGE->set_cacheable(false);

$capquiz = new capquiz($cm->instance);
$course = get_course($cm->course);

$title = format_string($capquiz->get('name'));
$title .= moodle_page::TITLE_SEPARATOR;
$title .= $course->shortname;
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);

$PAGE->activityheader->disable();

/** @var \mod_capquiz\output\renderer $renderer */
$renderer = $PAGE->get_renderer('mod_capquiz');

$user = capquiz_user::get_record([
    'userid' => $USER->id,
    'capquizid' => $capquiz->get('id'),
]);
if (!$user) {
    $user = $capquiz->create_user($USER->id);
}

$attempt = $user->find_unreviewed_attempt();
if (!$attempt) {
    $slot = capquiz_slot::get_record_for_next_question($user);
    if ($slot) {
        $attempt = $user->create_attempt($slot);
    }
}

$action = optional_param('action', '', PARAM_ALPHA);
switch ($action) {
    case 'submit':
        // The attempt will now be submitted. The page would render as normal such that the user can
        // review question feedback, but we want to redirect to a 'clean' attempt page.
        $attempt->submit($capquiz, $user);
        redirect($PAGE->url);
        // Unreachable.

    case 'review':
        // The attempt will now be marked as reviewed. A new question will be presented when redirected.
        $attempt->mark_as_reviewed();
        redirect($PAGE->url);
        // Unreachable.

    default:
        break;
}

echo $OUTPUT->header();

if ($attempt) {
    echo $renderer->render(new attempt($attempt, $user, $capquiz));
} else {
    echo get_string('you_finished_capquiz', 'capquiz');
}

echo $OUTPUT->footer();
