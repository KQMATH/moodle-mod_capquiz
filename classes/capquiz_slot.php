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

declare(strict_types=1);

namespace mod_capquiz;

use core\persistent;
use mod_capquiz\local\helpers\elo;

/**
 * CAPQuiz slot.
 *
 * @package     mod_capquiz
 * @author      Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright   2024 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_slot extends persistent {
    /** @var string The table name. */
    const TABLE = 'capquiz_slot';

    /**
     * Create the initial question rating…
     *
     * @return void
     */
    protected function after_create(): void {
        $questionrating = new capquiz_question_rating(record: (object)[
            'slotid' => $this->get('id'),
            'rating' => $this->get('rating'),
            'manual' => false,
        ]);
        $questionrating->create();
    }

    /**
     * Rate this slot. The new question rating is returned.
     *
     * @param float $rating
     * @param bool $manual
     * @return capquiz_question_rating
     */
    public function rate(float $rating, bool $manual): capquiz_question_rating {
        $this->set('rating', $rating);
        $this->save();
        $questionrating = new capquiz_question_rating();
        $questionrating->set_many([
            'slotid' => $this->get('id'),
            'rating' => $rating,
            'manual' => $manual,
        ]);
        return $questionrating->create();
    }

    /**
     * Returns the question_references record for this slot.
     *
     * @return ?\stdClass question_references record
     */
    public function find_question_reference(): ?\stdClass {
        global $DB;
        return $DB->get_record('question_references', [
            'component' => 'mod_capquiz',
            'questionarea' => 'slot',
            'itemid' => $this->get('id'),
        ]) ?: null;
    }

    /**
     * Returns the question_bank_entries record for this slot.
     *
     * @return ?\stdClass question_bank_entries record
     */
    public function find_question_bank_entry(): ?\stdClass {
        global $DB;
        return $DB->get_record_sql("
            SELECT qbe.*
              FROM {question_references} qr
              JOIN {question_bank_entries} qbe
                ON qbe.id = qr.questionbankentryid
             WHERE qr.component = 'mod_capquiz'
               AND qr.questionarea = 'slot'
               AND qr.itemid = :slotid", [
            'slotid' => $this->get('id'),
        ]) ?: null;
    }

    /**
     * Returns the currently used question_versions record for this slot.
     *
     * @return ?\stdClass question_versions record
     */
    public function find_question_version(): ?\stdClass {
        global $DB;
        return $DB->get_record_sql("
            SELECT qv.*,
                   qr.version AS referencedversion
              FROM {question_references} qr
              JOIN {question_bank_entries} qbe
                ON qbe.id = qr.questionbankentryid
              JOIN {question_versions} qv
                ON qv.questionbankentryid = qbe.id
               AND qv.version = COALESCE(
                       qr.version,
                       (SELECT MAX(qv2.version)
                         FROM {question_versions} qv2
                        WHERE qv2.questionbankentryid = qr.questionbankentryid)
                   )
             WHERE qr.component = 'mod_capquiz'
               AND qr.questionarea = 'slot'
               AND qr.itemid = :slotid", [
            'slotid' => $this->get('id'),
        ]) ?: null;
    }

    /**
     * Returns the currently used question record for this slot.
     *
     * @return ?\stdClass question record
     */
    public function find_question(): ?\stdClass {
        global $DB;
        return $DB->get_record_sql("
            SELECT q.*
              FROM {question_references} qr
              JOIN {question_versions} qv
                ON qv.questionbankentryid = qr.questionbankentryid
               AND qv.version = COALESCE(
                       qr.version,
                       (SELECT MAX(qv2.version)
                         FROM {question_versions} qv2
                        WHERE qv2.questionbankentryid = qr.questionbankentryid)
                   )
              JOIN {question} q
                ON q.id = qv.questionid
             WHERE qr.component = 'mod_capquiz'
               AND qr.questionarea = 'slot'
               AND qr.itemid = :slotid", [
            'slotid' => $this->get('id'),
        ]) ?: null;
    }

    /**
     * Returns the course record related to the question in this slot.
     *
     * @return ?\stdClass course record
     */
    public function find_related_course(): ?\stdClass {
        global $DB;
        return $DB->get_record_sql("
            SELECT c.id AS id
              FROM {question_references} qr
              JOIN {question_bank_entries} qbe
                ON qbe.id = qr.questionbankentryid
              JOIN {question_categories} qc
                ON qc.id = qbe.questioncategoryid
              JOIN {context} ctx
                ON ctx.id = qc.contextid
         LEFT JOIN {course_modules} cm
                ON cm.id = ctx.instanceid
               AND ctx.contextlevel = 70
              JOIN {course} c
                ON (ctx.contextlevel = 50 AND c.id = ctx.instanceid)
                OR (ctx.contextlevel = 70 AND c.id = cm.course)
             WHERE qr.component = 'mod_capquiz'
               AND qr.questionarea = 'slot'
               AND qr.itemid = :slotid", [
            'slotid' => $this->get('id'),
        ]) ?: null;
    }

    /**
     * Finds the next range of slots suitable for a given user based on their rating.
     *
     * @param capquiz_user $user
     * @return self[]
     */
    private static function get_records_for_question_selection(capquiz_user $user): array {
        global $DB;
        $capquiz = new capquiz($user->get('capquizid'));
        $minquestionsuntilreappearance = $capquiz->get('minquestionsuntilreappearance');
        $inactiveattempts = $user->get_reviewed_attempts($minquestionsuntilreappearance);
        $inactiveattemptsiterator = new \ArrayIterator(array_reverse($inactiveattempts, true));
        $excludedslotids = [];
        for ($i = 0; $i < $minquestionsuntilreappearance; $i++) {
            if (!$inactiveattemptsiterator->valid()) {
                break;
            }
            /** @var capquiz_attempt $attempt */
            $attempt = $inactiveattemptsiterator->current();
            $excludedslotids[] = $attempt->get('slotid');
            $inactiveattemptsiterator->next();
        }
        $excludedslotids = array_unique($excludedslotids);
        $params = [
            'capquizid' => $capquiz->get('id'),
            'idealquestionrating' => elo::ideal_question_rating($capquiz->get('userwinprobability'), $user->get('rating')),
        ];
        $sql = 'SELECT *
                  FROM {' . self::TABLE . '}
                 WHERE capquizid = :capquizid';
        if (!empty($excludedslotids)) {
            [$insql, $inparams] = $DB->get_in_or_equal($excludedslotids, SQL_PARAMS_NAMED, equal: false);
            $params += $inparams;
            $sql .= " AND id {$insql}";
        }
        $sql .= ' ORDER BY ABS(:idealquestionrating - rating)';
        $records = $DB->get_records_sql($sql, $params, 0, $capquiz->get('numquestioncandidates'));
        return array_values(array_map(fn(\stdClass $record) => new self(0, $record), $records));
    }

    /**
     * Get the next suitable question for a given user based on their rating.
     *
     * @param capquiz_user $user
     * @return ?self
     */
    public static function get_record_for_next_question(capquiz_user $user): ?self {
        $slots = self::get_records_for_question_selection($user);
        return empty($slots) ? null : $slots[mt_rand(0, count($slots) - 1)];
    }

    /**
     * Delete question ratings and attempts for this slot.
     *
     * @return void
     */
    protected function before_delete(): void {
        global $DB;
        $DB->delete_records('question_references', [
            'component' => 'mod_capquiz',
            'questionarea' => 'slot',
            'itemid' => $this->get('id'),
        ]);
        capquiz_question_rating::delete_records(['slotid' => $this->get('id')]);
        capquiz_attempt::delete_records(['slotid' => $this->get('id')]);
    }

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties(): array {
        return [
            'capquizid' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
            ],
            'rating' => [
                'type' => PARAM_FLOAT,
                'default' => 0.0,
                'null' => NULL_NOT_ALLOWED,
            ],
        ];
    }
}
