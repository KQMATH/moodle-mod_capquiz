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

require_once("../../config.php");

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/mod/capquiz/lib.php');
require_once($CFG->dirroot . '/mod/capquiz/utility.php');

if ($capquiz = capquiz::create()) {
    if ($delete_selected = optional_param(capquiz_urls::$param_delete_selected, null, PARAM_TEXT)) {
        echo "Delete is not implemented";
        return;
    }
    else {
        $question_page = optional_param(capquiz_urls::$param_question_page, 0, PARAM_INT);
        redirect_to_url(capquiz_urls::view_question_list_url($question_page));
    }
}

redirect_to_front_page();