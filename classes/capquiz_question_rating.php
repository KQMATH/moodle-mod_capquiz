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
 * This file defines a class represeting a capquiz question rating
 *
 * @package     mod_capquiz
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz;

use dml_exception;
use stdClass;

/**
 * Class capquiz_question_rating
 *
 * @package     mod_capquiz
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_question_rating {

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
     * Loads a question rating from the database with a matching id
     *
     * @param int $questionratingid
     */
    public static function load_question_rating(int $questionratingid): ?capquiz_question_rating {
        global $DB;
        $record = $DB->get_record('capquiz_question_rating', ['id' => $questionratingid]);
        return empty($record) ? null : new capquiz_question_rating($record);
    }

    /**
     * Creates a new question rating and inserts it to the database
     *
     * @param capquiz_question $question
     * @param float $rating
     * @param bool $manual
     */
    public static function create_question_rating(capquiz_question $question, float $rating,
                                                  bool $manual = false): capquiz_question_rating {
        return self::insert_question_rating_entry($question->id(), $rating, $manual);
    }

    /**
     * Insert new question rating to database
     *
     * @param int $questionid
     * @param float $rating
     * @param bool $manual
     */
    public static function insert_question_rating_entry(int $questionid, float $rating,
                                                        bool $manual = false): capquiz_question_rating {
        global $DB;
        $record = new stdClass();
        $record->capquiz_question_id = $questionid;
        $record->rating = $rating;
        $record->manual = $manual;
        $record->timecreated = time();
        $ratingid = $DB->insert_record('capquiz_question_rating', $record);
        $record->id = $ratingid;
        return new capquiz_question_rating($record);
    }

    /**
     * Load information about the latest question rating for an attempt from the database.
     *
     * @param int $questionid
     */
    public static function latest_question_rating_by_question(int $questionid): ?capquiz_question_rating {
        global $DB;
        $sql = "SELECT cqr.*
                  FROM {capquiz_question_rating} cqr
                  JOIN {capquiz_question} cq ON cq.id = cqr.capquiz_question_id
                 WHERE cqr.id = (
                    SELECT MAX(cqr2.id)
                    FROM {capquiz_question_rating} cqr2
                    JOIN {capquiz_question} cq2 ON cq2.id = cqr2.capquiz_question_id
                    WHERE cq2.id = cq.id
                    )
                AND cq.id = :question_id";
        $record = $DB->get_record_sql($sql, ['question_id' => $questionid]);
        return empty($record) ? null : new capquiz_question_rating($record);
    }

    /**
     * Returns this question ratings id
     */
    public function id(): int {
        return $this->record->id;
    }

    /**
     * Returns the time of when the question rating was created
     */
    public function timecreated(): string {
        return $this->record->timecreated;
    }

    /**
     * Returns the question rating
     */
    public function rating(): float {
        return $this->record->rating;
    }

    /**
     * Sets the question rating
     *
     * @param float $rating
     */
    public function set_rating(float $rating): void {
        global $DB;
        $this->record->rating = $rating;
        $DB->update_record('capquiz_question_rating', $this->record);
    }
}
