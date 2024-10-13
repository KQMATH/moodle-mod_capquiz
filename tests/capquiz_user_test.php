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
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();

        // Create a question in a new question category.
        /** @var \core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $context = \core\context\course::instance($course->id);
        $category = $questiongenerator->create_question_category(['contextid' => $context->id]);
        $question = $questiongenerator->create_question('truefalse', null, ['category' => $category->id]);

        // Add the question to a CAPQuiz.
        $capquiz = $this->generator->create_capquiz((int)$course->id);
        $slot = $capquiz->create_slot($question->id, 1000.0);

        // Create a user for the CAPQuiz.
        $user = $this->getDataGenerator()->create_user();
        $capquizuser = $capquiz->create_user((int)$user->id);

        // Test creating a question attempt for the user.
        $attempt = $capquizuser->create_attempt($slot);
        $this->assertNotNull($attempt);
        $this->assertEquals($capquiz->get('id'), $attempt->get('capquizid'));
        $this->assertEquals($capquizuser->get('id'), $attempt->get('capquizuserid'));
        $this->assertEquals($slot->get('id'), $attempt->get('slotid'));
    }
}
