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

    public function next_question_for_user(capquiz_user $user,
                                           capquiz_question_list $question_list,
                                           array $inactive_capquiz_attempts
    ) /*: ?capquiz_question*/ {
        $excluded = $this->determine_excluded_questions($inactive_capquiz_attempts);
        $candidates = $this->find_questions_closest_to_rating($user, $excluded);
        if (count($candidates) === 0) {
            return null;
        }
        $index = mt_rand(0, count($candidates) - 1);
        if ($question = $candidates[$index]) {
            return $question;
        }
        return null;
    }

    private function find_questions_closest_to_rating(capquiz_user $user, array $excluded_questions) : array {
        global $DB;
        $sql = 'SELECT * FROM {capquiz_question} WHERE question_list_id = ?';
        $sql .= str_repeat(' AND id <> ?', count($excluded_questions));
        $sql .= ' ORDER BY ABS(? - rating)';
        $params = [];
        $params[] = $this->capquiz->question_list()->id();
        if (count($excluded_questions) > 0) {
            array_push($params, ...$excluded_questions);
        }
        $params[] = $this->ideal_question_rating($user);
        $result_questions = $DB->get_records_sql($sql, $params, 0, $this->number_of_questions_to_select);
        $questions = [];
        foreach ($result_questions as $question_db_entry) {
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
