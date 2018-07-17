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
    private $question_k_factor;

    public function __construct(float $student_k_factor, float $question_k_factor) {
        $this->student_k_factor = $student_k_factor;
        $this->question_k_factor = $question_k_factor;
    }

    public function update_user_loss_rating(capquiz_user $user, capquiz_question $question) {
        $user_rating = $user->rating();
        $updated_rating = $user_rating + $this->student_k_factor * (0 - $this->expected_result($user_rating, $question->rating()));
        $user->set_rating($updated_rating);
    }

    public function update_user_victory_rating(capquiz_user $user, capquiz_question $question) {
        $user_rating = $user->rating();
        $updated_rating = $user_rating + $this->student_k_factor * (1 - $this->expected_result($user_rating, $question->rating()));
        $user->set_rating($updated_rating);
    }

    public function question_victory_ratings(capquiz_question $winner, capquiz_question $loser) {
        $loser_rating = $loser->rating();
        $winner_rating = $winner->rating();
        $updated_loser_rating = $loser_rating + $this->question_k_factor * (0 - $this->expected_result($winner_rating, $loser_rating));
        $updated_winner_rating = $winner_rating + $this->question_k_factor * (1 - $this->expected_result($loser_rating, $winner_rating));
        $loser->set_rating($updated_loser_rating);
        $winner->set_rating($updated_winner_rating);
    }

    private function expected_result(float $rating_a, float $rating_b) {
        $exponent = ($rating_b - $rating_a) / 400.0;
        return 1.0 / (1.0 + pow(10.0, $exponent));
    }

}
