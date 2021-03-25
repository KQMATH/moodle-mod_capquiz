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
 * Async handler for various quiz activities.
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz;

require_once('../../config.php');

$cmid = capquiz_urls::require_course_module_id_param();
$cm = get_coursemodule_from_id('capquiz', $cmid, 0, false, MUST_EXIST);
require_login($cm->course, false, $cm);
$context = \context_module::instance($cmid);
require_capability('mod/capquiz:student', $context);

$action = required_param('action', PARAM_TEXT);
$attemptid = optional_param('attempt', null, PARAM_INT);
$cmid = capquiz_urls::require_course_module_id_param();
$capquiz = new capquiz($cmid);

capquiz_urls::set_page_url($capquiz, capquiz_urls::$urlasync);

if ($attemptid !== null) {
    $user = $capquiz->user();
    $attempt = capquiz_question_attempt::load_attempt($user, $attemptid);
    if ($action === 'answered') {
        $capquiz->question_engine($user)->attempt_answered($user, $attempt);
    } else if ($action === 'reviewed') {
        $capquiz->question_engine($user)->attempt_reviewed($attempt);
    }
    capquiz_urls::redirect_to_dashboard();
}

capquiz_urls::redirect_to_front_page();
