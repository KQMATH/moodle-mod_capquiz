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
 * Displays the create_question_list view
 *
 * @package     mod_capquiz
 * @author      Sebastian S. Gundersen <sebastian@sgundersen.com>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz;

require_once('../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/mod/capquiz/lib.php');

$cmid = capquiz_urls::require_course_module_id_param();
$cm = get_coursemodule_from_id('capquiz', $cmid, 0, false, MUST_EXIST);
require_login($cm->course, false, $cm);
$context = \context_module::instance($cmid);
require_capability('mod/capquiz:instructor', $context);

$cmid = capquiz_urls::require_course_module_id_param();
$capquiz = new capquiz($cmid);
if (!$capquiz) {
    capquiz_urls::redirect_to_front_page();
}

capquiz_urls::set_page_url($capquiz, capquiz_urls::$urlviewcreateqlist);
$renderer = $capquiz->renderer();

if ($capquiz->has_question_list()) {
    $renderer->display_instructor_dashboard($capquiz);
} else {
    $renderer->display_question_list_create_view($capquiz);
}
