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
 * This file defines a class used to load capquiz rating systems
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2019 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz;

use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/capquiz/classes/rating_system/capquiz_rating_system_registry.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/rating_system/elo_rating/elo_rating_system.php');

/**
 * capquiz_rating_system_loader class
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_rating_system_loader {

    /** @var capquiz $capquiz */
    private capquiz $capquiz;

    /** @var ?stdClass $record */
    private ?stdClass $record = null;

    /** @var capquiz_rating_system_registry $registry */
    private capquiz_rating_system_registry $registry;

    /** @var ?stdClass $configuration */
    private ?stdClass $configuration;

    /**
     * Constructor.
     *
     * @param capquiz $capquiz
     */
    public function __construct(capquiz $capquiz) {
        $this->capquiz = $capquiz;
        $this->registry = new capquiz_rating_system_registry();
        $this->load_configuration();
    }

    /**
     * Returns rating system
     */
    public function rating_system(): ?capquiz_rating_system {
        if (!$this->record) {
            return null;
        }
        $system = $this->registry->rating_system($this->record->rating_system);
        if ($this->configuration) {
            $system->configure($this->configuration);
        }
        return $system;
    }

    /**
     * Checks if this instance has a rating system
     *
     * @return bool
     */
    public function has_rating_system(): bool {
        return $this->rating_system() !== null;
    }

    /**
     * Returns configuration form
     *
     * @param moodle_url $url
     */
    public function configuration_form(moodle_url $url): mixed {
        if ($this->record && $this->configuration) {
            return $this->registry->configuration_form($this->record->rating_system, $this->configuration, $url);
        }
        return null;
    }

    /**
     * Returns the current rating systems name
     *
     * @return string rating system name
     */
    public function current_rating_system_name(): string {
        if ($this->record) {
            return $this->record->rating_system;
        }
        return 'No rating system specified';
    }

    /**
     * Configure the current rating system.
     *
     * @param stdClass $candidateconfig
     */
    public function configure_current_rating_system(stdClass $candidateconfig): void {
        if (!$this->record) {
            return;
        }
        $system = $this->rating_system();
        $system->configure($candidateconfig);
        $config = $system->configuration();
        $this->record->configuration = empty((array)$config) ? '' : $this->serialize($config);
        $this->update_configuration($this->record);
    }

    /**
     * Set the default rating system.
     */
    public function set_default_rating_system(): void {
        $this->set_rating_system($this->registry->default_rating_system());
    }

    /**
     * Set the rating system
     *
     * @param string $ratingsystem
     */
    public function set_rating_system(string $ratingsystem): void {
        global $DB;
        $system = $this->registry->rating_system($ratingsystem);
        $record = new stdClass;
        $record->rating_system = $ratingsystem;
        $record->capquiz_id = $this->capquiz->id();
        $defaultconfig = $system->default_configuration();
        $record->configuration = empty((array)$defaultconfig) ? '' : $this->serialize($defaultconfig);
        if ($this->record) {
            $record->id = $this->record->id;
            $this->update_configuration($record);
        } else {
            $DB->insert_record('capquiz_rating_system', $record);
            $this->set_configuration($record);
        }
    }

    /**
     * Loads this instances configuration
     */
    private function load_configuration(): void {
        global $DB;
        $configuration = $DB->get_record('capquiz_rating_system', ['capquiz_id' => $this->capquiz->id()]);
        if ($configuration) {
            $this->set_configuration($configuration);
        }
    }

    /**
     * Updates this instances configuration as well as updates the database
     *
     * @param stdClass $configuration
     */
    private function update_configuration(stdClass $configuration): void {
        global $DB;
        if ($DB->update_record('capquiz_rating_system', $configuration)) {
            $this->set_configuration($configuration);
        }
    }

    /**
     * Sets this instances configuration
     *
     * @param stdClass $record
     */
    private function set_configuration(stdClass $record): void {
        $this->record = $record;
        $this->configuration = $this->deserialize($record->configuration);
    }

    /**
     * Serializes the input configuration object
     *
     * @param stdClass $configuration the configuration to be serialized
     * @return string json string representing the input configuration
     */
    private function serialize(stdClass $configuration): string {
        return json_encode($configuration);
    }

    /**
     * Deserializes JSON formatted configuration string
     *
     * @param string $configuration The JSON string to be deserialized back into a configuration object
     */
    private function deserialize(string $configuration): mixed {
        return json_decode($configuration, false);
    }

}
