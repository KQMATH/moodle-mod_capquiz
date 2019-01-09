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

/**
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class n_closest_selector extends capquiz_matchmaking_strategy {

    private $capquiz;
    private $user_win_probability;
    private $number_of_questions_to_select;
    private $prevent_same_question_for_turns;

    public function __construct(capquiz $capquiz) {
        $this->capquiz = $capquiz;
        $this->configure($this->default_configuration());
    }

    public function configure(\stdClass $configuration) /*: void*/ {
        if ($configuration->user_win_probability > 0) {
            $this->user_win_probability = $configuration->user_win_probability;
        }
        if ($configuration->number_of_questions_to_select > 0) {
            $this->number_of_questions_to_select = $configuration->number_of_questions_to_select;
        }
        if ($configuration->prevent_same_question_for_turns >= 0) {
            $this->prevent_same_question_for_turns = $configuration->prevent_same_question_for_turns;
        }
    }

    public function configuration() : \stdClass {
        $config = new \stdClass;
        $config->prevent_same_question_for_turns = $this->prevent_same_question_for_turns;
        $config->user_win_probability = $this->user_win_probability;
        $config->number_of_questions_to_select = $this->number_of_questions_to_select;
        return $config;
    }

    public function default_configuration() : \stdClass {
        $config = new \stdClass;
        $config->user_win_probability = 0.75;
        $config->prevent_same_question_for_turns = 0;
        $config->number_of_questions_to_select = 10;
        return $config;
    }

    public function next_question_for_user(capquiz_user $user, capquiz_question_list $question_list, array $inactive_capquiz_attempts) /*: ?capquiz_question*/ {
        $candidate_questions = $this->find_questions_closest_to_rating($user, $this->determine_excluded_questions($inactive_capquiz_attempts));
        if (count($candidate_questions) === 0) {
            return null;
        }
        $index = mt_rand(0, count($candidate_questions) - 1);
        if ($question = $candidate_questions[$index]) {
            return $question;
        }
        return null;
    }

    private function find_questions_closest_to_rating(capquiz_user $user, array $excluded_questions) : array {
        global $DB;
        $table = database_meta::$table_capquiz_question;
        $field_list_id = database_meta::$field_question_list_id;
        $field_question_id = database_meta::$field_id;
        $ideal_question_rating = $this->ideal_question_rating($user);
        $rating_field = database_meta::$field_rating;
        $question_list_id = $this->capquiz->question_list()->id();
        $sql = "SELECT * FROM {" . $table . "} WHERE $field_list_id=$question_list_id";
        foreach ($excluded_questions as $question_id) {
            $sql .= " AND $field_question_id <> $question_id";
        }
        $sql .= " ORDER BY ABS($ideal_question_rating - $rating_field) LIMIT $this->number_of_questions_to_select";
        $sql .= ";";
        $questions = [];
        foreach ($DB->get_records_sql($sql) as $question_db_entry) {
            $questions[] = new capquiz_question($question_db_entry);
        }
        return $questions;
    }

    private function ideal_question_rating(capquiz_user $user) : float {
        return 400.0 * log((1.0 / $this->user_win_probability) - 1.0, 10.0) + $user->rating();
    }

    private function determine_excluded_questions(array $inactive_attempts) : array {
        $it = new \ArrayIterator(array_reverse($inactive_attempts, true));
        $excluded = [];
        for ($i = 0; $i < $this->prevent_same_question_for_turns; $i++) {
            if (!$it->valid()) {
                break;
            }
            $excluded[] = $it->current()->question_id();
            $it->next();
        }
        return array_unique($excluded);
    }
}
