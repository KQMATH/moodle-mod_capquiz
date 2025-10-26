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
     * @param int $courseid
     * @param array $record
     * @return capquiz
     */
    public function create_capquiz(int $courseid, array $record = []): capquiz {
        $record['course'] = $courseid;
        $record = $this->create_instance($record);
        return new capquiz((int)$record->id);
    }
}
