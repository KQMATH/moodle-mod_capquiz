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

    /** @var float $studentkfactor */
    private $studentkfactor;

    /** @var float $questionkfactor */
    private $questionkfactor;

    public function configure(\stdClass $configuration) {
        if ($configuration->student_k_factor) {
            $this->studentkfactor = $configuration->student_k_factor;
        }
        if ($configuration->question_k_factor) {
            $this->questionkfactor = $configuration->question_k_factor;
        }
    }

    public function configuration() {
        $config = new \stdClass;
        $config->student_k_factor = $this->studentkfactor;
        $config->question_k_factor = $this->questionkfactor;
        return $config;
    }

    public function default_configuration() {
        $config = new \stdClass;
        $config->student_k_factor = 32;
        $config->question_k_factor = 8;
        return $config;
    }

    public function update_user_rating(capquiz_user $user, capquiz_question $question, float $score) {
        $current = $user->rating();
        $factor = $this->studentkfactor;
        $updated = $current + $factor * ($score - $this->expected_result($current, $question->rating()));
        $user->set_rating($updated);
    }

    public function question_victory_ratings($winner, $loser) {
        $loserating = $loser->rating();
        $winrating = $winner->rating();
        $factor = $this->questionkfactor;
        $newloserating = $loserating + $factor * (0 - $this->expected_result($winrating, $loserating));
        $newwinrating = $winrating + $factor * (1 - $this->expected_result($loserating, $winrating));
        $loser->set_rating($newloserating);
        $winner->set_rating($newwinrating);
    }

    private function expected_result(float $a, float $b) : float {
        $exponent = ($b - $a) / 400.0;
        return 1.0 / (1.0 + pow(10.0, $exponent));
    }

}
