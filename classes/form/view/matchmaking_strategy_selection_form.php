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

namespace mod_capquiz\form\view;

use mod_capquiz\capquiz;
use mod_capquiz\capquiz_matchmaking_strategy_loader;
use mod_capquiz\capquiz_matchmaking_strategy_registry;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class matchmaking_strategy_selection_form extends \moodleform {

    /** @var capquiz $capquiz */
    private $capquiz;

    public function __construct(capquiz $capquiz, \moodle_url $url) {
        $this->capquiz = $capquiz;
        parent::__construct($url);
    }

    public function definition() {
        $form = $this->_form;
        $loader = new capquiz_matchmaking_strategy_loader($this->capquiz);
        $registry = new capquiz_matchmaking_strategy_registry($this->capquiz);
        $strategies = $registry->selection_strategies();
        $index = 0;
        $selectedindex = -1;
        $radioarray = [];
        foreach ($strategies as $strategy) {
            if ($loader->current_strategy_name() === $strategy) {
                $selectedindex = $index;
            }
            $localized = capquiz_matchmaking_strategy_loader::localized_strategy_name($strategy);
            $radioarray[] = $form->createElement('radio', 'strategy', '', $localized, $index, [$strategy]);
            $index++;
        }
        $form->addGroup($radioarray, 'radioar', '', '</br>', false);
        $this->add_action_buttons(false);
        if ($selectedindex > -1) {
            $form->setDefault('strategy', $selectedindex);
        }
    }

    public function validations($data, $files) {
        return [];
    }

}
