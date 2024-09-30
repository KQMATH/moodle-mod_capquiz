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
 * This file defines a class used to load capquiz matchmaking strategies
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz;

use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/capquiz/classes/matchmaking/capquiz_matchmaking_strategy_registry.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/matchmaking/chronologic/chronologic_selector.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/matchmaking/n_closest/n_closest_selector.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/matchmaking/n_closest/n_closest_configuration_form.php');

/**
 * Class capquiz_matchmaking_strategy_loader
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_matchmaking_strategy_loader {

    /** @var capquiz $capquiz */
    private capquiz $capquiz;

    /** @var ?stdClass $record */
    private ?stdClass $record = null;

    /** @var capquiz_matchmaking_strategy_registry $registry */
    private capquiz_matchmaking_strategy_registry $registry;

    /** @var ?stdClass $configuration */
    private ?stdClass $configuration;

    /**
     * Constructor.
     *
     * @param capquiz $capquiz
     */
    public function __construct(capquiz $capquiz) {
        $this->capquiz = $capquiz;
        $this->registry = new capquiz_matchmaking_strategy_registry($capquiz);
        $this->load_configuration();
    }

    /**
     * Returns localized strategy name
     *
     * @param string $name
     */
    public static function localized_strategy_name(string $name): string {
        // TODO: This is a hack. The database records currently store the names, which makes localization hard.
        return match ($name) {
            'N-closest' => get_string('n_closest', 'capquiz'),
            'Chronological' => get_string('chronological', 'capquiz'),
            default => get_string('no_strategy_specified', 'capquiz'),
        };
    }

    /**
     * Returns the selected strategy
     *
     * @return ?capquiz_matchmaking_strategy
     */
    public function selector(): ?capquiz_matchmaking_strategy {
        if (!$this->record) {
            return null;
        }
        $strategy = $this->registry->selector($this->record->strategy);
        if ($this->configuration) {
            $strategy->configure($this->configuration);
        }
        return $strategy;
    }

    /**
     * Returns configuration form for the current matchmaking strategy
     *
     * @param moodle_url $url
     */
    public function configuration_form(moodle_url $url): mixed {
        if ($this->record && $this->configuration) {
            return $this->registry->configuration_form($this->record->strategy, $this->configuration, $url);
        }
        return null;
    }

    /**
     * Returns true if this instance has a strategy set
     */
    public function has_strategy(): bool {
        return $this->selector() !== null;
    }

    /**
     * Returns the name of the current strategy
     */
    public function current_strategy_name(): string {
        if ($this->record) {
            return $this->record->strategy;
        }
        return get_string('no_strategy_specified', 'capquiz');
    }

    /**
     * Configures teh current strategy
     *
     * @param stdClass $candidateconfig
     */
    public function configure_current_strategy(stdClass $candidateconfig): void {
        if (!$this->record) {
            return;
        }
        $selector = $this->selector();
        $selector->configure($candidateconfig);
        $config = $selector->configuration();
        $this->record->configuration = empty((array)$config) ? '' : $this->serialize($config);
        $this->update_configuration($this->record);
    }

    /**
     * Sets the default strategy
     */
    public function set_default_strategy(): void {
        $this->set_strategy($this->registry->default_selection_strategy());
    }

    /**
     * Sets strategy based on the strategy name
     *
     * @param string $strategy
     */
    public function set_strategy(string $strategy): void {
        $selector = $this->registry->selector($strategy);
        $record = new stdClass;
        $record->strategy = $strategy;
        $record->capquiz_id = $this->capquiz->id();
        $defaultconfig = $selector->default_configuration();
        $record->configuration = empty((array)$defaultconfig) ? '' : $this->serialize($defaultconfig);
        global $DB;
        if ($this->record) {
            $record->id = $this->record->id;
            $this->update_configuration($record);
        } else {
            $DB->insert_record('capquiz_question_selection', $record);
            $this->set_configuration($record);
        }
    }

    /**
     * Loads the strategy configuration from the database
     */
    private function load_configuration(): void {
        global $DB;
        $conditions = ['capquiz_id' => $this->capquiz->id()];
        $config = $DB->get_record('capquiz_question_selection', $conditions);
        if ($config) {
            $this->set_configuration($config);
        }
    }

    /**
     * Updates the strategy configuration and updates the database record
     *
     * @param stdClass $config
     */
    private function update_configuration(stdClass $config): void {
        global $DB;
        if ($DB->update_record('capquiz_question_selection', $config)) {
            $this->set_configuration($config);
        }
    }

    /**
     * Sets this configuration as a new configuration
     *
     * @param stdClass $record
     */
    private function set_configuration(stdClass $record): void {
        $this->record = $record;
        $this->configuration = $this->deserialize($record->configuration) ?: null;
    }

    /**
     * Returns the current configuration as a JSON string
     *
     * @param stdClass $configuration
     */
    private function serialize(stdClass $configuration): string {
        return json_encode($configuration);
    }

    /**
     * Takes in JSON encoded configuration string and returns a decoded configuration
     *
     * @param string $configuration
     */
    private function deserialize(string $configuration): mixed {
        return json_decode($configuration, false);
    }

}
