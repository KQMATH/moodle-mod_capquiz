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
 * This file defines a class used as a registry for the rating system
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz;

use coding_exception;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/capquiz/classes/rating_system/elo_rating/elo_rating_system.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/rating_system/elo_rating/elo_rating_system_form.php');

/**
 * Class capquiz_rating_system_registry
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_rating_system_registry {

    /** @var callable[][] $systems */
    private array $systems;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->register_rating_systems();
    }

    /**
     * Returns rating system
     *
     * @param string $system
     */
    public function rating_system(string $system): capquiz_rating_system {
        $value = $this->systems[$system];
        if (!$value) {
            $this->throw_rating_system_exception($system);
        }
        return array_values($value)[0]();
    }

    /**
     * Returns configuration form
     *
     * @param string $system
     * @param stdClass $configuration
     * @param moodle_url $url
     */
    public function configuration_form(string $system, stdClass $configuration, moodle_url $url) {
        $value = $this->systems[$system];
        if ($value) {
            $configfunc = array_values($value)[1];
            return $configfunc($url, $configuration);
        }
        $this->throw_rating_system_exception($system);
    }

    /**
     * Checks if this instance has a rating system
     *
     * @param string $system
     */
    public function has_rating_system(string $system): bool {
        return isset($this->systems[$system]);
    }

    /**
     * Returns the default rating system
     */
    public function default_rating_system(): string {
        // Default rating system is added first.
        // Modify caquiz_rating_system_registry::register_rating_systems() to change this.
        $ratingsystems = $this->rating_systems();
        return reset($ratingsystems);
    }

    /**
     * Returns the names of all rating systems.
     *
     * @return string[]
     */
    public function rating_systems(): array {
        return array_keys($this->systems);
    }

    /**
     * Registers rating systems
     */
    private function register_rating_systems(): void {
        // The first listed will be selected by default when creating a new activity.
        $this->systems = [
            'Elo' => [
                fn() => new elo_rating_system(),
                fn(moodle_url $url, stdClass $config) => new elo_rating_system_form($config, $url),
            ],
        ];
    }

    /**
     * Creates and throws exception
     *
     * @param string $system
     */
    private function throw_rating_system_exception(string $system) {
        $msg = "The specified rating system '$system' does not exist.";
        $msg .= " Options are {'" . implode("', '", $this->rating_systems());
        $msg .= "'}. This issue must be fixed by a programmer";
        throw new coding_exception($msg);
    }
}
