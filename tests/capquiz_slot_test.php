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
 * Test CAPQuiz slot.
 *
 * @package   mod_capquiz
 * @covers    \mod_capquiz\capquiz_slot
 * @author    Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright 2025 Norwegian University of Science and Technology (NTNU)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class capquiz_slot_test extends \advanced_testcase {
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
     * Test before delete hook.
     *
     * @return void
     */
    public function test_before_delete(): void {
        global $DB;

        // Setup.
        $capquiz = $this->generator->create_capquiz();
        $user = $this->generator->create_user($capquiz);
        $slot = $this->generator->create_slot($capquiz);

        // Confirm that the setup is as expected.
        $this->assertNotNull($user->create_attempt($slot));
        $this->assertEquals(1, capquiz_attempt::count_records(['slotid' => $slot->get('id')]));
        $this->assertEquals(1, capquiz_question_rating::count_records(['slotid' => $slot->get('id')]));
        $slot->rate(1100.0, true);
        $this->assertEquals(2, capquiz_question_rating::count_records(['slotid' => $slot->get('id')]));

        // Delete the slot. Keep the id for further tests.
        $slotid = $slot->get('id');
        $this->assertTrue($slot->delete());
        $this->assertEquals(0, $slot->get('id'));

        // Confirm the question reference, question ratings, and attempts were all deleted.
        $this->assertEquals(0, $DB->count_records('question_references', [
            'component' => 'mod_capquiz',
            'questionarea' => 'slot',
            'itemid' => $slot->get('id'),
        ]));
        $this->assertEquals(0, capquiz_question_rating::count_records(['slotid' => $slotid]));
        $this->assertEquals(0, capquiz_attempt::count_records(['slotid' => $slotid]));
    }
}
