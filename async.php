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

namespace mod_capquiz;

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/capquiz/utility.php');

/**
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function question_attempt_async(capquiz $capquiz, string $action, int $attemptid) {
    $user = $capquiz->user();
    $attempt = capquiz_question_attempt::load_attempt($capquiz, $user, $attemptid);
    if ($action === capquiz_actions::$attempt_answered) {
        $capquiz->question_engine()->attempt_answered($user, $attempt);
    } else if ($action === capquiz_actions::$attempt_reviewed) {
        $capquiz->question_engine()->attempt_reviewed($user, $attempt);
    }
    redirect_to_dashboard($capquiz);
}

function capquiz_async() {
    $cmid = required_param(capquiz_urls::$param_id, PARAM_INT);
    $action = required_param(capquiz_actions::$parameter, PARAM_TEXT);
    $attemptid = optional_param(capquiz_urls::$param_attempt, null, PARAM_INT);
    $capquiz = new capquiz($cmid);
    set_page_url($capquiz, capquiz_urls::$url_async);
    if ($attemptid !== null) {
        question_attempt_async($capquiz, $action, $attemptid);
    }
    redirect_to_front_page();
}

capquiz_async();
