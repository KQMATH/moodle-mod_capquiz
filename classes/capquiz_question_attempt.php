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
class capquiz_question_attempt {

    /** @var \stdClass $record */
    private $record;

    /** @var \question_usage_by_activity $quba */
    private $quba;

    public function __construct(\question_usage_by_activity $quba, \stdClass $record) {
        $this->record = $record;
        $this->quba = $quba;
    }

    public static function create_attempt(capquiz $capquiz, capquiz_user $user, capquiz_question $question) {
        $quba = $capquiz->question_usage();
        $questions = question_load_questions([$question->question_id()]);
        $targetquestion = reset($questions);
        if (!$targetquestion) {
            return null;
        }
        $questiondefinition = \question_bank::make_question($targetquestion);
        $slot = $quba->add_question($questiondefinition);
        $quba->start_question($slot);
        \question_engine::save_questions_usage_by_activity($quba);
        return self::insert_attempt_entry($capquiz, $user, $question, $slot);
    }

    public static function active_attempt(capquiz $capquiz, capquiz_user $user) {
        global $DB;
        $criteria = [
            'user_id' => $user->id(),
            'reviewed' => false
        ];
        try {
            $entry = $DB->get_record('capquiz_attempt', $criteria, '*', MUST_EXIST);
            return new capquiz_question_attempt($capquiz->question_usage(), $entry);
        } catch (\dml_exception $e) {
            return null;
        }
    }

    public static function load_attempt(capquiz $capquiz, capquiz_user $user, int $attemptid) {
        global $DB;
        $criteria = [
            'id' => $attemptid,
            'user_id' => $user->id()
        ];
        try {
            $entry = $DB->get_record('capquiz_attempt', $criteria, '*', MUST_EXIST);
            return new capquiz_question_attempt($capquiz->question_usage(), $entry);
        } catch (\dml_exception $e) {
            return null;
        }
    }

    public static function previous_attempt(capquiz $capquiz, capquiz_user $user) {
        global $DB;
        try {
            $sql = 'SELECT * FROM {capquiz_attempt} WHERE user_id = ? ORDER BY time_reviewed DESC LIMIT 1';
            $attempt = $DB->get_record_sql($sql, [$user->id()], MUST_EXIST);
            return new capquiz_question_attempt($capquiz->question_usage(), $attempt);
        } catch (\dml_exception $e) {
            return null;
        }
    }

    public static function inactive_attempts(capquiz $capquiz, capquiz_user $user) : array {
        global $DB;
        $records = [];
        $criteria = [
            'user_id' => $user->id(),
            'answered' => true,
            'reviewed' => true
        ];
        foreach ($DB->get_records('capquiz_attempt', $criteria) as $entry) {
            array_push($records, new capquiz_question_attempt($capquiz->question_usage(), $entry));
        }
        return $records;
    }

    public function id() : int {
        return $this->record->id;
    }

    public function moodle_attempt_id() : int {
        return $this->record->attempt_id;
    }

    public function question_id() : int {
        return $this->record->question_id;
    }

    public function question_slot() : int {
        return $this->record->slot;
    }

    public function is_answered() : bool {
        return $this->record->answered;
    }

    public function is_correctly_answered() : bool {
        if (!$this->is_answered()) {
            return false;
        }
        $moodleattempt = $this->quba->get_question_attempt($this->question_slot());
        return $moodleattempt->get_state()->is_correct();
    }

    public function is_reviewed() : bool {
        return $this->record->reviewed;
    }

    public function is_pending() : bool {
        return !$this->is_reviewed();
    }

    public function mark_as_answered() : bool {
        global $DB;
        $submitteddata = $this->quba->extract_responses($this->question_slot());
        $this->quba->process_action($this->question_slot(), $submitteddata);
        $record = $this->record;
        $record->answered = true;
        $record->time_answered = time();
        $this->quba->finish_question($this->question_slot(), time());
        \question_engine::save_questions_usage_by_activity($this->quba);
        try {
            $DB->update_record('capquiz_attempt', $record);
            $this->record = $record;
            return true;
        } catch (\dml_exception $e) {
            return false;
        }
    }

    public function mark_as_reviewed() : bool {
        global $DB;
        $record = $this->record;
        $record->reviewed = true;
        $record->time_reviewed = time();
        try {
            $DB->update_record('capquiz_attempt', $record);
            $this->record = $record;
            return true;
        } catch (\dml_exception $e) {
            return false;
        }
    }

    private static function insert_attempt_entry(capquiz $capquiz, capquiz_user $user, capquiz_question $question, int $slot) {
        global $DB;
        $record = new \stdClass();
        $record->slot = $slot;
        $record->user_id = $user->id();
        $record->question_id = $question->id();
        try {
            $DB->insert_record('capquiz_attempt', $record);
            return self::active_attempt($capquiz, $user);
        } catch (\dml_exception $e) {
            return null;
        }
    }

}