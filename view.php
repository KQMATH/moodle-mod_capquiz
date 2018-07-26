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

require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/mod/capquiz/lib.php');
require_once($CFG->dirroot . '/mod/capquiz/utility.php');

$capquiz = capquiz::create();

if (!$capquiz) {
    redirect_to_front_page();
}

set_page_url($capquiz, capquiz_urls::$url_view);
$renderer = $capquiz->renderer();

if ($capquiz->is_instructor()) {
    if (!$capquiz->has_question_list()) {
        $renderer->display_choose_question_list_view($capquiz);
    } else if (!$capquiz->selection_strategy_registry()->has_strategy()) {
        $renderer->display_set_selection_strategy_view($capquiz);
    } else {
        $renderer->display_instructor_dashboard($capquiz);
    }
} else if ($capquiz->is_student()) {
    $renderer->display_question_attempt_view($capquiz);
} else {
    $renderer->display_unauthorized_view();
}
