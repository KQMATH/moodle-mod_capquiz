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

namespace mod_capquiz;

use dml_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * @package     mod_capquiz
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_question_rating {

    /** @var stdClass $record */
    private $record;

    /**
     * capquiz_question constructor.
     * @param stdClass $record
     * @throws dml_exception
     */
    public function __construct(stdClass $record) {
        $this->record = $record;
    }

    public static function load_question_rating(int $questionratingid) {
        global $DB;
        $record = $DB->get_record('capquiz_question_rating', ['id' => $questionratingid]);
        if ($record === false) {
            return null;
        }
        return new capquiz_question_rating($record);
    }

    public static function create_question_rating(capquiz_question $question, $rating, capquiz_question_attempt $attempt = null) {
        $attemptid = $attempt ? $attempt->id() : $attempt;
        return self::insert_question_rating_entry($question->id(), $rating, $attemptid);
    }

    /**
     * @param int $questionid
     * @param float $rating
     * @param int|null $attemptid
     * @return capquiz_question_rating|null
     */
    public static function insert_question_rating_entry(int $questionid, float $rating, int $attemptid = null) {
        global $DB, $USER;

        $record = new stdClass();
        $record->capquiz_question_id = $questionid;
        $record->capquiz_attempt_id = $attemptid;
        $record->rating = $rating;
        $record->timecreated = time();
        $record->user_id = $USER->id;
        try {
            $DB->insert_record('capquiz_question_rating', $record);
            return new capquiz_question_rating($record);
        } catch (dml_exception $e) {
            return null;
        }
    }

    /**
     * Load information about the latest question rating for an attempt from the database.
     *
     * @param int $attemptid
     * @return capquiz_question_rating
     * @throws dml_exception
     */
    public static function latest_question_rating_by_attempt(int $attemptid) {
        global $DB;
        $sql = "SELECT cqr.id AS id,
                  FROM {capquiz_question_rating} cqr
                  JOIN {capquiz_attempt} ca ON cur.capquiz_attempt_id = ca.id
                 WHERE cur.timecreated = {".self::latest_question_rating_subquery()."}
                   AND ca.id = :attemptid";
        $questionrating = $DB->get_record_sql($sql, ['attemptid' => $attemptid]);
        return $questionrating ? new capquiz_question_rating($questionrating) : null;
    }

    protected static function latest_question_rating_subquery($questionratingid = 'cqr.id') {
        $sql = "(
                SELECT MAX(timecreated)
                FROM {capquiz_question_rating}
                WHERE id = $questionratingid
                )";
        return $sql;
    }

    public function id(): int {
        return $this->record->id;
    }

    public function timecreated(): string {
        return $this->question->timecreated;
    }

    public function rating(): float {
        return $this->record->rating;
    }

    public function set_rating(float $rating) {
        global $DB;
        $this->record->rating = $rating;
        $DB->update_record('capquiz_question_rating', $this->record);
    }
}
