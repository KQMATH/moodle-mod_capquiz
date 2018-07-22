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

use mod_capquiz\capquiz_urls;

defined('MOODLE_INTERNAL') || die();

require_once('../../config.php');

class basic_renderer {

    /**
     * @param renderer $renderer
     * @return bool|string
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public static function render_home_button(renderer $renderer) {
        return basic_renderer::render_action_button($renderer, capquiz_urls::redirect(capquiz_urls::view_url()), get_string('home', 'capquiz'));
    }

    /**
     * @param renderer $renderer
     * @param \moodle_url $url
     * @param string $label
     * @param string $http_method
     * @return bool|string
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public static function render_action_button(renderer $renderer, \moodle_url $url, string $label, string $http_method = 'post') {
        $html = $renderer->render_from_template('capquiz/button', [
            'button' => [
                'primary' => true,
                'method' => $http_method,
                'url' => $url->out_as_local_url(false),
                'label' => $label
            ]
        ]);
        return $html;
    }

    /**
     * @param string $name
     * @param \moodle_url $link
     * @return \tabobject
     * @throws \coding_exception
     */
    private static function tab($name, $link) {
        $text = get_string($name, 'capquiz');
        return new \tabobject($name, $link, $text);
    }

    /**
     * @param string $activetab
     * @return string html
     * @throws \coding_exception
     */
    public static function tabs($activetab) {
        $tabs = [
            self::tab('view_question_list', capquiz_urls::view_question_list_url()),
            self::tab('view_leaderboard', capquiz_urls::view_leaderboard_url()),
            self::tab('view_configuration', capquiz_urls::view_configuration_url())
        ];
        return print_tabs([$tabs], $activetab, null, null, true);
    }
}
