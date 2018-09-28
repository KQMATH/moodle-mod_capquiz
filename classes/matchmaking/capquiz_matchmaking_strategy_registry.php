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

namespace mod_capquiz;

require_once($CFG->dirroot . '/mod/capquiz/classes/capquiz_matchmaking_strategy.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/matchmaking/chronologic/chronologic_selector.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/matchmaking/n_closest/n_closest_selector.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/matchmaking/n_closest/n_closest_configuration_form.php');

defined('MOODLE_INTERNAL') || die();

/**
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_matchmaking_strategy_registry {

    /** @var capquiz $capquiz */
    private $capquiz;

    /** @var  */
    private $strategies;

    public function __construct(capquiz $capquiz) {
        $this->capquiz = $capquiz;
        $this->register_selection_strategies();
    }

    public function selector(string $strategy) : ?capquiz_matchmaking_strategy {
        if ($value = $this->strategies[$strategy]) {
            return array_values($value)[0]();
        }
        $this->throw_strategy_exception($strategy);
    }

    public function configuration_form(string $strategy, \stdClass $configuration, \moodle_url $url) : ?\moodleform {
        if ($value = $this->strategies[$strategy]) {
            return array_values($value)[1]($url, $configuration);
        }
        $this->throw_strategy_exception($strategy);
    }

    public function has_strategy(string $strategy) : bool {
        if ($value = $this->strategies[$strategy]) {
            return true;
        }
        return false;
    }

    public function default_selection_strategy() : string {
        // The default selection strategy is added first.
        // Modify capquiz_matchmaking_strategy_registry::register_selection_strategies() to change this.
        $selection_strategies = $this->selection_strategies();
        return reset($selection_strategies);
    }

    /**
     * @return string[]
     */
    public function selection_strategies() : array {
        $names = [];
        foreach (array_keys($this->strategies) as $value) {
            $names[] = $value;
        }
        return $names;
    }

    private function register_selection_strategies() : void {
        //The first listed will be selected by default when creating a new activity
        $capquiz = $this->capquiz;
        $this->strategies = [
            'N-closest' => [
                function () use ($capquiz) {
                    return new n_closest_selector($capquiz);
                },
                function (\moodle_url $url, \stdClass $configuration) {
                    return new n_closest_configuration_form($configuration, $url);
                }
            ],
            'Chronological' => [
                function () use ($capquiz) {
                    return new chronologic_selector();
                },
                function (\moodle_url $url, \stdClass $configuration) {
                    return null;
                }
            ]
        ];
    }

    private function throw_strategy_exception(string $strategy) : void {
        $msg = "The specified strategy '$strategy' does not exist.";
        $msg .= " Options are {'" . implode("', '", $this->selection_strategies());
        $msg .= "'}. This issue must be fixed by a programmer";
        throw new \Exception($msg);
    }
}
