<?php

namespace mod_capquiz;

defined('MOODLE_INTERNAL') || die();

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
