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
use qubaid_join;
use question_engine;

/**
 * CAPQuiz user.
 *
 * @package     mod_capquiz
 * @author      Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright   2024 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_user extends persistent {
    /** @var string The table name. */
    const TABLE = 'capquiz_user';

    /**
     * Create the initial user rating…
     *
     * @return void
     */
    protected function after_create(): void {
        $userrating = new capquiz_user_rating(record: (object)[
            'capquizuserid' => $this->get('id'),
            'rating' => $this->get('rating'),
            'manual' => false,
        ]);
        $userrating->create();
    }

    /**
     * Rate this user. The new user rating is returned.
     *
     * @param float $rating
     * @param bool $manual
     */
    public function rate(float $rating, bool $manual): capquiz_user_rating {
        $this->set('rating', $rating);
        $this->save();
        $userrating = new capquiz_user_rating();
        $userrating->set_many([
            'capquizuserid' => $this->get('id'),
            'rating' => $rating,
            'manual' => $manual,
        ]);
        return $userrating->create();
    }

    /**
     * Creates a new question attempt for this user.
     *
     * @param capquiz_slot $slot
     * @return ?capquiz_attempt
     */
    public function create_attempt(capquiz_slot $slot): ?capquiz_attempt {
        global $DB;
        $question = $slot->find_question();
        if (!$question) {
            return null;
        }
        $questions = question_load_questions([$question->id]);
        $question = reset($questions);
        if (!$question) {
            return null;
        }
        $transaction = $DB->start_delegated_transaction();
        $quba = $this->get_question_usage();
        $qubaslot = $quba->add_question(\question_bank::make_question($question));
        $quba->start_question($qubaslot);
        \question_engine::save_questions_usage_by_activity($quba);
        $attempt = new capquiz_attempt();
        $attempt->set_many([
            'slot' => $qubaslot,
            'capquizid' => $this->get('capquizid'),
            'capquizuserid' => $this->get('id'),
            'slotid' => $slot->get('id'),
        ]);
        $attempt->create();
        $transaction->allow_commit();
        return $attempt;
    }

    /**
     * Get the question usage for this user's quiz attempts.
     * TODO: This function should not create the question usage.
     *
     * @return \question_usage_by_activity
     */
    public function get_question_usage(): \question_usage_by_activity {
        if (!$this->get('questionusageid')) {
            $capquiz = new capquiz($this->get('capquizid'));
            $quba = $capquiz->create_question_usage();
            $this->set('questionusageid', $quba->get_id());
            $this->save();
        }
        return \question_engine::load_questions_usage_by_activity($this->get('questionusageid'));
    }

    /**
     * Find an unreviewed attempt.
     *
     * @return ?capquiz_attempt
     */
    public function find_unreviewed_attempt(): ?capquiz_attempt {
        $attempt = capquiz_attempt::get_record([
            'capquizuserid' => $this->get('id'),
            'reviewed' => 0,
        ]);
        return $attempt ?: null;
    }

    /**
     * Find the previously reviewed attempt.
     *
     * @return capquiz_attempt|null
     */
    public function find_previously_reviewed_attempt(): ?capquiz_attempt {
        $records = capquiz_attempt::get_records(
            filters: ['capquizuserid' => $this->get('id')],
            sort: 'timereviewed',
            order: 'DESC',
            limit: 1,
        );
        return empty($records) ? null : reset($records);
    }

    /**
     * Get reviewed attempts.
     *
     * @param int $limit
     * @return capquiz_attempt[]
     */
    public function get_reviewed_attempts(int $limit): array {
        return capquiz_attempt::get_records(
            filters: [
                'capquizuserid' => $this->get('id'),
                'answered' => true,
                'reviewed' => true,
            ],
            sort: 'timecreated',
            order: 'DESC',
            limit: $limit,
        );
    }

    /**
     * Delete question usage, user ratings, and question attempts for this user.
     *
     * @return void
     */
    protected function before_delete(): void {
        $qubaidjoin = new qubaid_join(
            from: '{' . static::TABLE . '} cu',
            usageidcolumn: 'cu.questionusageid',
            where: 'cu.id = :capquizuserid',
            params: ['capquizuserid' => $this->get('id')],
        );
        question_engine::delete_questions_usage_by_activities($qubaidjoin);
        capquiz_attempt::delete_records(['capquizuserid' => $this->get('id')]);
        capquiz_user_rating::delete_records(['capquizuserid' => $this->get('id')]);
    }

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties(): array {
        return [
            'userid' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
            ],
            'capquizid' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
            ],
            'questionusageid' => [
                'type' => PARAM_INT,
                'default' => null,
                'null' => NULL_ALLOWED,
            ],
            'rating' => [
                'type' => PARAM_FLOAT,
                'default' => 0.0,
                'null' => NULL_NOT_ALLOWED,
            ],
            'higheststars' => [
                'type' => PARAM_INT,
                'default' => 0,
                'null' => NULL_NOT_ALLOWED,
            ],
            'starsgraded' => [
                'type' => PARAM_INT,
                'default' => 0,
                'null' => NULL_NOT_ALLOWED,
            ],
        ];
    }
}
