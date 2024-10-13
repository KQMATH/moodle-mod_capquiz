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
 * Test ELO helper.
 *
 * @package   mod_capquiz
 * @covers    \mod_capquiz\local\helpers\elo
 * @author    Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright 2025 Norwegian University of Science and Technology (NTNU)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class elo_test extends \advanced_testcase {
    /**
     * Test new rating.
     *
     * @return void
     */
    public function test_new_rating(): void {
        $this->assertEqualsWithDelta(1003.57408, elo::new_rating(8.0, 0.5, 1000.0, 1500.0), 0.0001);
    }

    /**
     * Test expected score.
     *
     * @return void
     */
    public function test_expected_score(): void {
        $this->assertEqualsWithDelta(0.05324, elo::expected_score(1000.0, 1500.0), 0.0001);
        $this->assertEqualsWithDelta(0.94676, elo::expected_score(1500.0, 1000.0), 0.0001);
    }

    /**
     * Test ideal question rating.
     *
     * @return void
     */
    public function test_ideal_question_rating(): void {
        $this->assertEqualsWithDelta(852.80929, elo::ideal_question_rating(0.70, 1000.0), 0.0001);
        $this->assertEqualsWithDelta(201.74592, elo::ideal_question_rating(0.99, 1000.0), 0.0001);
    }
}
