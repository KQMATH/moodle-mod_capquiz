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
 * This file defines a class which acts as a selector for the chronologic matchmaking strategy
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz;

defined('MOODLE_INTERNAL') || die();

/**
 * Class chronologic_selector
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class chronologic_selector extends capquiz_matchmaking_strategy {

    /**
     * Nothing to configure
     *
     * @param \stdClass $configuration
     * @return mixed|void
     */
    public function configure(\stdClass $configuration) {

    }

    /**
     * No configuration needed
     *
     * @return null
     */
    public function configuration() {
        return null;
    }

    /**
     * No configuration needed
     *
     * @return null
     */
    public function default_configuration() {
        return null;
    }

    /**
     * Returns the next question for the user in a chronological order
     *
     * @param capquiz_user $user
     * @param capquiz_question_list $qlist
     * @param array $inactiveattempts
     * @return capquiz_question|null
     */
    public function next_question_for_user(capquiz_user $user, capquiz_question_list $qlist, array $inactiveattempts) {
        $answered = function (capquiz_question $q) use ($inactiveattempts) {
            foreach ($inactiveattempts as $attempt) {
                if ($attempt->question_id() === $q->id()) {
                    return true;
                }
            }
            return false;
        };
        foreach ($qlist->questions() as $question) {
            if (!$answered($question)) {
                return $question;
            }
        }
        return null;
    }
}
