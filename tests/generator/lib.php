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

use mod_capquiz\capquiz;
use mod_capquiz\capquiz_slot;
use mod_capquiz\capquiz_user;

/**
 * CAPQuiz module test data generator class
 *
 * @package   mod_capquiz
 * @author    Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright 2025 Norwegian University of Science and Technology (NTNU)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_capquiz_generator extends testing_module_generator {
    /**
     * Generate a test CAPQuiz instance.
     *
     * @param array|stdClass $record
     * @param array|null $options
     * @return stdClass
     */
    public function create_instance($record = null, ?array $options = null): stdClass {
        $data = [
            'name' => 'CAPQuiz',
            'questiondisplayoptions' => json_encode([]),
        ];
        $data += (array)$record;
        return parent::create_instance((object)$data, (array)$options);
    }

    /**
     * Create a CAPQUiz.
     *
     * @param array $record
     * @return capquiz
     */
    public function create_capquiz(array $record = []): capquiz {
        if (!array_key_exists('course', $record)) {
            $datagenerator = phpunit_util::get_data_generator();
            $course = $datagenerator->create_course();
            $record['course'] = (int)$course->id;
        }
        $record = $this->create_instance($record);
        return new capquiz((int)$record->id);
    }

    /**
     * Create a CAPQuiz slot.
     *
     * @param capquiz $capquiz
     * @param float $rating
     * @param ?int $questionid A question will be generated if this is null
     * @return capquiz_slot
     */
    public function create_slot(capquiz $capquiz, float $rating = 1000.0, ?int $questionid = null): capquiz_slot {
        if ($questionid === null) {
            $questionid = $this->create_question($capquiz)->id;
        }
        return $capquiz->create_slot($questionid, $rating);
    }

    /**
     * Create a question in the context of a CAPQuiz.
     *
     * @param capquiz $capquiz
     * @return stdClass
     */
    public function create_question(capquiz $capquiz): \stdClass {
        $datagenerator = phpunit_util::get_data_generator();
        /** @var \core_question_generator $questiongenerator */
        $questiongenerator = $datagenerator->get_plugin_generator('core_question');
        $context = \core\context\course::instance($capquiz->get('course'));
        $category = $questiongenerator->create_question_category(['contextid' => $context->id]);
        return $questiongenerator->create_question('truefalse', null, ['category' => $category->id]);
    }

    /**
     * Create a CAPQuiz user.
     *
     * @param capquiz $capquiz
     * @return capquiz_user
     */
    public function create_user(capquiz $capquiz): capquiz_user {
        $datagenerator = phpunit_util::get_data_generator();
        $user = $datagenerator->create_user();
        return $capquiz->create_user((int)$user->id);
    }
}
