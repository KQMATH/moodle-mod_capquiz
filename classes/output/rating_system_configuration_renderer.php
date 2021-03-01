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
 * This file defines a class used to render the rating system configuration view
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz\output;

use mod_capquiz\capquiz;
use mod_capquiz\capquiz_rating_system_loader;
use mod_capquiz\capquiz_urls;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/editlib.php');

/**
 * Class rating_system_configuration_renderer
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rating_system_configuration_renderer {

    /** @var capquiz $capquiz */
    private $capquiz;

    /** @var renderer $renderer */
    private $renderer;

    /** @var capquiz_rating_system_loader $registry */
    private $registry;

    /**
     * rating_system_configuration_renderer constructor.
     * @param capquiz $capquiz
     * @param renderer $renderer
     */
    public function __construct(capquiz $capquiz, renderer $renderer) {
        $this->capquiz = $capquiz;
        $this->renderer = $renderer;
        $this->registry = new capquiz_rating_system_loader($capquiz);
    }

    /**
     * Calls submethod that renders the rating_system_configuration view
     *
     * @return bool|string
     * @throws \moodle_exception
     */
    public function render() {
        if ($this->registry->has_rating_system()) {
            return $this->render_configuration();
        } else {
            return '<h3>No rating system has been specified</h3>';
        }
    }

    /**
     * Renders the rating configuration view
     *
     * @return bool|string
     * @throws \moodle_exception
     */
    private function render_configuration() {
        $html = $this->render_form();
        return $this->renderer->render_from_template('capquiz/rating_system_configuration', [
            'strategy' => $this->registry->current_rating_system_name(),
            'form' => $html
        ]);
    }

    /**
     * Renders the rating configuration form
     *
     * @return string
     * @throws \moodle_exception
     */
    private function render_form() {
        global $PAGE;
        $url = $PAGE->url;
        if ($form = $this->registry->configuration_form($url)) {
            $formdata = $form->get_data();
            if ($formdata) {
                $this->registry->configure_current_rating_system($formdata);
                redirect(capquiz_urls::view_rating_system_url());
            }
            return $form->render();
        }
        return 'There is nothing to configure for this rating system';
    }
}
