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
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_question {

    /** @var \stdClass $record */
    private $record;

    /** @var capquiz_question_rating $rating */
    private $rating;

    public function __construct(\stdClass $record) {
        global $DB;
        $this->record = $record;
        // TODO: This query should probably be done in question list.
        $sql = 'SELECT name,questiontext FROM {question} WHERE id = ?';
        $qname = $DB->get_record_sql($sql, array($record->question_id));
        // $question = $DB->get_record('question', ['id' => $record->question_id]);
        if ($question !== false) {
            $this->record->name = $qname->name;
            $this->record->text = $qname->questiontext;
        } else {
            $this->record->name = get_string('missing_question', 'capquiz');
            $this->record->text = $this->record->name;
        }
        $rating = capquiz_question_rating::latest_question_rating_by_question($record->id);
        if (is_null($rating)) {
            $this->rating = capquiz_question_rating::insert_question_rating_entry($this->id(), $this->rating());
        } else {
            $this->rating = $rating;
        }
    }

    public static function load(int $questionid) {
        global $DB;
        $record = $DB->get_record('capquiz_question', ['id' => $questionid]);
        if ($record === false) {
            return null;
        }
        return new capquiz_question($record);
    }

    public function entry() : \stdClass {
        return $this->record;
    }

    public function id() : int {
        return $this->record->id;
    }

    public function question_id() : int {
        return $this->record->question_id;
    }

    public function question_list_id() : int {
        return $this->record->question_list_id;
    }

    public function rating() : float {
        return $this->record->rating;
    }

    public function get_capquiz_question_rating() : capquiz_question_rating {
        return $this->rating;
    }

    public function set_rating($rating, bool $manual = false) {
        global $DB;
        $this->record->rating = $rating;
        $DB->update_record('capquiz_question', $this->record);

        $questionrating = capquiz_question_rating::create_question_rating($this, $rating, $manual);
        $this->rating = $questionrating;

    }

    public function name() : string {
        return $this->record->name;
    }

    public function text() : string {
        return $this->record->text;
    }

    public function course_id() : int {
        global $DB;
        $sql = 'SELECT c.id AS id
                  FROM {capquiz_question} cq
                  JOIN {question} q ON q.id = cq.question_id
                  JOIN {question_categories} qc ON qc.id = q.category
                  JOIN {context} ctx ON ctx.id = qc.contextid
             LEFT JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = 70
                  JOIN {course} c ON (ctx.contextlevel = 50 AND c.id = ctx.instanceid)
                       OR (ctx.contextlevel = 70 AND c.id = cm.course)
                 WHERE cq.id = :questionid';
        $course = $DB->get_record_sql($sql, ['questionid' => $this->id()]);
        return $course ? $course->id : 0;
    }

}
