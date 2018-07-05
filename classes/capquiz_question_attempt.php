<?php

namespace mod_capquiz;

defined('MOODLE_INTERNAL') || die();

class capquiz_question_attempt {

    private $db_entry;
    private $question_usage;

    public function __construct(\question_usage_by_activity $question_usage, \stdClass $db_entry) {
        $this->db_entry = $db_entry;
        $this->question_usage = $question_usage;
    }

    public static function create_attempt(capquiz $capquiz, capquiz_user $user, capquiz_question $question) {
        $question_usage = $capquiz->question_usage();
        $questions = question_load_questions([$question->question_id()]);
        $target_question = reset($questions);
        if (!$target_question)
            return null;
        $question_definition = \question_bank::make_question($target_question);
        $slot = $question_usage->add_question($question_definition);
        $question_usage->start_question($slot);
        \question_engine::save_questions_usage_by_activity($question_usage);
        return self::insert_attempt_entry($capquiz, $user, $question, $slot);
    }

    public static function active_attempt(capquiz $capquiz, capquiz_user $user) {
        global $DB;
        $criteria = [
            database_meta::$field_user_id => $user->id(),
            database_meta::$field_reviewed => false
        ];
        if ($entry = $DB->get_record(database_meta::$table_capquiz_attempt, $criteria)) {
            return new capquiz_question_attempt($capquiz->question_usage(), $entry);
        }
        return null;
    }

    public static function load_attempt(capquiz $capquiz, capquiz_user $user, int $attempt_id) {
        global $DB;
        $criteria = [
            database_meta::$field_id => $attempt_id,
            database_meta::$field_user_id => $user->id()
        ];
        if ($entry = $DB->get_record(database_meta::$table_capquiz_attempt, $criteria)) {
            return new capquiz_question_attempt($capquiz->question_usage(), $entry);
        }
        return null;
    }

    public static function inactive_attempts(capquiz $capquiz, capquiz_user $user) {
        global $DB;
        $records = [];
        $criteria = [
            database_meta::$field_user_id => $user->id(),
            database_meta::$field_answered => true,
            database_meta::$field_reviewed => true
        ];
        foreach ($DB->get_records(database_meta::$table_capquiz_attempt, $criteria) as $entry) {
            array_push($records, new capquiz_question_attempt($capquiz->question_usage(), $entry));
        }
        return $records;
    }

    public function id() {
        return $this->db_entry->id;
    }

    public function moodle_attempt_id() {
        return $this->db_entry->attempt_id;
    }

    public function question_id() {
        return $this->db_entry->question_id;
    }

    public function question_slot() {
        return $this->db_entry->slot;
    }

    public function question_usage() {
        return $this->question_usage;
    }

    public function is_answered() {
        return $this->db_entry->answered;
    }

    public function is_correctly_answered() {
        if (!$this->is_answered()) {
            return false;
        }
        $moodle_attempt = $this->question_usage->get_question_attempt($this->question_slot());
        return $moodle_attempt->get_state()->is_correct();
    }

    public function is_reviewed() {
        return $this->db_entry->reviewed;
    }

    public function is_pending() {
        return !$this->is_reviewed();
    }

    public function mark_as_answered() {
        global $DB;
        $submitteddata = $this->question_usage->extract_responses($this->question_slot(), $_POST);
        $this->question_usage->process_action($this->question_slot(), $submitteddata);
        $db_entry = $this->db_entry;
        $db_entry->answered = true;
        $this->question_usage->finish_question($this->question_slot(), time());
        \question_engine::save_questions_usage_by_activity($this->question_usage);
        try {
            if ($DB->update_record(database_meta::$table_capquiz_attempt, $db_entry)) {
                $this->db_entry = $db_entry;
            } else {
                throw new \Exception("Unable to mark attempt as answered");
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function mark_as_reviewed() {
        global $DB;
        $db_entry = $this->db_entry;
        $db_entry->reviewed = true;
        try {
            if ($DB->update_record(database_meta::$table_capquiz_attempt, $db_entry)) {
                $this->db_entry = $db_entry;
            } else {
                throw new \Exception("Unable to mark attempt as reviewed");
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private static function insert_attempt_entry(capquiz $capquiz, capquiz_user $user, capquiz_question $question, int $slot) {
        $attempt_entry = new \stdClass();
        $attempt_entry->slot = $slot;
        $attempt_entry->user_id = $user->id();
        $attempt_entry->question_id = $question->id();
        global $DB;
        try {
            if ($DB->insert_record(database_meta::$table_capquiz_attempt, $attempt_entry)) {
                return self::active_attempt($capquiz, $user);
            } else {
                throw new \Exception("Unable to store new attempt");
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

}