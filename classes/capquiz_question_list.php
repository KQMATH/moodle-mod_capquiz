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

    /** @var capquiz $capquiz */
    private $capquiz;

    public function __construct(\stdClass $db_entry, capquiz $capquiz) {
        global $DB;
        $this->db_entry = $db_entry;
        $this->capquiz = $capquiz;
        $this->questions = [];
        $entries = $DB->get_records(database_meta::$table_capquiz_question, [
            database_meta::$field_question_list_id => $this->db_entry->id
        ]);
        foreach ($entries as $entry) {
            $this->questions[] = new capquiz_question($entry);
        }
    }

    public function id() : int {
        return $this->db_entry->id;
    }

    public function author() /*: ?\stdClass*/ {
        global $DB;
        $criteria = [
            database_meta::$field_id => $this->db_entry->author
        ];
        if ($entry = $DB->get_record(database_meta::$table_moodle_user, $criteria)) {
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

    public function capquiz_origin_id() : int {
        return $this->db_entry->capquiz_origin_id;
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

    public function set_level_rating(int $level, int $rating) : void {
        $db_entry = $this->db_entry;
        $field = "level_{$level}_rating";
        $db_entry->{$field} = $rating;
        $this->update_database($db_entry);
    }

    public function set_level_ratings(array $ratings) : void {
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

    public function next_level_percent(int $rating) : int {
        $goal = 0;
        for ($level = 1; $level < 6; $level++) {
            $goal = $this->required_rating_for_level($level);
            if ($goal > $rating) {
                $previous = $this->capquiz->default_user_rating();
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

    public static function load_question_list(capquiz $capquiz, int $question_list_id) /*: ?capquiz_question_list*/ {
        global $DB;
        $conditions = [database_meta::$field_id => $question_list_id, database_meta::$field_is_template => 0];
        if ($entry = $DB->get_record(database_meta::$table_capquiz_question_list, $conditions)) {
            return new capquiz_question_list($entry, $capquiz);
        }
        return null;
    }

    public static function load_question_template(capquiz $capquiz, int $question_list_id) /*: ?capquiz_question_list*/ {
        $conditions = [database_meta::$field_id => $question_list_id, database_meta::$field_is_template => 1];
        global $DB;
        if ($entry = $DB->get_record(database_meta::$table_capquiz_question_list, $conditions)) {
            return new capquiz_question_list($entry, $capquiz);
        }
        return null;
    }

    public static function load_any(capquiz $capquiz, int $question_list_id) /*: ?capquiz_question_list*/ {
        global $DB;
        $conditions = [database_meta::$field_id => $question_list_id];
        if ($entry = $DB->get_record(database_meta::$table_capquiz_question_list, $conditions)) {
            return new capquiz_question_list($entry, $capquiz);
        }
        return null;
    }

    public static function load_question_lists(capquiz $capquiz) : array {
        return capquiz_question_list::load_question_lists_from_criteria($capquiz, [database_meta::$field_is_template => 0]);
    }

    public static function load_question_list_templates(capquiz $capquiz) : array {
        return capquiz_question_list::load_question_lists_from_criteria($capquiz, [database_meta::$field_is_template => 1]);
    }

    public static function copy(capquiz_question_list $question_list, bool $insert_as_template) : int {
        global $DB;
        $question_list_entry = $question_list->db_entry;
        $question_list_id = $question_list_entry->id;
        $question_list_entry->id = null;
        $question_list_entry->is_template = $insert_as_template ? 1 : 0;
        $transaction = $DB->start_delegated_transaction();
        try {
            $questions = $DB->get_records(database_meta::$table_capquiz_question, [database_meta::$field_question_list_id => $question_list_id]);
            $question_list_id = $DB->insert_record(database_meta::$table_capquiz_question_list, $question_list_entry);
            foreach ($questions as $question) {
                $question->id = null;
                $question->question_list_id = $question_list_id;
                $DB->insert_record(database_meta::$table_capquiz_question, $question);
            }
            $DB->commit_delegated_transaction($transaction);
            return $question_list_id;
        } catch (\dml_exception $exception) {
            $DB->rollback_delegated_transaction($transaction, $exception);
            return 0;
        }
    }

    private function update_database(\stdClass $db_entry) : void {
        global $DB;
        if ($DB->update_record(database_meta::$table_capquiz_question_list, $db_entry)) {
            $this->db_entry = $db_entry;
        }
    }

    private static function load_question_lists_from_criteria(capquiz $capquiz, array $conditions) : array {
        global $DB;
        $lists = [];
        $records = $DB->get_records(database_meta::$table_capquiz_question_list, $conditions);
        foreach ($records as $record) {
            $lists[] = new capquiz_question_list($record, $capquiz);
        }
        return $lists;
    }

}
