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

/**
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class basic_renderer {
    /**
     * @param renderer $renderer
     * @return string
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public static function render_home_button(renderer $renderer) {
        return basic_renderer::render_action_button($renderer, capquiz_urls::redirect(capquiz_urls::view_url()),
            get_string('home', 'capquiz'));
    }

    /**
     * @param renderer $renderer
     * @param \moodle_url $url
     * @param string $label
     * @param string $httpmethod The HTTP method to use for the form
     * @param string[] $params The keys are used as names
     * @return string
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public static function render_action_button(renderer $renderer, \moodle_url $url,
            string $label, string $httpmethod = 'post', array $params = []) {
        $paramobjects = [];
        foreach ($params as $name => $value) {
            $paramobjects = [
                'name' => $name,
                'value' => $value
            ];
        }
        $html = $renderer->render_from_template('capquiz/button', [
            'button' => [
                'primary' => true,
                'method' => $httpmethod,
                'url' => $url->out(false),
                'label' => $label,
                'params' => $paramobjects
            ]
        ]);
        return $html;
    }
}
