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
 * @author      Sebastian S. Gundersen <sebastian@sgundersen.com>
 * @copyright   2019 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_question_list {

    /** @var \stdClass $record */
    private $record;

    /** @var capquiz_question[] $questions */
    private $questions = null;

    /** @var \question_usage_by_activity $quba */
    private $quba;

    public function __construct(\stdClass $record, $context) {
        global $DB;
        $this->record = $record;

        $this->load_questions() ;

        $this->create_question_usage($context);
        $this->quba = \question_engine::load_questions_usage_by_activity($this->record->question_usage_id);
    }

    private function load_questions() {
        global $DB;
        if ( is_null( $this->questions ) ) {
           $entries = $DB->get_records('capquiz_question', ['question_list_id' => $this->record->id]);
           $this->questions = [];
           foreach ($entries as $entry) {
               $this->questions[] = new capquiz_question($entry);
           }
        }
    }

    public function star_ratings_array() {
        $ratings = explode(',', $this->record->star_ratings);
        foreach ($ratings as &$rating) {
            $rating = (int)$rating;
        }
        return $ratings;
    }

    public function max_stars() {
        return count($this->star_ratings_array());
    }

    public function star_rating(int $star) {
        $stars = $this->star_ratings_array();
        return $stars[$star - 1];
    }

    /**
     * @param int[] $ratings
     * @throws \dml_exception
     */
    public function set_star_ratings(array $ratings) {
        global $DB;
        $starratings = implode(',', $ratings);
        if (strlen($starratings) < 250) {
            $this->record->star_ratings = $starratings;
            $DB->update_record('capquiz_question_list', $this->record);
        }
    }

    public function next_level_percent(capquiz $capquiz, int $rating) : int {
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

    public function question_usage() {
        return $this->quba;
    }

    public function id() : int {
        return $this->record->id;
    }

    public function author() {
        global $DB;
        $record = $DB->get_record('user', ['id' => $this->record->author]);
        return $record ? $record : null;
    }

    public function has_questions() : bool {
        return count($this->questions()) > 0;
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

    public function time_created() : string {
        return $this->record->time_created;
    }

    public function time_modified() : string {
        return $this->record->time_modified;
    }

    public function question_count() : int {
        return count($this->questions());
    }

    /**
     * @return capquiz_question[]
     */
    public function questions() : array {
        $this->load_questions() ;
        return $this->questions;
    }

    public function question(int $questionid) {
        $this->load_questions() ;
        foreach ($this->questions as $question) {
            if ($question->id() === $questionid) {
                return $question;
            }
        }
        return null;
    }

    public function has_question(int $questionid) {
        $this->load_questions() ;
        // TODO: This is unnecessarily slow for large question lists
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
        $thatqs = $that->questions() ;
        foreach ( $thatqs as $question) {
            if ($this->has_question($question->question_id()) === null) {
                $newquestion = new \stdClass();
                $newquestion->question_list_id = $this->id();
                $newquestion->question_id = $question->question_id();
                $newquestion->rating = $question->rating();
                $capquizquestionid = $DB->insert_record('capquiz_question', $newquestion, true);
                capquiz_question_rating::insert_question_rating_entry($capquizquestionid, $newquestion->rating);
            }
        }
    }

    public function create_instance_copy(capquiz $capquiz) {
        return $this->create_copy($capquiz, false);
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

    public function create_template_copy(capquiz $capquiz) {
        return $this->create_copy($capquiz, true);
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
        $this->load_questions() ;
        foreach ($this->questions() as $question) {
            $record = $question->entry();
            $record->id = null;
            $record->question_list_id = $qlistid;
            $capquizquestionid = $DB->insert_record('capquiz_question', $record, true);
            capquiz_question_rating::insert_question_rating_entry($capquizquestionid, $record->rating);
        }
    }

    private function create_copy(capquiz $capquiz, bool $template) {
        global $DB;
        $record = $this->record;
        $record->id = null;
        $record->capquiz_id = $template ? null : $capquiz->id();
        $record->context_id = \context_course::instance($capquiz->course()->id)->id;
        $record->question_usage_id = null;
        $record->is_template = $template;
        $record->time_created = time();
        $record->time_modified = time();
        $transaction = $DB->start_delegated_transaction();
        try {
            $newid = $DB->insert_record('capquiz_question_list', $record);
            $this->copy_questions_to_list($newid);
            $DB->commit_delegated_transaction($transaction);
            $record->id = $newid;
            return new capquiz_question_list($record, $capquiz->context());
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
        $record->star_ratings = implode(',', $ratings);
        $record->author = $USER->id;
        $record->is_template = 0;
        $record->time_created = time();
        $record->time_modified = time();
        $record->context_id = \context_course::instance($capquiz->course()->id)->id;
        try {
            $qlistid = $DB->insert_record('capquiz_question_list', $record);
            $qlist = self::load_any($qlistid, $capquiz->context());
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
        $record = $DB->get_record('capquiz_question_list', ['capquiz_id' => $capquiz->id()]);
        return $record ? new capquiz_question_list($record, $capquiz->context()) : null;
    }

    public static function load_any(int $qlistid, $context) {
        global $DB;
        $record = $DB->get_record('capquiz_question_list', ['id' => $qlistid]);
        return $record ? new capquiz_question_list($record, $context) : null;
    }

    /**
     * @param $context
     * @return capquiz_question_list[]
     * @throws \dml_exception
     */
    public static function load_question_list_templates($context) : array {
        global $DB;
        $records = $DB->get_records('capquiz_question_list', ['is_template' => 1]);
        $qlists = [];
        foreach ($records as $record) {
            $qlists[] = new capquiz_question_list($record, $context);
        }
        return $qlists;
    }

}
