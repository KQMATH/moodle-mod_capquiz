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

    /**
     * capquiz_question_attempt constructor.
     * @param \question_usage_by_activity $quba
     * @param \stdClass $record
     */
    public function __construct(\question_usage_by_activity $quba, \stdClass $record) {
        $this->record = $record;
        $this->quba = $quba;
    }

    /**
     * @param capquiz_user $user
     * @param capquiz_question $question
     * @return capquiz_question_attempt|null
     */
    public static function create_attempt(capquiz_user $user, capquiz_question $question) {
        $quba = $user->question_usage();
        $questions = question_load_questions([$question->question_id()]);
        $targetquestion = reset($questions);
        if (!$targetquestion) {
            return null;
        }
        $questiondefinition = \question_bank::make_question($targetquestion);
        $slot = $quba->add_question($questiondefinition);
        $quba->start_question($slot);
        \question_engine::save_questions_usage_by_activity($quba);
        return self::insert_attempt_entry($user, $question, $slot);
    }

    /**
     * @param capquiz_user $user
     * @return capquiz_question_attempt|null
     */
    public static function active_attempt(capquiz_user $user) {
        global $DB;
        try {
            $entry = $DB->get_record('capquiz_attempt', [
                'user_id' => $user->id(),
                'reviewed' => false
            ], '*', MUST_EXIST);
            return new capquiz_question_attempt($user->question_usage(), $entry);
        } catch (\dml_exception $e) {
            return null;
        }
    }

    /**
     * @param capquiz_user $user
     * @param int $attemptid
     * @return capquiz_question_attempt|null
     */
    public static function load_attempt(capquiz_user $user, int $attemptid) {
        global $DB;
        try {
            $entry = $DB->get_record('capquiz_attempt', [
                'id' => $attemptid,
                'user_id' => $user->id()
            ], '*', MUST_EXIST);
            return new capquiz_question_attempt($user->question_usage(), $entry);
        } catch (\dml_exception $e) {
            return null;
        }
    }

    /**
     * @param capquiz_user $user
     * @return capquiz_question_attempt|null
     */
    public static function previous_attempt(capquiz_user $user) {
        global $DB;
        try {
            $sql = 'SELECT *
                      FROM {capquiz_attempt}
                     WHERE user_id = :userid
                  ORDER BY time_reviewed DESC
                     LIMIT 1';
            $attempt = $DB->get_record_sql($sql, ['userid' => $user->id()], MUST_EXIST);
            return new capquiz_question_attempt($user->question_usage(), $attempt);
        } catch (\dml_exception $e) {
            return null;
        }
    }

    /**
     * @param capquiz_user $user
     * @return capquiz_question_attempt[]
     * @throws \dml_exception
     */
    public static function inactive_attempts(capquiz_user $user) : array {
        global $DB;
        $entries = $DB->get_records('capquiz_attempt', [
            'user_id' => $user->id(),
            'answered' => true,
            'reviewed' => true
        ]);
        $records = [];
        foreach ($entries as $entry) {
            array_push($records, new capquiz_question_attempt($user->question_usage(), $entry));
        }
        return $records;
    }

    public function id() : int {
        return $this->record->id;
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

    public function get_state() : \question_state {
        $moodleattempt = $this->quba->get_question_attempt($this->question_slot());
        return $moodleattempt->get_state();
    }

    public function is_reviewed() : bool {
        return $this->record->reviewed;
    }

    public function is_pending() : bool {
        return !$this->is_reviewed();
    }

    public function is_question_valid() : bool {
        global $DB;
        $sql = 'SELECT cq.id
                  FROM {capquiz_attempt} ca
                  JOIN {capquiz_question} cq
                    ON ca.question_id = cq.id
                 WHERE ca.id = :attemptid';
        $result = $DB->get_record_sql($sql, ['attemptid' => $this->id()]);
        return $result !== false;
    }

    public function delete() {
        global $DB;
        $DB->execute('DELETE FROM {capquiz_attempt} WHERE id = :id', ['id' => $this->id()]);
    }

    public function mark_as_answered() {
        global $DB;
        $submitteddata = $this->quba->extract_responses($this->question_slot());
        $this->quba->process_action($this->question_slot(), $submitteddata);
        $this->record->answered = true;
        $this->record->time_answered = time();
        $this->quba->finish_question($this->question_slot(), time());
        \question_engine::save_questions_usage_by_activity($this->quba);
        $DB->update_record('capquiz_attempt', $this->record);
    }

    public function mark_as_reviewed() {
        global $DB;
        $this->record->reviewed = true;
        $this->record->time_reviewed = time();
        $DB->update_record('capquiz_attempt', $this->record);
    }

    public function set_question_rating(capquiz_question_rating $rating, $previous = false) {
        global $DB;
        if (!$previous) {
            $this->record->question_rating_id = $rating->id();
        } else {
            $this->record->question_prev_rating_id = $rating->id();
        }
        $DB->update_record('capquiz_attempt', $this->record);
    }

    public function set_previous_question_rating(capquiz_question_rating $rating, $previous = false) {
        global $DB;
        if (!$previous) {
            $this->record->prev_question_rating_id = $rating->id();
        } else {
            $this->record->prev_question_prev_rating_id = $rating->id();
        }
        $DB->update_record('capquiz_attempt', $this->record);
    }

    public function set_user_rating(capquiz_user_rating $rating, $previous = false) {
        global $DB;
        if (!$previous) {
            $this->record->user_rating_id = $rating->id();
        } else {
            $this->record->user_prev_rating_id = $rating->id();
        }
        $DB->update_record('capquiz_attempt', $this->record);
    }

    /**
     * @param capquiz_user $user
     * @param capquiz_question $question
     * @param int $slot
     * @return capquiz_question_attempt|null
     */
    private static function insert_attempt_entry(capquiz_user $user, capquiz_question $question, int $slot) {
        global $DB;
        $record = new \stdClass();
        $record->slot = $slot;
        $record->user_id = $user->id();
        $record->question_id = $question->id();
        try {
            $DB->insert_record('capquiz_attempt', $record);
            return self::active_attempt($user);
        } catch (\dml_exception $e) {
            return null;
        }
    }

}
