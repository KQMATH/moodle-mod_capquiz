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

require_once($CFG->dirroot . '/mod/capquiz/classes/rating_system/capquiz_rating_system_registry.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/rating_system/elo_rating/elo_rating_system.php');

/**
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_rating_system_loader {

    /** @var capquiz $capquiz */
    private $capquiz;

    /** @var \stdClass $record */
    private $record;

    /** @var capquiz_rating_system_registry $registry */
    private $registry;

    /** @var \stdClass $configuration */
    private $configuration;

    public function __construct(capquiz $capquiz) {
        $this->capquiz = $capquiz;
        $this->registry = new capquiz_rating_system_registry();
        $this->load_configuration();
    }

    public function rating_system() {
        if (!$this->record) {
            return null;
        }
        $system = $this->registry->rating_system($this->record->rating_system);
        if ($this->configuration) {
            $system->configure($this->configuration);
        }
        return $system;
    }

    public function has_rating_system() : bool {
        return $this->rating_system() !== null;
    }

    public function configuration_form(\moodle_url $url) {
        if ($this->record && $this->configuration) {
            return $this->registry->configuration_form($this->record->rating_system, $this->configuration, $url);
        }
        return null;
    }

    public function current_rating_system_name() : string {
        if ($this->record) {
            return $this->record->rating_system;
        }
        return 'No rating system specified';
    }

    public function configure_current_rating_system(\stdClass $candidateconfig) {
        if (!$this->record) {
            return;
        }
        $system = $this->rating_system();
        $system->configure($candidateconfig);
        $configuration = $system->configuration();
        if ($configuration) {
            $this->record->configuration = $this->serialize($configuration);
        } else {
            $this->record->configuration = '';
        }
        $this->update_configuration($this->record);
    }

    public function set_default_rating_system() {
        $this->set_rating_system($this->registry->default_rating_system());
    }

    public function set_rating_system(string $ratingsystem) {
        global $DB;
        $system = $this->registry->rating_system($ratingsystem);
        $record = new \stdClass;
        $record->rating_system = $ratingsystem;
        $record->capquiz_id = $this->capquiz->id();
        $defaultconfig = $system->default_configuration();
        if ($defaultconfig) {
            $record->configuration = $this->serialize($defaultconfig);
        } else {
            $record->configuration = '';
        }
        if ($this->record) {
            $record->id = $this->record->id;
            $this->update_configuration($record);
        } else {
            $DB->insert_record(database_meta::$tableratingsystem, $record);
            $this->set_configuration($record);
        }
    }

    private function load_configuration() {
        global $DB;
        $conditions = [database_meta::$fieldcapquizid => $this->capquiz->id()];
        $configuration = $DB->get_record(database_meta::$tableratingsystem, $conditions);
        if ($configuration) {
            $this->set_configuration($configuration);
        }
    }

    private function update_configuration(\stdClass $configuration) {
        global $DB;
        if ($DB->update_record(database_meta::$tableratingsystem, $configuration)) {
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
