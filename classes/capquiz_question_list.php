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

defined('MOODLE_INTERNAL') || die();

class capquiz_question_list {

    private $db_entry;
    private $questions;

    public function __construct(\stdClass $db_entry) {
        global $DB;
        $this->db_entry = $db_entry;
        $this->questions = [];
        $entries = $DB->get_records(database_meta::$table_capquiz_question, [
            database_meta::$field_question_list_id => $this->db_entry->id
        ]);
        foreach ($entries as $entry) {
            $this->questions[] = new capquiz_question($entry);
        }
    }

    public function id() {
        return $this->db_entry->id;
    }

    public function is_published() {
        return $this->db_entry->published;
    }

    public function has_questions() {
        return count($this->questions) > 0;
    }

    public function capquiz_origin_id() {
        return $this->db_entry->capquiz_origin_id;
    }

    public function title() {
        return $this->db_entry->title;
    }

    public function description() {
        return $this->db_entry->description;
    }

    /**
     * Get the rating required to earn a badge for the specified level.
     * @param int $level
     * @return int | null
     */
    public function level_rating(int $level) {
        $field = "level_{$level}_rating";
        if (!isset($this->db_entry->{$field})) {
            return null;
        }
        return (int)$this->db_entry->{$field};
    }

    public function rating_in_stars(int $rating) {
        $stars = 0;
        for ($level = 1; $level < 6; $level++) {
            if ($rating >= $this->level_rating($level)) {
                $stars++;
            }
        }
        return $stars;
    }

    public function stars_as_array(int $stars) {
        $result = [];
        for ($star = 1; $star < 6; $star++) {
            $result[] = $stars >= $star;
        }
        return $result;
    }

    public function next_star_percent(int $rating) {
        $goal = 0;
        for ($level = 2; $level < 6; $level++) {
            $goal = $this->level_rating($level);
            if ($goal > $rating) {
                $previous = $this->level_rating($level - 1);
                $rating -= $previous;
                $goal -= $previous;
                break;
            }
        }
        if ($goal < 1) {
            return 0;
        }
        return (int)($rating / $goal * 100);
    }

    public function time_created() {
        return $this->db_entry->time_created;
    }

    public function time_modified() {
        return $this->db_entry->time_modified;
    }

    public function question_count() {
        return count($this->questions);
    }

    public function questions() {
        return $this->questions;
    }

    public function question(string $question_id) {
        foreach ($this->questions as $question) {
            if ($question->id() === $question_id) {
                return $question;
            }
        }
        return null;
    }

}
