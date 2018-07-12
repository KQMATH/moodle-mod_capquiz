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

class default_elo_rating_system extends capquiz_rating_system {

    private $student_k_factor;

    public function __construct(float $student_k_factor) {
        $this->student_k_factor = $student_k_factor;
    }

    public function user_loss_rating(capquiz_user $user, capquiz_question $question) {
        return $user->rating() + $this->student_k_factor * (0 - $this->expected_result($user->rating(), $question->rating()));
    }

    public function user_victory_rating(capquiz_user $user, capquiz_question $question) {
        return $user->rating() + $this->student_k_factor * (1 - $this->expected_result($user->rating(), $question->rating()));
    }

    // Must return an array with modified ratings such as: array(first_rating, second_rating)
    public function question_draw_ratings(capquiz_question $first, capquiz_question $second) {
        return [$first->rating(), $second->rating()];
    }

    // Must return an array with modified ratings such as: array(winner_rating, loser_rating)
    public function question_victory_ratings(capquiz_question $winner, capquiz_question $loser) {
        return [$winner->rating(), $loser->rating()];
    }

    private function expected_result(float $rating_a, float $rating_b) {
        $exponent = ($rating_b - $rating_a) / 400.0;
        return 1.0 / (1.0 + pow(10.0, $exponent));
    }

}
