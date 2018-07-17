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

use core\session\database;

defined('MOODLE_INTERNAL') || die();

class adaptive_question_selector extends capquiz_question_selector {

    private $capquiz;
    private $user_win_probability;
    private $number_of_questions_drawn;

    public function __construct(capquiz $capquiz) {
        $this->capquiz = $capquiz;
        $this->user_win_probability = 0.75;
        $this->number_of_questions_drawn = 10;
    }

    public function next_question_for_user(capquiz_user $user, capquiz_question_list $question_list, array $inactive_capquiz_attempts) {
        $candidate_questions = $this->find_questions_closest_to_rating($user);
        $index = mt_rand(0, count($candidate_questions) - 1);
        if ($question = $candidate_questions[$index])
            return $question;
        return null;
    }

    private function find_questions_closest_to_rating(capquiz_user $user) {
        global $DB;
        $table = database_meta::$table_capquiz_question;
        $field = database_meta::$field_question_list_id;
        $ideal_question_rating = $this->ideal_question_rating($user);
        $rating_field = database_meta::$field_rating;
        $question_list_id = $this->capquiz->question_list()->id();
        $sql = "SELECT * FROM {" . $table . "} WHERE $field=$question_list_id";
        $sql .= " ORDER BY ABS($rating_field-$ideal_question_rating) LIMIT $this->number_of_questions_drawn";
        $sql .= ";";
        $questions = [];
        foreach ($DB->get_records_sql($sql) as $question_db_entry) {
            $questions[] = new capquiz_question($question_db_entry);
        }
        return $questions;
    }

    private function ideal_question_rating(capquiz_user $user) {
        return 400.0 * log((1.0 / $this->user_win_probability) - 1.0, 10.0) + $user->rating();
    }
}
