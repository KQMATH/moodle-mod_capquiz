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

    /** @var \stdClass $record */
    private $record;

    /** @var capquiz_question[] $questions */
    private $questions;

    /** @var \question_usage_by_activity $quba */
    private $quba;

    public function __construct(\stdClass $record) {
        global $DB;
        $this->record = $record;
        $entries = $DB->get_records('capquiz_question', ['question_list_id' => $this->record->id]);
        $this->questions = [];
        foreach ($entries as $entry) {
            $this->questions[] = new capquiz_question($entry);
        }
        if ($this->has_question_usage()) {
            $this->quba = \question_engine::load_questions_usage_by_activity($this->record->question_usage_id);
        }
    }

    public function question_usage() {
        return $this->quba;
    }

    public function id() : int {
        return $this->record->id;
    }

    public function author() {
        global $DB;
        $criteria = ['id' => $this->record->author];
        $record = $DB->get_record('user', $criteria);
        // Returning null instead of false on failure.
        return $record ? $record : null;
    }

    public function has_questions() : bool {
        return count($this->questions) > 0;
    }

    public function is_template() : bool {
        return $this->record->is_template;
    }

    public function default_question_rating() : float {
        return $this->record->default_question_rating;
    }

    public function set_default_question_rating(float $rating) {
        global $DB;
        $this->record->default_question_rating = $rating;
        $DB->update_record('capquiz_question_list', $this->record);
    }

    public function title() : string {
        return $this->record->title;
    }

    public function description() : string {
        return $this->record->description;
    }

    public function first_level() : int {
        return 1;
    }

    public function level_count() : int {
        return 5;
    }

    public function required_rating_for_level(int $level) {
        $field = "level_{$level}_rating";
        if (!isset($this->record->{$field})) {
            return null;
        }
        return (int)$this->record->{$field};
    }

    public function set_level_ratings(array $ratings) {
        global $DB;
        $numratings = count($ratings);
        if ($numratings !== $this->level_count()) {
            throw new \Exception("$numratings ratings given. " . $this->level_count() . ' required.');
        }
        $level = $this->first_level();
        foreach ($ratings as $rating) {
            $field = "level_{$level}_rating";
            $this->record->{$field} = $rating;
            $level++;
        }
        $DB->update_record('capquiz_question_list', $this->record);
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
        return $this->record->time_created;
    }

    public function time_modified() : string {
        return $this->record->time_modified;
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

    public function question(int $questionid) {
        foreach ($this->questions as $question) {
            if ($question->id() === $questionid) {
                return $question;
            }
        }
        return null;
    }

    public function has_question(int $questionid) {
        foreach ($this->questions as $question) {
            if ($question->question_id() === $questionid) {
                return $question;
            }
        }
        return null;
    }

    /**
     * The questions from $that will be imported to this question list.
     *
     * @param capquiz_question_list $that The question list to import questions from.
     * @throws \dml_exception
     */
    public function merge(capquiz_question_list $that) {
        global $DB;
        foreach ($that->questions as $question) {
            if ($this->has_question($question->question_id()) === null) {
                $newquestion = new \stdClass();
                $newquestion->question_list_id = $this->id();
                $newquestion->question_id = $question->question_id();
                $newquestion->rating = $question->rating();
                $DB->insert_record('capquiz_question', $newquestion);
            }
        }
    }

    public function create_instance_copy(int $capquizid) {
        return $this->create_copy($capquizid, false);
    }

    public function convert_to_instance(int $capquizid) : bool {
        global $DB;
        if ($this->id() || !$this->is_template()) {
            return false;
        }
        $this->record->capquiz_id = $capquizid;
        $this->record->is_template = 0;
        $DB->update_record('capquiz_question_list', $this->record);
        return true;
    }

    public function create_template_copy() {
        return $this->create_copy(null, true);
    }

    public function create_question_usage($context) {
        global $DB;
        if ($this->has_question_usage()) {
            return;
        }
        $quba = \question_engine::make_questions_usage_by_activity('mod_capquiz', $context);
        $quba->set_preferred_behaviour('immediatefeedback');
        // TODO: Don't suppress the error if it becomes possible to save QUBAs without slots.
        @\question_engine::save_questions_usage_by_activity($quba);
        $this->record->question_usage_id = $quba->get_id();
        $DB->update_record('capquiz_question_list', $this->record);
    }

    private function has_question_usage() : bool {
        return $this->record->question_usage_id !== null;
    }

    private function copy_questions_to_list(int $qlistid) {
        global $DB;
        foreach ($this->questions() as $question) {
            $record = $question->entry();
            $record->id = null;
            $record->question_list_id = $qlistid;
            $DB->insert_record('capquiz_question', $record);
        }
    }

    private function create_copy($capquizid, bool $template) {
        global $DB;
        if (!$capquizid && !$template) {
            return null;
        }
        $record = $this->record;
        $record->id = null;
        $record->capquiz_id = $capquizid;
        $record->question_usage_id = null;
        $record->is_template = $template;
        $transaction = $DB->start_delegated_transaction();
        try {
            $newid = $DB->insert_record('capquiz_question_list', $record);
            $this->copy_questions_to_list($newid);
            $DB->commit_delegated_transaction($transaction);
            $record->id = $newid;
            return new capquiz_question_list($record);
        } catch (\dml_exception $exception) {
            $DB->rollback_delegated_transaction($transaction, $exception);
            return null;
        }
    }

    public static function create_new_instance(capquiz $capquiz, string $title, string $description, array $ratings) {
        global $DB, $USER;
        if (count($ratings) < 5) {
            return null;
        }
        $record = new \stdClass();
        $record->capquiz_id = $capquiz->id();
        $record->title = $title;
        $record->description = $description;
        $record->level_1_rating = $ratings[0];
        $record->level_2_rating = $ratings[1];
        $record->level_3_rating = $ratings[2];
        $record->level_4_rating = $ratings[3];
        $record->level_5_rating = $ratings[4];
        $record->author = $USER->id;
        $record->is_template = 0;
        $record->time_created = time();
        $record->time_modified = time();
        try {
            $qlistid = $DB->insert_record('capquiz_question_list', $record);
            $qlist = self::load_any($qlistid);
            if (!$qlist) {
                return null;
            }
            $capquiz->validate_matchmaking_and_rating_systems();
            return $qlist;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function load_question_list(capquiz $capquiz) {
        global $DB;
        $conditions = ['capquiz_id' => $capquiz->id()];
        $record = $DB->get_record('capquiz_question_list', $conditions);
        if ($record) {
            return new capquiz_question_list($record);
        }
        return null;
    }

    public static function load_any(int $qlistid) {
        global $DB;
        $conditions = ['id' => $qlistid];
        $record = $DB->get_record('capquiz_question_list', $conditions);
        if ($record) {
            return new capquiz_question_list($record);
        }
        return null;
    }

    public static function load_question_list_templates() : array {
        global $DB;
        $records = $DB->get_records('capquiz_question_list', ['is_template' => 1]);
        $qlists = [];
        foreach ($records as $record) {
            $qlists[] = new capquiz_question_list($record);
        }
        return $qlists;
    }

}
