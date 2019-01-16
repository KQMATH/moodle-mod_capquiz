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
class capquiz_question_list {

    /** @var \stdClass $db_entry */
    private $db_entry;

    /** @var capquiz_question[] $questions */
    private $questions;

    /** @var \question_usage_by_activity $quba */
    private $quba;

    public function __construct(\stdClass $db_entry) {
        global $DB;
        $this->db_entry = $db_entry;
        $entries = $DB->get_records(database_meta::$table_capquiz_question, [
            database_meta::$field_question_list_id => $this->db_entry->id
        ]);
        $this->questions = [];
        foreach ($entries as $entry) {
            $this->questions[] = new capquiz_question($entry);
        }
        if ($this->has_question_usage()) {
            $this->quba = \question_engine::load_questions_usage_by_activity($this->db_entry->question_usage_id);
        }
    }

    public function question_usage() {
        return $this->quba;
    }

    public function id() : int {
        return $this->db_entry->id;
    }

    public function author() /*: ?\stdClass*/ {
        global $DB;
        $criteria = [database_meta::$field_id => $this->db_entry->author];
        $entry = $DB->get_record(database_meta::$table_moodle_user, $criteria);
        if ($entry) {
            return $entry;
        } else {
            return null;
        }
    }

    public function can_create_template() : bool {
        return $this->has_questions();
    }

    public function has_questions() : bool {
        return count($this->questions) > 0;
    }

    public function is_template() : bool {
        return $this->db_entry->is_template;
    }

    public function default_question_rating() : float {
        return $this->db_entry->default_question_rating;
    }

    public function title() : string {
        return $this->db_entry->title;
    }

    public function description() : string {
        return $this->db_entry->description;
    }

    public function first_level() : int {
        return 1;
    }

    public function level_count() : int {
        return 5;
    }

    public function required_rating_for_level(int $level) /*: ?int*/ {
        $field = "level_{$level}_rating";
        if (!isset($this->db_entry->{$field})) {
            return null;
        }
        return (int)$this->db_entry->{$field};
    }

    public function set_level_rating(int $level, int $rating) /*: void*/ {
        $db_entry = $this->db_entry;
        $field = "level_{$level}_rating";
        $db_entry->{$field} = $rating;
        $this->update_database($db_entry);
    }

    public function set_level_ratings(array $ratings) /*: void*/ {
        $counts = count($ratings);
        if ($counts !== $this->level_count()) {
            throw new \Exception("Wrong number of ratings specified for badges: $counts given and " . $this->level_count() . ' required');
        }
        $db_entry = $this->db_entry;
        $level = $this->first_level();
        foreach ($ratings as $rating) {
            $field = "level_{$level}_rating";
            $db_entry->{$field} = $rating;
            $level++;
        }
        $this->update_database($db_entry);
    }

    public function user_level(capquiz_user $user) : int {
        $stars = 0;
        for ($level = 1; $level < 6; $level++) {
            if ($user->rating() >= $this->required_rating_for_level($level)) {
                $stars++;
            }
        }
        return $stars;
    }

    public function next_level_percent(capquiz $capquiz, int $rating) : int {
        $goal = 0;
        for ($level = 1; $level < 6; $level++) {
            $goal = $this->required_rating_for_level($level);
            if ($goal > $rating) {
                $previous = $capquiz->default_user_rating();
                if ($level > 1) {
                    $previous = $this->required_rating_for_level($level - 1);
                }
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

    public function time_created() : string {
        return $this->db_entry->time_created;
    }

    public function time_modified() : string {
        return $this->db_entry->time_modified;
    }

    public function question_count() : int {
        return count($this->questions);
    }

    /**
     * @return capquiz_question[]
     */
    public function questions() : array {
        return $this->questions;
    }

    public function question(int $question_id) /*: ?capquiz_question*/ {
        foreach ($this->questions as $question) {
            if ($question->id() === $question_id) {
                return $question;
            }
        }
        return null;
    }

    public function create_instance_copy(int $capquiz_id) /*: ?capquiz_question_list */ {
        return $this->create_copy($capquiz_id, false);
    }

    public function convert_to_instance(int $capquizid) : bool {
        if ($this->id() || !$this->is_template()) {
            return false;
        }
        $this->db_entry->capquiz_id = $capquizid;
        $this->db_entry->is_template = 0;
        $this->update_database($this->db_entry);
        return true;
    }

    public function create_template_copy() /*: ?capquiz_question_list */ {
        return $this->create_copy(null, true);
    }

    public function has_question_usage() : bool {
        return $this->db_entry->question_usage_id !== null;
    }

    public function create_question_usage($context) {
        if ($this->has_question_usage()) {
            return;
        }
        $qusage = \question_engine::make_questions_usage_by_activity('mod_capquiz', $context);
        $qusage->set_preferred_behaviour('immediatefeedback');
        // TODO: Don't suppress the error if it becomes possible to save QUBAs without slots.
        @\question_engine::save_questions_usage_by_activity($qusage);
        $this->db_entry->question_usage_id = $qusage->get_id();
        $this->update_database($this->db_entry);
    }

    private function copy_questions_to_list(int $qlistid) {
        global $DB;
        foreach ($this->questions() as $question) {
            $qentry = $question->entry();
            $qentry->id = null;
            $qentry->question_list_id = $qlistid;
            $DB->insert_record(database_meta::$table_capquiz_question, $qentry);
        }
    }

    private function create_copy($capquizid, bool $template) {
        global $DB;
        if (!$capquizid && !$template) {
            return null;
        }
        $newentry = $this->db_entry;
        $newentry->id = null;
        $newentry->capquiz_id = $capquizid;
        $newentry->question_usage_id = null;
        $newentry->is_template = $template;
        $transaction = $DB->start_delegated_transaction();
        try {
            $newid = $DB->insert_record(database_meta::$table_capquiz_question_list, $newentry);
            $this->copy_questions_to_list($newid);
            $DB->commit_delegated_transaction($transaction);
            $newentry->id = $newid;
            return new capquiz_question_list($newentry);
        } catch (\dml_exception $exception) {
            $DB->rollback_delegated_transaction($transaction, $exception);
            return null;
        }
    }

    private function update_database(\stdClass $db_entry) /*: void*/ {
        global $DB;
        if ($DB->update_record(database_meta::$table_capquiz_question_list, $db_entry)) {
            $this->db_entry = $db_entry;
        }
    }

    public static function create_new_instance(capquiz $capquiz, string $title, string $description, array $ratings) {
        global $DB, $USER;
        if (count($ratings) < 5) {
            return null;
        }
        $entry = new \stdClass();
        $entry->capquiz_id = $capquiz->id();
        $entry->title = $title;
        $entry->description = $description;
        $entry->level_1_rating = $ratings[0];
        $entry->level_2_rating = $ratings[1];
        $entry->level_3_rating = $ratings[2];
        $entry->level_4_rating = $ratings[3];
        $entry->level_5_rating = $ratings[4];
        $entry->author = $USER->id;
        $entry->is_template = 0;
        $entry->time_created = time();
        $entry->time_modified = time();
        try {
            $qlistid = $DB->insert_record(database_meta::$table_capquiz_question_list, $entry);
            $qlist = capquiz_question_list::load_any($qlistid);
            if (!$qlist) {
                return null;
            }
            $capquiz->validate_matchmaking_and_rating_systems();
            return $qlist;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function load_question_list(capquiz $capquiz) /*: ?capquiz_question_list*/ {
        global $DB;
        $conditions = [database_meta::$field_capquiz_id => $capquiz->id()];
        $entry = $DB->get_record(database_meta::$table_capquiz_question_list, $conditions);
        if ($entry) {
            return new capquiz_question_list($entry);
        }
        return null;
    }

    public static function load_any(int $question_list_id) /*: ?capquiz_question_list*/ {
        global $DB;
        $conditions = [database_meta::$field_id => $question_list_id];
        $entry = $DB->get_record(database_meta::$table_capquiz_question_list, $conditions);
        if ($entry) {
            return new capquiz_question_list($entry);
        }
        return null;
    }

    public static function load_question_list_templates() : array {
        return capquiz_question_list::load_question_lists_from_criteria([database_meta::$field_is_template => 1]);
    }

    private static function load_question_lists_from_criteria(array $conditions) : array {
        global $DB;
        $lists = [];
        $records = $DB->get_records(database_meta::$table_capquiz_question_list, $conditions);
        foreach ($records as $record) {
            $lists[] = new capquiz_question_list($record);
        }
        return $lists;
    }

}
