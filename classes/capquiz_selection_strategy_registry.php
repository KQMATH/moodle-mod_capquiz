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
    private $db_entry;
    private $strategies;
    private $configuration;

    public function __construct(capquiz $capquiz) {
        $this->capquiz = $capquiz;
        $this->load_configuration();
        $this->register_selection_strategies();
    }

    public function selector() {
        if ($db_entry = $this->db_entry)
            return $this->selector_for_strategy($db_entry->strategy, $this->configuration);
        return null;
    }

    public function configuration_form(\moodle_url $url) {
        if ($db_entry = $this->db_entry)
            return $this->configuration_form_for_strategy($db_entry->strategy, $this->configuration, $url);
        return null;
    }

    public function current_strategy() {
        if ($db_entry = $this->db_entry)
            return $db_entry->strategy;
        return "No strategy specified";
    }

    public function has_strategy() {
        if ($db_entry = $this->db_entry) {
            return $this->selector() != null;
        }
        return false;
    }

    public function configure_current_strategy(\stdClass $candidate_configuration) {
        if ($db_entry = $this->db_entry) {
            $selector = $this->selector_for_strategy($db_entry->strategy, $candidate_configuration);
            if ($configuration = $selector->configuration())
                $db_entry->configuration = $this->serialize($configuration);
            else {
                $db_entry->configuration = new \stdClass;
            }
            $this->update_configuration($db_entry);
        }
    }

    public function set_strategy(string $strategy) {
        $selector = $this->selector_for_strategy($strategy, new \stdClass);
        if ($this->db_entry)
            $db_entry = $this->db_entry;
        else
            $db_entry = new \stdClass;
        $db_entry->strategy = $strategy;
        $db_entry->capquiz_id = $this->capquiz->id();
        if ($default_configuration = $selector->default_configuration()) {
            $db_entry->configuration = $this->serialize($default_configuration);
        } else {
            $db_entry->configuration = new \stdClass;
        }
        global $DB;
        if ($this->db_entry) {
            $this->update_configuration($db_entry);
        } else {
            $DB->insert_record(database_meta::$table_capquiz_question_selection, $db_entry);
        }
    }

    public function selection_strategies() {
        $names = [];
        foreach (array_keys($this->strategies) as $value) {
            $names[] = $value;
        }
        return $names;
    }

    private function load_configuration() {
        $conditions = [
            database_meta::$field_capquiz_id => $this->capquiz->id()
        ];
        global $DB;
        if ($db_entry = $DB->get_record(database_meta::$table_capquiz_question_selection, $conditions)) {
            $this->db_entry = $db_entry;
            $this->configuration = $this->deserialize($db_entry->configuration);
        }
    }

    private function update_configuration(\stdClass $configuration) {

        global $DB;
        if ($DB->update_record(database_meta::$table_capquiz_question_selection, $configuration)) {
            $this->db_entry = $configuration;
        }
    }

    private function register_selection_strategies() {
        $capquiz = $this->capquiz;
        $this->strategies = [
            'Chronological' => [
                function (\stdClass $configuration) use ($capquiz) {
                    return new chronologic_selector($capquiz, $configuration);
                },
                function (\moodle_url $url, \stdClass $configuration) use ($capquiz) {
                    return null;
                }],

            'N-closest' => [
                function (\stdClass $configuration) use ($capquiz) {
                    return new n_closest_selector($capquiz, $configuration);
                },
                function (\moodle_url $url, \stdClass $configuration) use ($capquiz) {
                    return new n_closest_configuration_form($capquiz, $configuration, $url);
                }
            ]
        ];
    }

    private function selector_for_strategy(string $strategy, \stdClass $configuration) {
        if ($value = $this->strategies[$strategy]) {
            return array_values($value)[0]($configuration);
        }
        $this->throw_strategy_exception($strategy);
    }

    private function configuration_form_for_strategy(string $strategy, \stdClass $configuration, \moodle_url $url) {
        if ($value = $this->strategies[$strategy]) {
            return array_values($value)[1]($url, $configuration);
        }
        $this->throw_strategy_exception($strategy);
    }

    private function serialize(\stdClass $configuration) {
        return json_encode($configuration);
    }

    private function deserialize(string $configuration) {
        return json_decode($configuration);
    }

    private function throw_strategy_exception(string $strategy) {
        $msg = "The specified strategy '$strategy' does not exist.";
        $msg .= " Options are {'" . implode("', '", $this->selection_strategies());
        $msg .= "'}. This issue must be fixed by a programmer";
        throw new \Exception($msg);
    }
}
