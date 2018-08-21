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

/**
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class matchmaking_configuration_renderer {
    private $capquiz;
    private $renderer;
    private $registry;

    public function __construct(capquiz $capquiz, renderer $renderer) {
        $this->capquiz = $capquiz;
        $this->renderer = $renderer;
        $this->registry = $this->capquiz->selection_strategy_loader();
    }

    public function render() {
        if ($this->registry->has_strategy()) {
            return $this->render_configuration();
        } else {
            return '<h3>' . get_string('no_matchmaing_strategy_selected', 'capquiz') . '</h3>';
        }
    }

    private function render_configuration() {
        $html = $this->render_form();
        return $this->renderer->render_from_template('capquiz/matchmaking_configuration', [
            'strategy' => $this->registry->current_strategy_name(),
            'form' => $html

        ]);
    }

    private function render_form() {
        global $PAGE;
        $url = $PAGE->url;
        if ($form = $this->registry->configuration_form($url)) {
            if ($form_data = $form->get_data()) {
                $this->registry->configure_current_strategy($form_data);
                $url = capquiz_urls::view_matchmaking_configuration_url();
                redirect($url);
            }
            return $form->render();
        }
        return 'There is nothing to configure for this strategy';
    }
}
