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

use core\persistent;

/**
 * Class capquiz_question_rating
 *
 * @package     mod_capquiz
 * @author      Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright   2024 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_question_rating extends persistent {
    /** @var string The table name. */
    const TABLE = 'capquiz_question_rating';

    /**
     * Get the latest question rating for a given slot.
     *
     * @param capquiz_slot $slot
     */
    public static function get_latest_by_slot(capquiz_slot $slot): ?capquiz_question_rating {
        $records = self::get_records(['slotid' => $slot->get('id')], 'timecreated', 'DESC', 0, 1);
        return empty($records) ? null : reset($records);
    }

    /**
     * Delete records where criteria matches, while also calling hooks.
     *
     * @param array $criteria
     * @return void
     */
    public static function delete_records(array $criteria): void {
        do {
            $records = self::get_records($criteria, limit: 100);
            foreach ($records as $record) {
                $record->delete();
            }
        } while (!empty($records));
    }

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties(): array {
        return [
            'slotid' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
            ],
            'rating' => [
                'type' => PARAM_FLOAT,
                'default' => 0.0,
                'null' => NULL_NOT_ALLOWED,
            ],
            'manual' => [
                'type' => PARAM_BOOL,
                'default' => false,
                'null' => NULL_NOT_ALLOWED,
            ],
        ];
    }
}
