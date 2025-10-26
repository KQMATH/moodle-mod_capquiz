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
 * Test CAPQuiz.
 *
 * @package   mod_capquiz
 * @covers    \mod_capquiz\capquiz
 * @author    Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright 2025 Norwegian University of Science and Technology (NTNU)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class capquiz_test extends \advanced_testcase {
    /** @var \mod_capquiz_generator CAPQuiz generator */
    private \mod_capquiz_generator $generator;

    /** @var \core_question_generator Question generator */
    private \core_question_generator $questiongenerator;

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
        /** @var \core_question_generator $questiongenerator */
        $questiongenerator = self::getDataGenerator()->get_plugin_generator('core_question');
        $this->questiongenerator = $questiongenerator;
    }

    /**
     * Test checking if a CAPQuiz is open.
     *
     * @return void
     */
    public function test_is_open(): void {
        $clock = $this->mock_clock_with_frozen();
        $capquiz = new capquiz();
        $capquiz->set_many([
            'timeopen' => $clock->time() + 1,
            'timedue' => $clock->time() + 2,
        ]);
        $this->assertFalse($capquiz->is_open());
        $clock->bump();
        $this->assertTrue($capquiz->is_open());
        $clock->bump();
        $this->assertTrue($capquiz->is_open());
        $clock->bump();
        $this->assertFalse($capquiz->is_open());
    }

    /**
     * Test checking if a CAPQuiz is past due time.
     *
     * @return void
     */
    public function test_is_past_due_time(): void {
        $clock = $this->mock_clock_with_frozen();
        $capquiz = new capquiz();
        $capquiz->set('timedue', $clock->time() + 1);
        $this->assertFalse($capquiz->is_past_due_time());
        $clock->bump();
        $this->assertFalse($capquiz->is_past_due_time());
        $clock->bump();
        $this->assertTrue($capquiz->is_past_due_time());
        $clock->bump();
        $this->assertTrue($capquiz->is_past_due_time());
        $capquiz->set('timedue', 0);
        $clock->bump(100);
        $this->assertFalse($capquiz->is_past_due_time());
    }

    /**
     * Test creating a CAPQuiz user.
     *
     * @return void
     */
    public function test_create_user(): void {
        $course = $this->getDataGenerator()->create_course();
        $capquiz = $this->generator->create_capquiz((int)$course->id);
        $user = $this->getDataGenerator()->create_user();

        // Test that the user is created as expected.
        $capquizuser = $capquiz->create_user((int)$user->id);
        $this->assertEquals((int)$user->id, $capquizuser->get('userid'));
        $this->assertEquals($capquiz->get('id'), $capquizuser->get('capquizid'));
        $this->assertEquals($capquiz->get('defaultuserrating'), $capquizuser->get('rating'));

        // Test that the user rating was added as expected.
        $ratings = capquiz_user_rating::get_records(['capquizuserid' => $capquizuser->get('id')]);
        $this->assertCount(1, $ratings);
        $rating = reset($ratings);
        $this->assertEqualsWithDelta($capquiz->get('defaultuserrating'), $rating->get('rating'), 0.00001);
        $this->assertFalse($rating->get('manual'));
    }

    /**
     * Test creating and deleting slots.
     *
     * @return void
     */
    public function test_create_and_delete_slot(): void {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $capquiz = $this->generator->create_capquiz((int)$course->id);
        $context = \core\context\course::instance($course->id);
        $category = $this->questiongenerator->create_question_category(['contextid' => $context->id]);
        $question = $this->questiongenerator->create_question('truefalse', null, ['category' => $category->id]);
        $user = $this->getDataGenerator()->create_user();
        $capquizuser = $capquiz->create_user((int)$user->id);

        // Testing creating slot.
        $slot = $capquiz->create_slot($question->id, 1000.0);
        $slotid = $slot->get('id');
        $conditions = [
            'component' => 'mod_capquiz',
            'questionarea' => 'slot',
            'itemid' => $slot->get('id'),
        ];
        $this->assertTrue($DB->record_exists('question_references', $conditions));
        $this->assertEquals($capquiz->get('id'), $slot->get('capquizid'));

        // Create a question attempt.
        $this->assertNotNull($capquizuser->create_attempt($slot));
        $this->assertEquals(1, capquiz_attempt::count_records(['slotid' => $slotid]));

        // Test deleting slot.
        $this->assertTrue($capquiz->delete_slot($slot));
        $this->assertFalse($DB->record_exists('question_references', $conditions));
        $this->assertEquals(0, capquiz_attempt::count_records(['slotid' => $slotid]));
        $this->assertEquals(0, $slot->get('id'), 'Slot id should be unset in persistent::delete()');
        $this->assertFalse($capquiz->delete_slot($slot));
    }
}
