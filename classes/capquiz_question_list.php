<?php

namespace mod_capquiz;

require_once($CFG->dirroot . '/mod/capquiz/classes/capquiz_question.php');

defined('MOODLE_INTERNAL') || die();

class capquiz_question_list {

    private $db_entry;
    private $questions;

    public function __construct(\stdClass $db_entry) {
        global $DB;
        $this->db_entry = $db_entry;
        $records = [];
        foreach ($DB->get_records(database_meta::$table_capquiz_question, [database_meta::$field_question_list_id => $this->db_entry->id]) as $entry) {
            array_push($records, new capquiz_question($entry));
        }
        $this->questions = $records;
    }

    public function id() {
        return $this->db_entry->id;
    }

    public function is_published() {
        return $this->db_entry->published;
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
