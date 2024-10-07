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

/**
 * This file defines a class represeting a capquiz user rating
 *
 * @package     mod_capquiz
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz;

use stdClass;

/**
 * Class capquiz_user_rating
 *
 * @package     mod_capquiz
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_user_rating {

    /** @var stdClass $record */
    private stdClass $record;

    /**
     * Constructor.
     *
     * @param stdClass $record
     */
    public function __construct(stdClass $record) {
        $this->record = $record;
    }

    /**
     * Loads and returns user rating from database
     *
     * @param int $questionratingid
     */
    public static function load_user_rating(int $questionratingid): ?capquiz_user_rating {
        global $DB;
        $record = $DB->get_record('capquiz_question_rating', ['id' => $questionratingid]);
        if ($record === false) {
            return null;
        }
        return new capquiz_user_rating($record);
    }

    /**
     * Creates and inserts a new user rating to the database
     *
     * @param capquiz_user $user
     * @param float $rating
     * @param bool $manual
     */
    public static function create_user_rating(capquiz_user $user, float $rating, bool $manual = false): ?capquiz_user_rating {
        return self::insert_user_rating_entry($user->id(), $rating, $manual);
    }

    /**
     * Load information about the latest user rating for an capquiz user from the database.
     *
     * @param int $capquizuserid
     */
    public static function latest_user_rating_by_user(int $capquizuserid): ?capquiz_user_rating {
        global $DB;
        $sql = "SELECT cur.*
                  FROM {capquiz_user_rating} cur
                  JOIN {capquiz_user} cu ON cu.id = cur.capquiz_user_id
                 WHERE cur.id = (
                    SELECT MAX(cur2.id)
                    FROM {capquiz_user_rating} cur2
                    JOIN {capquiz_user} cu2 ON cu2.id = cur2.capquiz_user_id
                    WHERE cu2.id = cu.id
                    )
                AND cu.id = :capquiz_user_id";
        $record = $DB->get_record_sql($sql, ['capquiz_user_id' => $capquizuserid]);

        return $record ? new capquiz_user_rating($record) : null;
    }

    /**
     * Inserts a new user rating record to the database
     *
     * @param int $capquizuserid
     * @param float $rating
     * @param bool $manual
     */
    public static function insert_user_rating_entry(int $capquizuserid, float $rating, bool $manual = false): capquiz_user_rating {
        global $DB, $USER;
        $record = new stdClass();
        $record->capquiz_user_id = $capquizuserid;
        $record->rating = $rating;
        $record->manual = $manual;
        $record->timecreated = time();
        $record->user_id = $USER->id;
        $record->id = $DB->insert_record('capquiz_user_rating', $record);
        return new capquiz_user_rating($record);
    }

    /**
     * Returns this user ratings id
     */
    public function id(): int {
        return $this->record->id;
    }

    /**
     * Returns this user ratings rating
     */
    public function rating(): float {
        return $this->record->rating;
    }

    /**
     * Sets this user ratings rating and updates the database record
     *
     * @param float $rating
     */
    public function set_rating(float $rating): void {
        global $DB;
        $this->record->rating = $rating;
        $DB->update_record('capquiz_user_rating', $this->record);
    }
}
