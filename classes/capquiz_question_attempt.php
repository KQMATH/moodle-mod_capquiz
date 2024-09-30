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

/**
 * This file defines a class represeting a capquiz question attempt
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz;

use question_bank;
use question_engine;
use question_state;
use question_usage_by_activity;
use stdClass;

/**
 * Class capquiz_question_attempt
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_question_attempt {

    /** @var stdClass $record */
    private stdClass $record;

    /** @var question_usage_by_activity $quba */
    private question_usage_by_activity $quba;

    /**
     * Constructor.
     *
     * @param question_usage_by_activity $quba
     * @param stdClass $record
     */
    public function __construct(question_usage_by_activity $quba, stdClass $record) {
        $this->record = $record;
        $this->quba = $quba;
    }

    /**
     * Creates a new question attempt for a user
     *
     * @param capquiz_user $user
     * @param capquiz_question $question
     */
    public static function create_attempt(capquiz_user $user, capquiz_question $question): ?capquiz_question_attempt {
        $quba = $user->question_usage();
        $questions = question_load_questions([$question->question_id()]);
        $targetquestion = reset($questions);
        if (!$targetquestion) {
            return null;
        }
        $questiondefinition = question_bank::make_question($targetquestion);
        $slot = $quba->add_question($questiondefinition);
        $quba->start_question($slot);
        question_engine::save_questions_usage_by_activity($quba);
        return self::insert_attempt_entry($user, $question, $slot);
    }

    /**
     * Returns the users currently active attempt
     *
     * @param capquiz_user $user
     */
    public static function active_attempt(capquiz_user $user): ?capquiz_question_attempt {
        global $DB;
        $entry = $DB->get_record('capquiz_attempt', ['user_id' => $user->id(), 'reviewed' => false]);
        if (empty($entry)) {
            return null;
        }
        return new capquiz_question_attempt($user->question_usage(), $entry);
    }

    /**
     * Loads a users attempt based on the user and attempt id
     *
     * @param capquiz_user $user
     * @param int $attemptid
     */
    public static function load_attempt(capquiz_user $user, int $attemptid): ?capquiz_question_attempt {
        global $DB;
        $entry = $DB->get_record('capquiz_attempt', ['id' => $attemptid, 'user_id' => $user->id()]);
        if (empty($entry)) {
            return null;
        }
        return new capquiz_question_attempt($user->question_usage(), $entry);
    }

    /**
     * Returns the users previous attempt
     *
     * @param capquiz_user $user
     */
    public static function previous_attempt(capquiz_user $user): ?capquiz_question_attempt {
        global $DB;
        $sql = 'SELECT *
                  FROM {capquiz_attempt}
                 WHERE user_id = :userid
              ORDER BY time_reviewed DESC
                 LIMIT 1';
        $attempt = $DB->get_record_sql($sql, ['userid' => $user->id()], MUST_EXIST);
        return new capquiz_question_attempt($user->question_usage(), $attempt);
    }

    /**
     * Returns the users inactive attempts (Answered and reviewed)
     *
     * @param capquiz_user $user
     * @return capquiz_question_attempt[]
     */
    public static function inactive_attempts(capquiz_user $user): array {
        global $DB;
        $records = $DB->get_records('capquiz_attempt', [
            'user_id' => $user->id(),
            'answered' => true,
            'reviewed' => true,
        ]);
        return array_map(function (stdClass $record) use ($user) {
            return new capquiz_question_attempt($user->question_usage(), $record);
        }, array_values($records));
    }

    /**
     * Returns the attempts id
     */
    public function id(): int {
        return $this->record->id;
    }

    /**
     * Returns the id of the question
     */
    public function question_id(): int {
        return $this->record->question_id;
    }

    /**
     * Returns the slot of the question
     */
    public function question_slot(): int {
        return $this->record->slot;
    }

    /**
     * Returns true if the attempt has an answer
     */
    public function is_answered(): bool {
        return $this->record->answered;
    }

    /**
     * Returns true if the answer is correct
     */
    public function is_correctly_answered(): bool {
        if (!$this->is_answered()) {
            return false;
        }
        $moodleattempt = $this->quba->get_question_attempt($this->question_slot());
        return $moodleattempt->get_state()->is_correct();
    }

    /**
     * Returns the state of the question
     */
    public function get_state(): question_state {
        $moodleattempt = $this->quba->get_question_attempt($this->question_slot());
        return $moodleattempt->get_state();
    }

    /**
     * Returns true if the attempt is reviewed
     */
    public function is_reviewed(): bool {
        return $this->record->reviewed;
    }

    /**
     * Returns true if the attempt is not reviewed
     */
    public function is_pending(): bool {
        return !$this->is_reviewed();
    }

    /**
     * Checks if the question is valid
     */
    public function is_question_valid(): bool {
        global $DB;
        $sql = 'SELECT cq.id
                  FROM {capquiz_attempt} ca
                  JOIN {capquiz_question} cq
                    ON ca.question_id = cq.id
                 WHERE ca.id = :attemptid';
        $result = $DB->get_record_sql($sql, ['attemptid' => $this->id()]);
        return $result !== false;
    }

    /**
     * Deletes attempt from database
     */
    public function delete(): void {
        global $DB;
        $DB->delete_records('capquiz_attempt', ['id' => $this->id()]);
    }

    /**
     * Marks attempt as answered
     */
    public function mark_as_answered(): void {
        global $DB;
        $submitteddata = $this->quba->extract_responses($this->question_slot());
        $this->quba->process_action($this->question_slot(), $submitteddata);
        $this->record->answered = true;
        $this->record->time_answered = time();
        $this->quba->finish_question($this->question_slot(), time());
        question_engine::save_questions_usage_by_activity($this->quba);
        $DB->update_record('capquiz_attempt', $this->record);
    }

    /**
     * Marks attempt as viewed
     */
    public function mark_as_reviewed(): void {
        global $DB;
        $this->record->reviewed = true;
        $this->record->time_reviewed = time();
        $DB->update_record('capquiz_attempt', $this->record);
    }

    /**
     * Sets current question rating
     *
     * @param capquiz_question_rating $rating
     * @param bool $previous
     */
    public function set_question_rating(capquiz_question_rating $rating, bool $previous = false): void {
        global $DB;
        if ($previous) {
            $this->record->question_prev_rating_id = $rating->id();
        } else {
            $this->record->question_rating_id = $rating->id();
        }
        $DB->update_record('capquiz_attempt', $this->record);
    }

    /**
     * Sets rating for the previous rating
     *
     * @param capquiz_question_rating $rating
     * @param bool $previous
     */
    public function set_previous_question_rating(capquiz_question_rating $rating, bool $previous = false): void {
        global $DB;
        if ($previous) {
            $this->record->prev_question_prev_rating_id = $rating->id();
        } else {
            $this->record->prev_question_rating_id = $rating->id();
        }
        $DB->update_record('capquiz_attempt', $this->record);
    }

    /**
     * Sets the user rating of the attempt
     *
     * @param capquiz_user_rating $rating
     * @param bool $previous
     */
    public function set_user_rating(capquiz_user_rating $rating, bool $previous = false): void {
        global $DB;
        if ($previous) {
            $this->record->user_prev_rating_id = $rating->id();
        } else {
            $this->record->user_rating_id = $rating->id();
        }
        $DB->update_record('capquiz_attempt', $this->record);
    }

    /**
     * Inserts an attempt into the database
     *
     * @param capquiz_user $user
     * @param capquiz_question $question
     * @param int $slot
     */
    private static function insert_attempt_entry(capquiz_user $user, capquiz_question $question,
                                                 int $slot): ?capquiz_question_attempt {
        global $DB;
        $record = new stdClass();
        $record->slot = $slot;
        $record->user_id = $user->id();
        $record->question_id = $question->id();
        $DB->insert_record('capquiz_attempt', $record);
        return self::active_attempt($user);
    }

}
