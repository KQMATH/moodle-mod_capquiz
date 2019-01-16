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
class elo_rating_system extends capquiz_rating_system {

    /** @var float $student_k_factor */
    private $student_k_factor;

    /** @var float $question_k_factor */
    private $question_k_factor;

    public function configure(\stdClass $configuration) {
        if ($student_k_factor = $configuration->student_k_factor) {
            $this->student_k_factor = $student_k_factor;
        }
        if ($question_k_factor = $configuration->question_k_factor) {
            $this->question_k_factor = $question_k_factor;
        }
    }

    public function configuration() /*: ?\stdClass*/ {
        $config = new \stdClass;
        $config->student_k_factor = $this->student_k_factor;
        $config->question_k_factor = $this->question_k_factor;
        return $config;
    }

    public function default_configuration() /*: ?\stdClass*/ {
        $config = new \stdClass;
        $config->student_k_factor = 32;
        $config->question_k_factor = 8;
        return $config;
    }

    public function update_user_rating(capquiz_user $user, capquiz_question $question, float $score) /*: void*/ {
        $current = $user->rating();
        $factor = $this->student_k_factor;
        $updated = $current + $factor * ($score - $this->expected_result($current, $question->rating()));
        $user->set_rating($updated);
    }

    public function question_victory_ratings(capquiz_question $winner, capquiz_question $loser) /*: void*/ {
        $loserating = $loser->rating();
        $winrating = $winner->rating();
        $factor = $this->question_k_factor;
        $newloserating = $loserating + $factor * (0 - $this->expected_result($winrating, $loserating));
        $newwinrating = $winrating + $factor * (1 - $this->expected_result($loserating, $winrating));
        $loser->set_rating($newloserating);
        $winner->set_rating($newwinrating);
    }

    private function expected_result(float $rating_a, float $rating_b) : float {
        $exponent = ($rating_b - $rating_a) / 400.0;
        return 1.0 / (1.0 + pow(10.0, $exponent));
    }

}
