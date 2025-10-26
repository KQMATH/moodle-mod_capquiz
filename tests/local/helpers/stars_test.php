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

use mod_capquiz\capquiz;
use mod_capquiz\capquiz_user;

/**
 * Test stars helper.
 *
 * @package   mod_capquiz
 * @covers    \mod_capquiz\local\helpers\stars
 * @author    Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright 2025 Norwegian University of Science and Technology (NTNU)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class stars_test extends \advanced_testcase {
    /**
     * Test getting the completion level to the next rating as a percent value.
     *
     * @return void
     */
    public function test_get_percent_to_next_star(): void {
        $capquiz = new capquiz();
        $capquiz->set_many([
            'defaultuserrating' => 1000.0,
            'starratings' => '1000,1200,1400,1600,1800',
        ]);
        $this->assertEquals(50, stars::get_percent_to_next_star($capquiz, 1100.0));
        $this->assertEquals(99, stars::get_percent_to_next_star($capquiz, 1199.99999));
        $this->assertEquals(0, stars::get_percent_to_next_star($capquiz, 1200.0));
        $this->assertEquals(5, stars::get_percent_to_next_star($capquiz, 1210.0));
        $this->assertEquals(75, stars::get_percent_to_next_star($capquiz, 1550.0));
        $this->assertEquals(100, stars::get_percent_to_next_star($capquiz, 1800.0));

        $this->assertEquals(111, stars::get_percent_to_next_star($capquiz, 2000.0));
    }

    /**
     * Test getting the required rating for a given star.
     *
     * @return void
     */
    public function test_get_required_rating_for_star(): void {
        $this->assertEquals(1000, stars::get_required_rating_for_star('1000,1200,1400', 1));
        $this->assertEquals(1200, stars::get_required_rating_for_star('1000,1200,1400', 2));
        $this->assertEquals(1400, stars::get_required_rating_for_star('1000,1200,1400', 3));
        $this->assertEquals(1000, stars::get_required_rating_for_star('1000', 2));
        $this->assertEquals(700, stars::get_required_rating_for_star('500,600,700', 100));
    }

    /**
     * Test getting max number of stars that can be achieved.
     *
     * @return void
     */
    public function test_get_max_stars(): void {
        $this->assertEquals(1, stars::get_max_stars(''));
        $this->assertEquals(1, stars::get_max_stars('100'));
        $this->assertEquals(2, stars::get_max_stars('100,200'));
        $this->assertEquals(3, stars::get_max_stars('1,1,1'));
        $this->assertEquals(4, stars::get_max_stars('10,20,300,400'));
        $this->assertEquals(5, stars::get_max_stars('10,11,12,13,14'));
    }

    /**
     * Test checking if user has achieved a passing grade.
     *
     * @return void
     */
    public function test_is_user_passing(): void {
        $capquiz = new capquiz();
        $capquiz->set('starstopass', 3);
        $this->assertFalse(stars::is_user_passing(new capquiz_user(0, (object)['starsgraded' => 1]), $capquiz));
        $this->assertFalse(stars::is_user_passing(new capquiz_user(0, (object)['starsgraded' => 2]), $capquiz));
        $this->assertTrue(stars::is_user_passing(new capquiz_user(0, (object)['starsgraded' => 3]), $capquiz));
    }
}
