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
 * This file defines an abstract capquiz rating system
 *
 * @package     mod_capquiz
 * @author      Sebastian S. Gundersen <sebastian@sgundersen.com>
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2019 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz;

use stdClass;

/**
 * Class capquiz_rating_system
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class capquiz_rating_system {

    /**
     * Function to configure a rating system
     *
     * @param stdClass $config
     */
    abstract public function configure(stdClass $config): void;

    /**
     * Function to get the rating system configuration
     */
    abstract public function configuration(): stdClass;

    /**
     * Function to get the default rating system configuration
     */
    abstract public function default_configuration(): stdClass;

    /**
     * Updates the users rating based on the rating system and its configuration
     *
     * @param capquiz_user $user
     * @param capquiz_question $question
     * @param float $score
     */
    abstract public function update_user_rating(capquiz_user $user, capquiz_question $question, float $score): void;

    /**
     * Updates the winning and losing questions ratings
     *
     * @param capquiz_question $winner
     * @param capquiz_question $loser
     */
    abstract public function question_victory_ratings(capquiz_question $winner, capquiz_question $loser): void;

}
