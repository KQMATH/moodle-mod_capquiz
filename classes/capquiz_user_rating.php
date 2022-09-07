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

use dml_exception;
use stdClass;

// REDUNDANT: defined('MOODLE_INTERNAL') || die();

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
    private $record;

    /**
     * capquiz_user constructor.
     * @param stdClass $record
     * @throws dml_exception
     */
    public function __construct(stdClass $record) {
        $this->record = $record;
    }

    /**
     * Loads and returns user rating from database
     *
     * @param int $questionratingid
     * @return capquiz_user_rating|null
     * @throws dml_exception
     */
    public static function load_user_rating(int $questionratingid) {
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
     * @param capquiz_user_rating $rating
     * @param bool $manual
     * @return capquiz_user_rating|null
     */
    public static function create_user_rating($user, $rating, $manual = false) {
        return self::insert_user_rating_entry($user->id(), $rating, $manual);
    }

    /**
     * Load information about the latest user rating for an capquiz user from the database.
     *
     * @param int $userid
     * @return capquiz_user_rating
     * @throws dml_exception
     */
    public static function latest_user_rating_by_user($userid) {
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
                AND cu.id = :user_id";
        $record = $DB->get_record_sql($sql, ['user_id' => $userid]);

        return $record ? new capquiz_user_rating($record) : null;
    }

    /**
     * Inserts a new user rating record to the database
     *
     * @param int $userid capquiz_user id
     * @param float $rating
     * @param null $attemptid
     * @return capquiz_user_rating|null
     */
    public static function insert_user_rating_entry(int $userid, float $rating, bool $manual = false) {
        global $DB, $USER;

        $record = new stdClass();
        $record->capquiz_user_id = $userid;
        $record->rating = $rating;
        $record->manual = $manual;
        $record->timecreated = time();
        $record->user_id = $USER->id;
        try {
            $ratingid = $DB->insert_record('capquiz_user_rating', $record);
            $record->id = $ratingid;
            return new capquiz_user_rating($record);
        } catch (dml_exception $e) {
            return null;
        }
    }

    /**
     * Returns this user ratings id
     *
     * @return int
     */
    public function id(): int {
        return $this->record->id;
    }

    /**
     * Returns the time this user rating was created
     *
     * @return string
     */
    public function timecreated(): string {
        return $this->user->timecreated;
    }

    /**
     * Returns this user ratings rating
     *
     * @return float
     */
    public function rating(): float {
        return $this->record->rating;
    }

    /**
     * Sets this user ratings rating and updates the database record
     *
     * @param float $rating
     * @throws dml_exception
     */
    public function set_rating(float $rating) {
        global $DB;
        $this->record->rating = $rating;
        $DB->update_record('capquiz_user_rating', $this->record);
    }
}
