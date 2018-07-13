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

namespace mod_capquiz\output;

defined('MOODLE_INTERNAL') || die();

class renderer extends \plugin_renderer_base  {

    public function output_renderer() {
        return $this->output;
    }

    public function display($view) {
        echo $this->output->header();
        echo $view->render();
        echo $this->output->footer();
    }

    public function display_student_view(\mod_capquiz\capquiz $capquiz) {
        $this->display(new student_view($capquiz, $this));
    }

    public function display_instructor_view(\mod_capquiz\capquiz $capquiz) {
        $this->display(new instructor_view($capquiz, $this));
    }

    public function display_unauthorized_view() {
        $this->display(new unauthorized_view($this));
    }

}
