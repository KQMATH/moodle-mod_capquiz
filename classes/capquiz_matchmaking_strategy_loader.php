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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/capquiz/classes/matchmaking/capquiz_matchmaking_strategy_registry.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/matchmaking/chronologic/chronologic_selector.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/matchmaking/n_closest/n_closest_selector.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/matchmaking/n_closest/n_closest_configuration_form.php');

/**
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_matchmaking_strategy_loader {

    /** @var capquiz $capquiz */
    private $capquiz;

    /** @var \stdClass $record */
    private $record;

    /** @var capquiz_matchmaking_strategy_registry $registry */
    private $registry;

    /** @var \stdClass $configuration */
    private $configuration;

    public function __construct(capquiz $capquiz) {
        $this->capquiz = $capquiz;
        $this->registry = new capquiz_matchmaking_strategy_registry($capquiz);
        $this->load_configuration();
    }

    public static function localized_strategy_name($name) {
        // TODO: This is a hack. The database records currently store the names, which makes localization hard.
        switch ($name) {
            case 'N-closest':
                return get_string('n_closest', 'capquiz');
            case 'Chronological':
                return get_string('chronological', 'capquiz');
            default:
                return get_string('no_strategy_specified', 'capquiz');
        }
    }

    public function selector() {
        if (!$this->record) {
            return null;
        }
        $strategy = $this->registry->selector($this->record->strategy);
        $config = $this->configuration;
        if ($config) {
            $strategy->configure($config);
        }
        return $strategy;
    }

    public function configuration_form(\moodle_url $url) {
        if ($this->record && $this->configuration) {
            return $this->registry->configuration_form($this->record->strategy, $this->configuration, $url);
        }
        return null;
    }

    public function has_strategy() : bool {
        return $this->selector() != null;
    }

    public function current_strategy_name() : string {
        if ($this->record) {
            return $this->record->strategy;
        }
        return get_string('no_strategy_specified', 'capquiz');
    }

    public function configure_current_strategy(\stdClass $candidateconfig) {
        if (!$this->record) {
            return;
        }
        $selector = $this->selector();
        $selector->configure($candidateconfig);
        $configuration = $selector->configuration();
        if ($configuration) {
            $this->record->configuration = $this->serialize($configuration);
        } else {
            $this->record->configuration = '';
        }
        $this->update_configuration($this->record);
    }

    public function set_default_strategy() {
        $this->set_strategy($this->registry->default_selection_strategy());
    }

    public function set_strategy(string $strategy) {
        $selector = $this->registry->selector($strategy);
        $record = new \stdClass;
        $record->strategy = $strategy;
        $record->capquiz_id = $this->capquiz->id();
        $defaultconfig = $selector->default_configuration();
        if ($defaultconfig) {
            $record->configuration = $this->serialize($defaultconfig);
        } else {
            $record->configuration = '';
        }
        global $DB;
        if ($this->record) {
            $record->id = $this->record->id;
            $this->update_configuration($record);
        } else {
            $DB->insert_record('capquiz_question_selection', $record);
            $this->set_configuration($record);
        }
    }

    private function load_configuration() {
        global $DB;
        $conditions = ['capquiz_id' => $this->capquiz->id()];
        $configuration = $DB->get_record('capquiz_question_selection', $conditions);
        if ($configuration) {
            $this->set_configuration($configuration);
        }
    }

    private function update_configuration(\stdClass $configuration) {
        global $DB;
        if ($DB->update_record('capquiz_question_selection', $configuration)) {
            $this->set_configuration($configuration);
        }
    }

    private function set_configuration(\stdClass $record) {
        $this->record = $record;
        $configuration = $this->deserialize($record->configuration);
        if ($configuration) {
            $this->configuration = $configuration;
        } else {
            $this->configuration = null;
        }
    }

    private function serialize(\stdClass $configuration) : string {
        return json_encode($configuration);
    }

    private function deserialize(string $configuration) {
        return json_decode($configuration, false);
    }

}
