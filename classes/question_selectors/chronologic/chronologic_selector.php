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

class chronologic_selector extends capquiz_question_selector {

    public function configure(\stdClass $configuration) {

    }

    public function configuration() {
        return null;
    }

    public function default_configuration() {
        return null;
    }

    public function next_question_for_user(capquiz_user $user, capquiz_question_list $question_list, array $inactive_capquiz_attempts) {
        $is_answered = function (capquiz_question $q) use ($inactive_capquiz_attempts) {
            foreach ($inactive_capquiz_attempts as $attempt) {
                if ($attempt->question_id() === $q->id()) {
                    return true;
                }
            }
            return false;
        };
        foreach ($question_list->questions() as $question) {
            if (!$is_answered($question)) {
                return $question;
            }
        }
        return null;
    }
}
