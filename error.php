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

use mod_capquiz\output\renderer;

require_once("../../config.php");

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/mod/capquiz/lib.php');
require_once($CFG->dirroot . '/mod/capquiz/utility.php');

function render_error_view(capquiz $capquiz, renderer $renderer) {
    echo $renderer->output_renderer()->header();
    if ($capquiz->is_instructor()) {
        echo 'Something went wrong(instructor)';
    } else if ($capquiz->is_student()) {
        echo 'Something went wrong(student)';
    }
    echo $renderer->output_renderer()->footer();
}

if ($capquiz = capquiz::create()) {
    set_page_url($capquiz, capquiz_urls::$url_error);
    render_error_view($capquiz, $capquiz->renderer());
} else {
    redirect_to_front_page();
}