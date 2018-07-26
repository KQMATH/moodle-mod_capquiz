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

class capquiz_question_engine {

    private $capquiz;
    private $question_usage;
    private $question_selector;
    private $question_rating_system;

    public function __construct(capquiz $capquiz, \question_usage_by_activity $question_usage, capquiz_question_selector $question_selector, capquiz_rating_system $rating_system) {
        $this->capquiz = $capquiz;
        $this->question_usage = $question_usage;
        $this->question_selector = $question_selector;
        $this->question_rating_system = $rating_system;
    }

    public function user_is_completed(capquiz_user $user) {
        if ($attempt = capquiz_question_attempt::active_attempt($this->capquiz, $user)) {
            return false;
        }
        if ($question = $this->find_question_for_user($user)) {
            return false;
        }
        return true;
    }

    public function attempt_for_user(capquiz_user $user) {
        if ($attempt = capquiz_question_attempt::active_attempt($this->capquiz, $user)) {
            return $attempt;
        }
        return $this->new_attempt_for_user($user);
    }

    public function attempt_for_current_user() {
        return $this->attempt_for_user($this->capquiz->user());
    }

    public function attempt_answered(capquiz_user $user, capquiz_question_attempt $attempt) {
        $attempt->mark_as_answered();
        $question = $this->capquiz->question_list()->question($attempt->question_id());
        if ($attempt->is_correctly_answered()) {
            $this->question_rating_system->update_user_victory_rating($user, $question);
            $this->maybe_award_badge($user);
        } else {
            $this->question_rating_system->update_user_loss_rating($user, $question);
        }
        if ($previous_attempt = capquiz_question_attempt::previous_attempt($this->capquiz, $user)) {
            $this->update_question_rating($previous_attempt, $attempt);
        }
    }

    /**
     * @param capquiz_user $user
     */
    private function maybe_award_badge(capquiz_user $user) {
        global $DB;
        $capquizid = $user->capquiz_id();
        try {
            $list = $DB->get_record('capquiz_question_list', ['id' => $this->capquiz->question_list_id()]);
            if (!$list) {
                return;
            }
        } catch (\dml_exception $exception) {
            return;
        }
        $list = new capquiz_question_list($list, $this->capquiz);
        $badge = new capquiz_badge(0, $capquizid);
        for ($level = 5; $level > 0; $level--) {
            $required = $list->level_rating($level);
            if ($user->rating() >= $required) {
                $badge->award($user->moodle_user_id(), $level);
                break;
            }
        }
    }

    public function attempt_reviewed(capquiz_user $user, capquiz_question_attempt $attempt) {
        $attempt->mark_as_reviewed();
    }

    private function new_attempt_for_user(capquiz_user $user) {
        if ($question = $this->find_question_for_user($user)) {
            return capquiz_question_attempt::create_attempt($this->capquiz, $user, $question);
        }
        return null;
    }

    private function find_question_for_user(capquiz_user $user) {
        return $this->question_selector->next_question_for_user($user, $this->capquiz->question_list(), capquiz_question_attempt::inactive_attempts($this->capquiz, $user));
    }

    private function update_question_rating(capquiz_question_attempt $previous, capquiz_question_attempt $current) {
        $current_correct = $current->is_correctly_answered();
        $previous_correct = $previous->is_correctly_answered();
        $current_question = $this->capquiz->question_list()->question($current->question_id());
        $previous_question = $this->capquiz->question_list()->question($previous->question_id());
        if ($previous_correct && !$current_correct)
            $this->question_rating_system->question_victory_ratings($current_question, $previous_question);
        else if (!$previous_correct && $current_correct)
            $this->question_rating_system->question_victory_ratings($previous_question, $current_question);
    }

}
