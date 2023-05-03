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
 * This file defines a class used to render the rating system selection form
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz\output;

use mod_capquiz\capquiz;
use mod_capquiz\capquiz_rating_system_loader;
use mod_capquiz\capquiz_rating_system_registry;
use mod_capquiz\capquiz_urls;
use mod_capquiz\form\view\rating_system_selection_form;

/**
 * Class rating_system_selection_renderer
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rating_system_selection_renderer {

    /** @var \moodle_url $url */
    private $url;

    /** @var capquiz $capquiz */
    private $capquiz;

    /** @var renderer $renderer */
    private $renderer;

    /** @var \moodle_page $page */
    private $page;

    /**
     * rating_system_selection_renderer constructor.
     *
     * @param capquiz $capquiz
     * @param renderer $renderer
     */
    public function __construct(capquiz $capquiz, renderer $renderer) {
        $this->capquiz = $capquiz;
        $this->renderer = $renderer;
        $this->url = capquiz_urls::view_rating_system_url();
        $this->page = $capquiz->get_page();
    }

    /**
     * Sets redirect url
     *
     * @param \moodle_url $url
     */
    public function set_redirect_url(\moodle_url $url) {
        $this->url = $url;
    }

    /**
     * Renders the rating system selection form
     * @return bool|string
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function render() {
        $url = $this->page->url;
        $form = new rating_system_selection_form($this->capquiz, $url);
        $formdata = $form->get_data();
        if ($formdata) {
            $registry = new capquiz_rating_system_registry();
            $loader = new capquiz_rating_system_loader($this->capquiz);
            $loader->set_rating_system($registry->rating_systems()[$formdata->rating_system]);
            redirect($this->url);
        }

        return $this->renderer->render_from_template('capquiz/rating_system_selection', [
            'form' => $form->render()
        ]);
    }

}
