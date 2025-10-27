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

namespace mod_capquiz;

/**
 * Test CAPQuiz user.
 *
 * @package   mod_capquiz
 * @covers    \mod_capquiz\capquiz_user
 * @author    Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright 2025 Norwegian University of Science and Technology (NTNU)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class capquiz_user_test extends \advanced_testcase {
    /** @var \mod_capquiz_generator CAPQuiz generator */
    private \mod_capquiz_generator $generator;

    /**
     * Set up.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        /** @var \mod_capquiz_generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('mod_capquiz');
        $this->generator = $generator;
    }

    /**
     * Test creating a CAPQuiz question attempt.
     *
     * @return void
     */
    public function test_create_attempt(): void {
        $capquiz = $this->generator->create_capquiz();
        $slot = $this->generator->create_slot($capquiz);
        $user = $this->generator->create_user($capquiz);

        $attempt = $user->create_attempt($slot);
        $this->assertNotNull($attempt);
        $this->assertEquals($capquiz->get('id'), $attempt->get('capquizid'));
        $this->assertEquals($user->get('id'), $attempt->get('capquizuserid'));
        $this->assertEquals($slot->get('id'), $attempt->get('slotid'));
    }

    /**
     * Test before delete hook.
     *
     * @return void
     */
    public function test_before_delete(): void {
        // Setup.
        $capquiz = $this->generator->create_capquiz();
        $slot = $this->generator->create_slot($capquiz);
        $user = $this->generator->create_user($capquiz);

        // Confirm that the setup is as expected.
        $this->assertNotNull($user->create_attempt($slot));
        $this->assertEquals(1, capquiz_attempt::count_records(['capquizuserid' => $user->get('id')]));
        $this->assertEquals(1, capquiz_user_rating::count_records(['capquizuserid' => $user->get('id')]));
        $user->rate(500.0, true);
        $this->assertEquals(2, capquiz_user_rating::count_records(['capquizuserid' => $user->get('id')]));

        // Delete the user. Keep the id for further tests.
        $capquizuserid = $user->get('id');
        $this->assertTrue($user->delete());
        $this->assertEquals(0, $user->get('id'));

        // Confirm the user ratings and question attempts were all deleted.
        $this->assertEquals(0, capquiz_user_rating::count_records(['capquizuserid' => $capquizuserid]));
        $this->assertEquals(0, capquiz_attempt::count_records(['capquizuserid' => $capquizuserid]));
    }
}
