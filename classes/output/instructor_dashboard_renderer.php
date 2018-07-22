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
use mod_capquiz\capquiz_badge;

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
        // TODO: Find another place to add badges.
        $badge = new capquiz_badge($this->capquiz->course_module()->course, $this->capquiz->id());
        $badge->create_badges();

        return $this->renderer->render_from_template('capquiz/instructor_dashboard', [
            'view_question_list_url' => capquiz_urls::view_question_list_url(),
            'view_leaderboard_url' => capquiz_urls::view_leaderboard_url(),
            'view_configuration_url' => capquiz_urls::view_configuration_url(),
            'publish' => $this->capquiz->can_publish() ? $this->publish_button() : false,
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
}
