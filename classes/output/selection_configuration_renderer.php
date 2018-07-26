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
use mod_capquiz\capquiz_selection_strategy_registry;
use mod_capquiz\capquiz_urls;
use mod_capquiz\form\view\create_question_set_form;

defined('MOODLE_INTERNAL') || die();

require_once('../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');

class selection_configuration_renderer {
    private $capquiz;
    private $renderer;

    public function __construct(capquiz $capquiz, renderer $renderer) {
        $this->capquiz = $capquiz;
        $this->renderer = $renderer;
    }

    public function render() {
        $registry = $this->capquiz->selection_strategy_registry();
        if ($registry->has_strategy())
            return $this->render_configuration($registry);
        else
            return '<h3>No selection strategy has been specified</h3>';
    }

    private function render_configuration(capquiz_selection_strategy_registry $registry) {
        global $PAGE;
        $url = $PAGE->url;
        if ($form = $registry->configuration_form($url)) {
            if ($form_data = $form->get_data()) {
                $registry->configure_current_strategy($form_data);
                $url = capquiz_urls::view_selection_configuration_url();
                redirect($url);
            }
        } else
            $form = 'There is nothing to configure for this strategy';
        return $this->renderer->render_from_template('capquiz/selection_configuration', [
            'strategy' => $registry->current_strategy(),
            'form' => $form->render()

        ]);
    }
}
