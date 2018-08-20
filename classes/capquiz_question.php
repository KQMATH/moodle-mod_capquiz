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

/**
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_question {

    private $db_entry;

    public function __construct(\stdClass $db_entry) {
        global $DB;
        $this->db_entry = $db_entry;
        $question = $DB->get_record(database_meta::$moodletable_question, [
            database_meta::$field_id => $db_entry->question_id
        ], '*', MUST_EXIST);
        if ($question) {
            $this->db_entry->name = $question->name;
            $this->db_entry->text = $question->questiontext;
        }
    }

    public static function load(int $question_id) {
        global $DB;
        $entry = $DB->get_record(database_meta::$table_capquiz_question, [
            database_meta::$field_id => $question_id
        ]);
        if ($entry)
            return new capquiz_question($entry);
        return null;
    }

    public function id() {
        return $this->db_entry->id;
    }

    public function question_id() {
        return $this->db_entry->question_id;
    }

    public function question_list_id() {
        return $this->db_entry->question_list_id;
    }

    public function rating() {
        return $this->db_entry->rating;
    }

    public function set_rating(float $rating) {
        global $DB;
        $db_entry = $this->db_entry;
        $db_entry->rating = $rating;
        if ($DB->update_record(database_meta::$table_capquiz_question, $db_entry)) {
            $this->db_entry = $db_entry;
            return true;
        }
        return false;
    }

    public function name() {
        return $this->db_entry->name;
    }

    public function text() {
        return $this->db_entry->text;
    }

}
