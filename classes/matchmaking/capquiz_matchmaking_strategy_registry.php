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
 * This file defines a class acting as a registry for matchmaking strategies
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/capquiz/classes/capquiz_matchmaking_strategy.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/matchmaking/chronologic/chronologic_selector.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/matchmaking/n_closest/n_closest_selector.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/matchmaking/n_closest/n_closest_configuration_form.php');

/**
 * Class capquiz_matchmaking_strategy_registry
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_matchmaking_strategy_registry {

    /** @var capquiz $capquiz */
    private $capquiz;

    /** @var array $strategies */
    private $strategies;

    /**
     * capquiz_matchmaking_strategy_registry constructor.
     * @param capquiz $capquiz
     */
    public function __construct(capquiz $capquiz) {
        $this->capquiz = $capquiz;
        $this->register_selection_strategies();
    }

    /**
     * Returns the specified matchmaking strategy or throws an error if it does not exist
     *
     * @param string $strategy
     * @return capquiz_matchmaking_strategy
     * @throws \Exception
     */
    public function selector(string $strategy) {
        if ($value = $this->strategies[$strategy]) {
            return array_values($value)[0]();
        }
        $this->throw_strategy_exception($strategy);
    }

    /**
     * Returns a configuration form for the matchmaking strategy
     *
     * @param string $strategy
     * @param \stdClass $config
     * @param \moodle_url $url
     * @return mixed
     * @throws \Exception
     */
    public function configuration_form(string $strategy, \stdClass $config, \moodle_url $url) {
        $value = $this->strategies[$strategy];
        if ($value) {
            $configfunc = array_values($value)[1];
            return $configfunc($url, $config);
        }
        $this->throw_strategy_exception($strategy);
    }

    /**
     * Returns true if the registry has the specified strategy
     *
     * @param string $strategy
     * @return bool
     */
    public function has_strategy(string $strategy) : bool {
        if ($value = $this->strategies[$strategy]) {
            return true;
        }
        return false;
    }

    /**
     * Returns the default selection strategy
     *
     * @return string
     */
    public function default_selection_strategy() : string {
        // The default selection strategy is added first.
        // Modify capquiz_matchmaking_strategy_registry::register_selection_strategies() to change this.
        $selectionstrategies = $this->selection_strategies();
        return reset($selectionstrategies);
    }

    /**
     * Returns all selection strategies' names
     *
     * @return string[]
     */
    public function selection_strategies() : array {
        $names = [];
        foreach (array_keys($this->strategies) as $value) {
            $names[] = $value;
        }
        return $names;
    }

    /**
     * Registers the selection strategies, the first registered will be the default strategy
     */
    private function register_selection_strategies() {
        // The first listed will be selected by default when creating a new activity.
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

    /**
     * Creates and throws a strategy exception
     *
     * @param string $strategy
     * @throws \Exception
     */
    private function throw_strategy_exception(string $strategy) {
        $msg = "The specified strategy '$strategy' does not exist.";
        $msg .= " Options are {'" . implode("', '", $this->selection_strategies());
        $msg .= "'}. This issue must be fixed by a programmer";
        throw new \Exception($msg);
    }
}
