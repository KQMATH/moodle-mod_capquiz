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

require_once($CFG->dirroot . '/mod/capquiz/classes/capquiz_question_selector.php');

require_once($CFG->dirroot . '/mod/capquiz/classes/question_selectors/chronologic/chronologic_selector.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/question_selectors/n_closest/n_closest_selector.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/question_selectors/n_closest/n_closest_configuration_form.php');

defined('MOODLE_INTERNAL') || die();

class capquiz_selection_strategy_registry {

    private $capquiz;
    private $strategies;

    public function __construct(capquiz $capquiz) {
        $this->capquiz = $capquiz;
        $this->register_selection_strategies();
    }


    public function selector(string $strategy) {
        if ($value = $this->strategies[$strategy]) {
            return array_values($value)[0]();
        }
        $this->throw_strategy_exception($strategy);
    }

    public function configuration_form(string $strategy, \stdClass $configuration, \moodle_url $url) {
        if ($value = $this->strategies[$strategy]) {
            return array_values($value)[1]($url, $configuration);
        }
        $this->throw_strategy_exception($strategy);
    }

    public function has_strategy(string $strategy) {
        if ($value = $this->strategies[$strategy]) {
            return true;
        }
        return false;
    }

    public function selection_strategies() {
        $names = [];
        foreach (array_keys($this->strategies) as $value) {
            $names[] = $value;
        }
        return $names;
    }

    private function register_selection_strategies() {
        $capquiz = $this->capquiz;
        $this->strategies = [
            'Chronological' => [
                function () use ($capquiz) {
                    return new chronologic_selector($capquiz);
                },
                function (\moodle_url $url, \stdClass $configuration) use ($capquiz) {
                    return null;
                }
            ],

            'N-closest' => [
                function () use ($capquiz) {
                    return new n_closest_selector($capquiz);
                },
                function (\moodle_url $url, \stdClass $configuration) use ($capquiz) {
                    return new n_closest_configuration_form($capquiz, $configuration, $url);
                }
            ]
        ];
    }

    private function throw_strategy_exception(string $strategy) {
        $msg = "The specified strategy '$strategy' does not exist.";
        $msg .= " Options are {'" . implode("', '", $this->selection_strategies());
        $msg .= "'}. This issue must be fixed by a programmer";
        throw new \Exception($msg);
    }
}
