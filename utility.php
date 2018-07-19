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

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/mod/capquiz/lib.php');

function redirect_to_front_page() {
    header('Location: /');
    exit;
}

function redirect_to_url(\moodle_url $url) {
    redirect($url);
}

function redirect_to_dashboard(capquiz $capquiz) {
    $target_url = new \moodle_url(capquiz_urls::$url_view);
    $target_url->param(capquiz_urls::$param_id, $capquiz->course_module_id());
    redirect_to_url($target_url);
}

function return_to_previous() {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

function set_page_url(capquiz $capquiz, string $url) {
    global $PAGE;
    $PAGE->set_context($capquiz->context());
    $PAGE->set_cm($capquiz->course_module());
    $PAGE->set_pagelayout('incourse');
    $url = new \moodle_url($url);
    $url->param(capquiz_urls::$param_id, $capquiz->course_module_id());
    $PAGE->set_url($url);
}