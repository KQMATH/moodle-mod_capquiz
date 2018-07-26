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

use mod_capquiz\capquiz;
use mod_capquiz\capquiz_urls;

defined('MOODLE_INTERNAL') || die();

require_once('../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');

class instructor_dashboard_renderer {

    private $capquiz;
    private $renderer;

    public function __construct(capquiz $capquiz, renderer $renderer) {
        $this->capquiz = $capquiz;
        $this->renderer = $renderer;
    }

    public function render() {
        $canpublish = $this->capquiz->can_publish();
        return $this->renderer->render_from_template('capquiz/instructor_dashboard', [
            'publish' => $canpublish ? $this->publish_button() : false,
            'create_template' => $canpublish ? $this->create_template_button() : false,
        ]);
    }

    private function publish_button() {
        return [
            'primary' => true,
            'method' => 'post',
            'url' => capquiz_urls::question_list_publish_url($this->capquiz->question_list())->out_as_local_url(false),
            'label' => get_string('publish', 'capquiz')
        ];
    }

    private function create_template_button() {
        return [
            'primary' => true,
            'method' => 'post',
            'url' => capquiz_urls::question_list_create_template_url($this->capquiz->question_list())->out_as_local_url(false),
            'label' => get_string('create_template', 'capquiz')
        ];
    }
}
