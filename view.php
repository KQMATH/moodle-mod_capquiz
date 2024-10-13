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
 * View page.
 *
 * @package     mod_capquiz
 * @author      Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright   2024 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_capquiz\capquiz;
use mod_capquiz\output\classlist;
use mod_capquiz\output\renderer;

require_once(__DIR__ . '/../../config.php');

global $CFG, $OUTPUT, $PAGE, $USER;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/mod/capquiz/lib.php');

$cmid = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('capquiz', $cmid, 0, false, MUST_EXIST);
require_login($cm->course, false, $cm);
$context = \core\context\module::instance($cmid);

$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_pagelayout('incourse');
$PAGE->set_url(new \core\url('/mod/capquiz/view.php', ['id' => $cmid]));

/** @var renderer $renderer */
$renderer = $PAGE->get_renderer('mod_capquiz');

echo $OUTPUT->header();

if (has_capability('mod/capquiz:instructor', $context)) {
    echo $renderer->render(new classlist(new capquiz($cm->instance)));
}

if (has_any_capability(['mod/capquiz:student', 'mod/capquiz:instructor'], $context)) {
    $attempturl = new \core\url('/mod/capquiz/attempt.php', ['id' => $cmid]);
    echo '<h2>Attempt quiz</h2>';
    echo '<div>';
    echo $renderer->render(new action_link($attempturl, get_string('attempt', 'capquiz')));
    echo '</div>';
}

echo $OUTPUT->footer();
