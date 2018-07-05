<?php

namespace mod_capquiz;

require_once($CFG->dirroot . '/mod/capquiz/database_meta.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/capquiz_question_list.php');

defined('MOODLE_INTERNAL') || die();

class capquiz_question_registry {

    private $capquiz;

    public function __construct(capquiz $capquiz) {
        $this->capquiz = $capquiz;
    }

    public function capquiz_instance() {
        return $this->capquiz;
    }

    public function question_ids(int $question_list_id) {
        $questions = $this->question_list($question_list_id)->questions();
        $ret = [];
        foreach ($questions as $question) {
            array_push($ret, $question->id());
        }
        return $ret;
    }

    public function question_list(int $list_id) {
        global $DB;
        if ($entry = $DB->get_record(database_meta::$table_capquiz_question_list, [database_meta::$field_id => $list_id])) {
            return new capquiz_question_list($entry);
        }
        return null;
    }

    public function question_lists() {
        global $DB;
        $records = [];
        foreach ($DB->get_records(database_meta::$table_capquiz_question_list) as $entry) {
            array_push($records, new capquiz_question_list($entry));
        }
        return $records;
    }

    public function has_question_lists() {
        return count($this->question_lists()) > 0;
    }

    public function create_question_list(string $title, string $description) {
        global $DB;
        $list = new \stdClass();
        $list->capquiz_id = $this->capquiz->course_module_id();
        $list->title = $title;
        $list->description = $description;
        $list->time_created = time();
        $list->time_modified = time();
        try {
            return $DB->insert_record(database_meta::$table_capquiz_question_list, $list);
        } catch (\Exception $e) {
            return false;
        }
    }

}
