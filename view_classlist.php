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

/**
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$course_module_id = capquiz_urls::require_course_module_id_param();
$course_module = get_coursemodule_from_id('capquiz', $course_module_id, 0, false, MUST_EXIST);
require_login($course_module->course, false, $course_module);
$context = \context_module::instance($course_module_id);
require_capability('mod/capquiz:instructor', $context);

$capquiz = capquiz::create();
if (!$capquiz) {
    capquiz_urls::redirect_to_front_page();
}

capquiz_urls::set_page_url($capquiz, capquiz_urls::$url_view_question_list);
$renderer = $capquiz->renderer();
$renderer->display_leaderboard($capquiz);
