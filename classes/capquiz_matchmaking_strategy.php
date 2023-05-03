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
 * This file defines a class that represents a capquiz matchmaking strategy
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz;

/**
 * Class capquiz_matchmaking_strategy
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class capquiz_matchmaking_strategy {

    /**
     * Sets a new matchmaking strategy configuration
     *
     * @param \stdClass $configuration
     * @return mixed
     */
    abstract public function configure(\stdClass $configuration);

    /**
     * Returns the current configuration
     *
     * @return mixed
     */
    abstract public function configuration();

    /**
     * Returns the default configuration
     *
     * @return mixed
     */
    abstract public function default_configuration();

    /**
     * Returns a new question for the user based on the matchmaking strategy configuration
     *
     * @param capquiz_user $user
     * @param capquiz_question_list $qlist
     * @param array $inactiveattempts
     * @return mixed
     */
    abstract public function next_question_for_user(capquiz_user $user, capquiz_question_list $qlist,
            array $inactiveattempts);

}
