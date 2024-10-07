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
 * This file defines the class capquiz_question_list
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @author      Sebastian S. Gundersen <sebastian@sgundersen.com>
 * @copyright   2019 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz;

use context_course;
use stdClass;

/**
 * Class capquiz_question_list
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @author      Sebastian S. Gundersen <sebastian@sgundersen.com>
 * @copyright   2019 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_question_list {

    /** @var stdClass $record */
    private stdClass $record;

    /** @var capquiz_question[] $questions */
    private array $questions;

    /**
     * Constructor.
     *
     * @param stdClass $record
     */
    public function __construct(stdClass $record) {
        global $DB;
        $this->record = $record;
        $records = $DB->get_records('capquiz_question', ['question_list_id' => $this->record->id]);
        $this->questions = array_map(fn(stdClass $question) => new capquiz_question($question), array_values($records));
    }

    /**
     * Returns array of ratings
     *
     * @return int[]
     */
    public function star_ratings_array(): array {
        $ratings = explode(',', $this->record->star_ratings);
        return array_map(fn(string $rating) => (float)$rating, $ratings);
    }

    /**
     * Returns the count of all ratings (The value needed for a full star score)
     */
    public function max_stars(): int {
        return count($this->star_ratings_array());
    }

    /**
     * Returns the value of the star rating $star
     *
     * @param int $star
     */
    public function star_rating(int $star): float {
        $stars = $this->star_ratings_array();
        return $stars[$star - 1];
    }

    /**
     * Sets the star ratings to new values and updates database
     *
     * @param int[] $ratings
     */
    public function set_star_ratings(array $ratings): void {
        global $DB;
        $starratings = implode(',', $ratings);
        if (strlen($starratings) < 250) {
            $this->record->star_ratings = $starratings;
            $DB->update_record('capquiz_question_list', $this->record);
        }
    }

    /**
     * Returns the completion level to the next rating as a percent value
     *
     * @param capquiz $capquiz
     * @param float $rating
     */
    public function next_level_percent(capquiz $capquiz, float $rating): int {
        $goal = 0;
        for ($star = 1; $star <= $this->max_stars(); $star++) {
            $goal = $this->star_rating($star);
            if ($goal > $rating) {
                $previous = $star > 1 ? $this->star_rating($star - 1) : $capquiz->default_user_rating();
                $rating -= $previous;
                $goal -= $previous;
                break;
            }
        }
        return $goal >= 1 ? (int)($rating / $goal * 100) : 0;
    }

    /**
     * Returns the id
     */
    public function id(): int {
        return $this->record->id;
    }

    /**
     * Returns the author user
     */
    public function author(): ?stdClass {
        global $DB;
        return $DB->get_record('user', ['id' => $this->record->author]) ?: null;
    }

    /**
     * Returns true if the list contains questions
     */
    public function has_questions(): bool {
        return count($this->questions) > 0;
    }

    /**
     * Returns true if the question list is a template
     */
    public function is_template(): bool {
        return $this->record->is_template;
    }

    /**
     * Returns the default question rating
     */
    public function default_question_rating(): float {
        return $this->record->default_question_rating;
    }

    /**
     * Sets teh default question rating
     *
     * @param float $rating
     */
    public function set_default_question_rating(float $rating): void {
        global $DB;
        $this->record->default_question_rating = $rating;
        $DB->update_record('capquiz_question_list', $this->record);
    }

    /**
     * Returns the title of the question list
     */
    public function title(): string {
        return $this->record->title;
    }

    /**
     * Returns the description of the question list
     */
    public function description(): string {
        return $this->record->description;
    }

    /**
     * Returns the time of when the list was created
     */
    public function time_created(): string {
        return $this->record->time_created;
    }

    /**
     * Returns the last time when the list was modified
     */
    public function time_modified(): string {
        return $this->record->time_modified;
    }

    /**
     * Returns the amount of questions in the list
     */
    public function question_count(): int {
        return count($this->questions);
    }

    /**
     * Returns all the questions in the list in an array
     *
     * @return capquiz_question[]
     */
    public function questions(): array {
        return $this->questions;
    }

    /**
     * Returns the question with the id of $questionid
     *
     * @param int $questionid
     */
    public function question(int $questionid): ?capquiz_question {
        foreach ($this->questions as $question) {
            if ($question->id() === $questionid) {
                return $question;
            }
        }
        return null;
    }

    /**
     * Checks if the list has the question with questionid $questionid
     *
     * @param int $questionid
     * @return mixed|capquiz_question|null
     */
    public function has_question(int $questionid): mixed {
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
     */
    public function merge(capquiz_question_list $that): void {
        global $DB;
        foreach ($that->questions as $question) {
            if ($this->has_question($question->question_id()) === null) {
                $newquestion = new stdClass();
                $newquestion->question_list_id = $this->id();
                $newquestion->question_id = $question->question_id();
                $newquestion->rating = $question->rating();
                $capquizquestionid = $DB->insert_record('capquiz_question', $newquestion, true);
                capquiz_question_rating::insert_question_rating_entry($capquizquestionid, $newquestion->rating);
            }
        }
    }

    /**
     * Creates a copy of this instance
     *
     * @param capquiz $capquiz
     */
    public function create_instance_copy(capquiz $capquiz): ?capquiz_question_list {
        return $this->create_copy($capquiz, false);
    }

    /**
     * Updates database record
     *
     * @param int $capquizid
     */
    public function convert_to_instance(int $capquizid): bool {
        global $DB;
        if ($this->id() || !$this->is_template()) {
            return false;
        }
        $this->record->capquiz_id = $capquizid;
        $this->record->is_template = 0;
        $DB->update_record('capquiz_question_list', $this->record);
        return true;
    }

    /**
     * Creates a copy of this instance as template
     *
     * @param capquiz $capquiz
     */
    public function create_template_copy(capquiz $capquiz): ?capquiz_question_list {
        return $this->create_copy($capquiz, true);
    }

    /**
     * Copies the questions in this list to database
     *
     * @param int $qlistid
     */
    private function copy_questions_to_list(int $qlistid): void {
        global $DB;
        foreach ($this->questions() as $question) {
            $record = $question->entry();
            $record->id = null;
            $record->question_list_id = $qlistid;
            $capquizquestionid = $DB->insert_record('capquiz_question', $record);
            capquiz_question_rating::insert_question_rating_entry($capquizquestionid, $record->rating);
        }
    }

    /**
     * Creates a copy of this instance and inserts the new copy into the database
     *
     * @param capquiz $capquiz
     * @param bool $template
     * @return ?capquiz_question_list The new but identical (apart from identicators) question list instance
     */
    private function create_copy(capquiz $capquiz, bool $template): ?capquiz_question_list {
        global $DB;
        $record = $this->record;
        $record->id = null;
        $record->capquiz_id = $template ? null : $capquiz->id();
        $record->context_id = context_course::instance($capquiz->course()->id)->id;
        $record->is_template = $template;
        $record->time_created = time();
        $record->time_modified = time();
        $transaction = $DB->start_delegated_transaction();
        try {
            $newid = $DB->insert_record('capquiz_question_list', $record);
            $this->copy_questions_to_list($newid);
            $DB->commit_delegated_transaction($transaction);
            $record->id = $newid;
            return new capquiz_question_list($record);
        } catch (\dml_exception $exception) {
            $DB->rollback_delegated_transaction($transaction, $exception);
        }
    }

    /**
     * Create new question list instance and insert it in database
     *
     * @param capquiz $capquiz
     * @param string $title
     * @param string $description
     * @param array $ratings
     */
    public static function create_new_instance(capquiz $capquiz, string $title, string $description,
                                               array $ratings): ?capquiz_question_list {
        global $DB, $USER;
        if (count($ratings) < 5) {
            return null;
        }
        $record = new stdClass();
        $record->capquiz_id = $capquiz->id();
        $record->title = $title;
        $record->description = $description;
        $record->star_ratings = implode(',', $ratings);
        $record->author = $USER->id;
        $record->is_template = 0;
        $record->time_created = time();
        $record->time_modified = time();
        $record->context_id = context_course::instance($capquiz->course()->id)->id;
        $qlistid = $DB->insert_record('capquiz_question_list', $record);
        $qlist = self::load_any($qlistid);
        if (!$qlist) {
            return null;
        }
        $capquiz->validate_matchmaking_and_rating_systems();
        return $qlist;
    }

    /**
     * Loads question list from database based on the capquiz
     *
     * @param capquiz $capquiz
     */
    public static function load_question_list(capquiz $capquiz): ?capquiz_question_list {
        global $DB;
        $record = $DB->get_record('capquiz_question_list', ['capquiz_id' => $capquiz->id()]);
        return $record ? new capquiz_question_list($record) : null;
    }

    /**
     * Loads question list from database based on the question list id
     *
     * @param int $qlistid
     */
    public static function load_any(int $qlistid): ?capquiz_question_list {
        global $DB;
        $record = $DB->get_record('capquiz_question_list', ['id' => $qlistid]);
        return $record ? new capquiz_question_list($record) : null;
    }

    /**
     * Loads question list templates
     *
     * @return capquiz_question_list[]
     * @throws \dml_exception
     */
    public static function load_question_list_templates(): array {
        global $DB;
        $records = $DB->get_records('capquiz_question_list', ['is_template' => 1]);
        $qlists = [];
        foreach ($records as $record) {
            $qlists[] = new capquiz_question_list($record);
        }
        return $qlists;
    }

}
