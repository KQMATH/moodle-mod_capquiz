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

require_once($CFG->dirroot . '/mod/capquiz/classes/rating_system/elo_rating/elo_rating_system.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/rating_system/elo_rating/elo_rating_system_form.php');

/**
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_rating_system_registry {

    /** @var callable[][] $systems */
    private $systems;

    public function __construct() {
        $this->register_rating_systems();
    }

    public function rating_system(string $system) {
        if ($value = $this->systems[$system]) {
            return array_values($value)[0]();
        }
        $this->throw_rating_system_exception($system);
    }

    public function configuration_form(string $system, \stdClass $configuration, \moodle_url $url) {
        if ($value = $this->systems[$system]) {
            $configfunc = array_values($value)[1];
            return $configfunc($url, $configuration);
        }
        $this->throw_rating_system_exception($system);
    }

    public function has_rating_system(string $system) : bool {
        return isset($this->systems[$system]);
    }

    public function default_rating_system() : string {
        // Default rating system is added first.
        // Modify caquiz_rating_system_registry::register_rating_systems() to change this.
        $ratingsystems = $this->rating_systems();
        return reset($ratingsystems);
    }

    /**
     * @return string[]
     */
    public function rating_systems() : array {
        $names = [];
        foreach (array_keys($this->systems) as $value) {
            $names[] = $value;
        }
        return $names;
    }

    private function register_rating_systems() {
        // The first listed will be selected by default when creating a new activity.
        $this->systems = [
            'Elo' => [
                function () {
                    return new elo_rating_system();
                },
                function (\moodle_url $url, \stdClass $configuration) {
                    return new elo_rating_system_form($configuration, $url);
                }
            ]
        ];
    }

    private function throw_rating_system_exception(string $system) {
        $msg = "The specified rating system '$system' does not exist.";
        $msg .= " Options are {'" . implode("', '", $this->rating_systems());
        $msg .= "'}. This issue must be fixed by a programmer";
        throw new \Exception($msg);
    }
}
