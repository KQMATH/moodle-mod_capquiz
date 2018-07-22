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
use mod_capquiz\form\view\configure_capquiz_form;
use function mod_capquiz\redirect_to_dashboard;

defined('MOODLE_INTERNAL') || die();

require_once('../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');

class configuration_renderer {
    private $capquiz;
    private $renderer;

    public function __construct(capquiz $capquiz, renderer $renderer) {
        $this->capquiz = $capquiz;
        $this->renderer = $renderer;
    }

    public function render() {
        global $PAGE;
        $url = $PAGE->url;
        $form = new configure_capquiz_form($this->capquiz, $url);
        if ($form->is_cancelled()) {
            redirect_to_dashboard($this->capquiz);
        }
        if ($form_data = $form->get_data()) {
            $this->capquiz->configure($form_data);
            redirect_to_dashboard($this->capquiz);
        }
        $form_html = $form->render();
        $tabs = basic_renderer::tabs('view_configuration');
        $configuration = $this->renderer->render_from_template('capquiz/configuration', [
            'form' => $form_html
        ]);
        return $tabs . $configuration;
    }
}
