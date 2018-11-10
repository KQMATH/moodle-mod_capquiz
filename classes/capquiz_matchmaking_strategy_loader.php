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

require_once($CFG->dirroot . '/mod/capquiz/classes/matchmaking/capquiz_matchmaking_strategy_registry.php');
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
class capquiz_matchmaking_strategy_loader {

    /** @var capquiz $capquiz */
    private $capquiz;

    /** @var \stdClass $db_entry */
    private $db_entry;

    /** @var capquiz_matchmaking_strategy_registry $registry */
    private $registry;

    /** @var \stdClass $configuration */
    private $configuration;

    public function __construct(capquiz $capquiz) {
        $this->capquiz = $capquiz;
        $this->registry = new capquiz_matchmaking_strategy_registry($capquiz);
        $this->load_configuration();
    }

    public function selector() /*: ?capquiz_matchmaking_strategy*/ {
        if ($db_entry = $this->db_entry) {
            $strategy = $this->registry->selector($db_entry->strategy);
            if ($config = $this->configuration) {
                $strategy->configure($config);
            }
            return $strategy;
        }
        return null;
    }

    public function configuration_form(\moodle_url $url) /*: ?\moodleform*/ {
        if ($db_entry = $this->db_entry) {
            if ($config = $this->configuration) {
                return $this->registry->configuration_form($db_entry->strategy, $config, $url);
            }
        }
        return null;
    }

    public function has_strategy() : bool {
        if ($db_entry = $this->db_entry) {
            return $this->selector() != null;
        }
        return false;
    }

    public function current_strategy_name() : string {
        if ($db_entry = $this->db_entry) {
            return $db_entry->strategy;
        }
        return 'No strategy specified';
    }

    public function configure_current_strategy(\stdClass $candidate_configuration) : void {
        if ($db_entry = $this->db_entry) {
            $selector = $this->selector($db_entry->strategy);
            $selector->configure($candidate_configuration);
            if ($configuration = $selector->configuration()) {
                $db_entry->configuration = $this->serialize($configuration);
            } else {
                $db_entry->configuration = '';
            }
            $this->update_configuration($db_entry);
        }
    }

    public function set_default_strategy() : void {
        $this->set_strategy($this->registry->default_selection_strategy());
    }

    public function set_strategy(string $strategy) : void {
        $selector = $this->registry->selector($strategy);
        $db_entry = new \stdClass;
        $db_entry->strategy = $strategy;
        $db_entry->capquiz_id = $this->capquiz->id();
        if ($default_configuration = $selector->default_configuration()) {
            $db_entry->configuration = $this->serialize($default_configuration);
        } else {
            $db_entry->configuration = '';
        }
        global $DB;
        if ($this->db_entry) {
            $db_entry->id = $this->db_entry->id;
            $this->update_configuration($db_entry);
        } else {
            $DB->insert_record(database_meta::$table_capquiz_question_selection, $db_entry);
            $this->set_configuration($db_entry);
        }
    }

    private function load_configuration() : void {
        global $DB;
        $conditions = [
            database_meta::$field_capquiz_id => $this->capquiz->id()
        ];
        if ($configuration = $DB->get_record(database_meta::$table_capquiz_question_selection, $conditions)) {
            $this->set_configuration($configuration);
        }
    }

    private function update_configuration(\stdClass $configuration) : void {
        global $DB;
        if ($DB->update_record(database_meta::$table_capquiz_question_selection, $configuration)) {
            $this->set_configuration($configuration);
        }
    }

    private function set_configuration(\stdClass $database_entry) {
        $this->db_entry = $database_entry;
        if ($configuration = $this->deserialize($database_entry->configuration)) {
            $this->configuration = $configuration;
        } else {
            $this->configuration = null;
        }
    }

    private function serialize(\stdClass $configuration) : string {
        return json_encode($configuration);
    }

    private function deserialize(string $configuration) /*: ?\stdClass*/ {
        return json_decode($configuration, false);
    }

}
