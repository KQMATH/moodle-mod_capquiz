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

require_once($CFG->dirroot . '/mod/capquiz/classes/rating_system/elo_rating/elo_rating_system.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/rating_system/elo_rating/elo_rating_system_form.php');

defined('MOODLE_INTERNAL') || die();

class capquiz_rating_system_registry {

    private $capquiz;
    private $systems;

    public function __construct(capquiz $capquiz) {
        $this->capquiz = $capquiz;
        $this->register_rating_systems();
    }


    public function rating_system(string $rating_system) {
        if ($value = $this->systems[$rating_system]) {
            return array_values($value)[0]();
        }
        $this->throw_rating_system_exception($rating_system);
    }

    public function configuration_form(string $rating_system, \stdClass $configuration, \moodle_url $url) {
        if ($value = $this->systems[$rating_system]) {
            return array_values($value)[1]($url, $configuration);
        }
        $this->throw_rating_system_exception($rating_system);
    }

    public function has_rating_system(string $rating_system) {
        if ($value = $this->systems[$rating_system]) {
            return true;
        }
        return false;
    }

    public function rating_systems() {
        $names = [];
        foreach (array_keys($this->systems) as $value) {
            $names[] = $value;
        }
        return $names;
    }

    private function register_rating_systems() {
        //The first listed will be selected by default when creating a new activity
        $capquiz = $this->capquiz;
        $this->systems = [
            'Elo' => [
                function () use ($capquiz) {
                    return new elo_rating_system($capquiz);
                },
                function (\moodle_url $url, \stdClass $configuration) {
                    return new elo_rating_system_form($configuration, $url);
                }
            ]
        ];
    }

    private function throw_rating_system_exception(string $rating_system) {
        $msg = "The specified rating system '$rating_system' does not exist.";
        $msg .= " Options are {'" . implode("', '", $this->rating_systems());
        $msg .= "'}. This issue must be fixed by a programmer";
        throw new \Exception($msg);
    }
}
