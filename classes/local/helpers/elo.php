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

declare(strict_types=1);

namespace mod_capquiz\local\helpers;

/**
 * ELO rating system helper for CAPQuiz.
 *
 * @package     mod_capquiz
 * @author      Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright   2024 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class elo {
    /**
     * Calculate a new rating based on the old rating and score towards the opponent's rating.
     *
     * @param float $kfactor
     * @param float $score
     * @param float $rating
     * @param float $opponentrating
     */
    public static function new_rating(float $kfactor, float $score, float $rating, float $opponentrating): float {
        return $rating + $kfactor * ($score - self::expected_score($rating, $opponentrating));
    }

    /**
     * Calculate the expected score.
     *
     * @param float $rating
     * @param float $opponentrating
     */
    public static function expected_score(float $rating, float $opponentrating): float {
        return 1.0 / (1.0 + pow(10.0, ($opponentrating - $rating) / 400.0));
    }

    /**
     * Calculate ideal question rating.
     *
     * @param float $winprobability
     * @param float $rating
     */
    public static function ideal_question_rating(float $winprobability, float $rating): float {
        return 400.0 * log(1.0 / $winprobability - 1.0, 10.0) + $rating;
    }
}
