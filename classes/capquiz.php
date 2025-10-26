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
use mod_capquiz\local\helpers\stars;

/**
 * CAPQuiz instance.
 *
 * @package     mod_capquiz
 * @author      Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright   2024 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz extends persistent {
    /** @var string The table name. */
    const TABLE = 'capquiz';

    /** @var \stdClass Course module info */
    private \stdClass $cm;

    /**
     * Constructor.
     *
     * @param int $id
     * @param ?\stdClass $record
     */
    public function __construct(int $id = 0, ?\stdClass $record = null) {
        parent::__construct($id, $record);
        $this->cm = new \stdClass();
        $this->cm->id = 0;
    }

    /**
     * Create a new question slot.
     *
     * @param int $questionid
     * @param float $rating
     * @return capquiz_slot
     */
    public function create_slot(int $questionid, float $rating): capquiz_slot {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        $questionbankentry = get_question_bank_entry($questionid);
        $slot = new capquiz_slot();
        $slot->set('capquizid', $this->get('id'));
        $slot->create();
        $slot->rate($rating, false);
        $context = $this->get_context();
        $DB->insert_record('question_references', (object)[
            'usingcontextid' => $context->id,
            'component' => 'mod_capquiz',
            'questionarea' => 'slot',
            'itemid' => $slot->get('id'),
            'questionbankentryid' => $questionbankentry->id,
            'version' => null, // Always latest.
        ]);
        $transaction->allow_commit();
        return $slot;
    }

    /**
     * Delete a question slot.
     *
     * This must be called instead of capquiz_slot::delete() in order to delete the question reference
     * and any question attempts as well.
     *
     * @param capquiz_slot $slot
     * @return bool
     */
    public function delete_slot(capquiz_slot $slot): bool {
        global $DB;
        if ($slot->get('capquizid') !== $this->get('id')) {
            return false;
        }
        if ($slot->get('id') === 0) {
            return false;
        }
        $DB->delete_records('question_references', [
            'component' => 'mod_capquiz',
            'questionarea' => 'slot',
            'itemid' => $slot->get('id'),
        ]);
        $DB->delete_records(capquiz_attempt::TABLE, ['slotid' => $slot->get('id')]);
        return $slot->delete();
    }

    /**
     * Create a new CAPQuiz user.
     *
     * @param int $moodleuserid
     * @return capquiz_user
     */
    public function create_user(int $moodleuserid): capquiz_user {
        $user = new capquiz_user();
        $user->set_many([
            'userid' => $moodleuserid,
            'capquizid' => $this->get('id'),
        ]);
        $user->create();
        $user->rate($this->get('defaultuserrating'), false);
        return $user;
    }

    /**
     * Create a new question usage.
     *
     * @return \question_usage_by_activity
     */
    public function create_question_usage(): \question_usage_by_activity {
        $quba = \question_engine::make_questions_usage_by_activity('mod_capquiz', $this->get_context());
        $quba->set_preferred_behaviour($this->get('questionbehaviour'));
        \question_engine::save_questions_usage_by_activity($quba);
        return $quba;
    }

    /**
     * Update preferred question behavior for all users.
     *
     * @return void
     */
    public function update_question_behavior(): void {
        foreach (capquiz_user::get_records(['capquizid' => $this->get('id')]) as $user) {
            $quba = $user->get_question_usage();
            $quba->set_preferred_behaviour($this->get('questionbehaviour'));
            \question_engine::save_questions_usage_by_activity($quba);
        }
    }

    /**
     * Check if the CAPQuiz is open.
     */
    public function is_open(): bool {
        $now = \core\di::get(\core\clock::class)->time();
        return $now >= $this->get('timeopen') && $now <= $this->get('timedue');
    }

    /**
     * Returns true if the capquiz is completely graded.
     */
    public function is_past_due_time(): bool {
        $now = \core\di::get(\core\clock::class)->time();
        return $now > $this->get('timedue') && $this->get('timedue') > 0;
    }

    /**
     * Returns the number of stars that can be achieved.
     */
    public function get_max_stars(): int {
        return stars::get_max_stars($this->get('starratings'));
    }

    /**
     * The star ratings are internally stored as a CSV string. Use this helper to easily get them as a float array.
     *
     * @return float[]
     */
    public function get_star_ratings_array(): array {
        return array_map('floatval', explode(',', $this->get('starratings')));
    }

    /**
     * Get course module info.
     *
     * @return \stdClass
     */
    public function get_cm(): \stdClass {
        if ($this->get('id') !== 0) {
            $this->cm = get_coursemodule_from_instance('capquiz', $this->get('id'), strictness: MUST_EXIST);
        }
        return $this->cm;
    }

    /**
     * Get context for this CAPQUiz instance.
     *
     * @return \core\context\module
     */
    public function get_context(): \core\context\module {
        $cmid = (int)$this->get_cm()->id;
        return \core\context\module::instance($cmid);
    }

    /**
     * Custom validation for the starstopass property.
     * Check if the number is between 0 and how many stars are configured.
     *
     * @param int $starstopass
     * @return bool|\lang_string
     */
    protected function validate_starstopass(int $starstopass): bool|\lang_string {
        if ($starstopass > stars::get_max_stars($this->get('starratings')) || $starstopass < 0) {
            return new \lang_string('errorvalidatestarstopass', 'capquiz');
        }
        return true;
    }

    /**
     * Custom validation for the starratings property.
     * Check if each rating in the CSV string is a valid number, and that they're greater than the previous.
     *
     * @param string $ratings
     * @return true|\core\lang_string
     */
    protected function validate_starratings(string $ratings): bool|\core\lang_string {
        $previous = 0.0;
        foreach (explode(',', $ratings) as $rating) {
            $previous = filter_var($rating, FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => $previous + 1.0]]);
            if (!$previous) {
                return new \core\lang_string('errorvalidatestarratings', 'capquiz');
            }
        }
        return true;
    }

    /**
     * Custom validation for the questiondisplayoptions property.
     * Check if each property has a valid name and value for {@see \question_display_options}.
     *
     * @param string $options
     * @return bool|\core\lang_string
     */
    protected function validate_questiondisplayoptions(string $options): bool|\core\lang_string {
        foreach (json_decode($options, true) as $key => $value) {
            if (!in_array($key, ['feedback', 'generalfeedback', 'rightanswer', 'correctness'])) {
                return false;
            }
            if ($value !== \question_display_options::HIDDEN && $value !== \question_display_options::VISIBLE) {
                return false;
            }
        }
        return true;
    }

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties(): array {
        return [
            'course' => [
                'type' => PARAM_INT,
                'default' => 0,
                'null' => NULL_NOT_ALLOWED,
            ],
            'name' => [
                'type' => PARAM_TEXT,
                'null' => NULL_NOT_ALLOWED,
            ],
            'intro' => [
                'type' => PARAM_TEXT,
                'null' => NULL_ALLOWED,
            ],
            'introformat' => [
                'type' => PARAM_INT,
                'default' => 0,
                'null' => NULL_NOT_ALLOWED,
            ],
            'defaultuserrating' => [
                'type' => PARAM_FLOAT,
                'default' => 1200.0,
                'null' => NULL_NOT_ALLOWED,
            ],
            'defaultquestionrating' => [
                'type' => PARAM_FLOAT,
                'default' => 600.0,
                'null' => NULL_NOT_ALLOWED,
            ],
            'starratings' => [
                'type' => PARAM_TEXT,
                'default' => '1300,1450,1600,1800,2000',
                'null' => NULL_NOT_ALLOWED,
            ],
            'starstopass' => [
                'type' => PARAM_INT,
                'default' => 3,
                'null' => NULL_NOT_ALLOWED,
            ],
            'timeopen' => [
                'type' => PARAM_INT,
                'default' => 0,
                'null' => NULL_NOT_ALLOWED,
            ],
            'timedue' => [
                'type' => PARAM_INT,
                'default' => 0,
                'null' => NULL_NOT_ALLOWED,
            ],
            'numquestioncandidates' => [
                'type' => PARAM_INT,
                'default' => 10,
                'null' => NULL_NOT_ALLOWED,
            ],
            'minquestionsuntilreappearance' => [
                'type' => PARAM_INT,
                'default' => 0,
                'null' => NULL_NOT_ALLOWED,
            ],
            'userwinprobability' => [
                'type' => PARAM_FLOAT,
                'default' => 0.75,
                'null' => NULL_NOT_ALLOWED,
            ],
            'userkfactor' => [
                'type' => PARAM_FLOAT,
                'default' => 32.0,
                'null' => NULL_NOT_ALLOWED,
            ],
            'questionkfactor' => [
                'type' => PARAM_FLOAT,
                'default' => 8.0,
                'null' => NULL_NOT_ALLOWED,
            ],
            'questionbehaviour' => [
                'type' => PARAM_TEXT,
                'default' => 'immediatefeedback',
                'null' => NULL_NOT_ALLOWED,
            ],
            'questiondisplayoptions' => [
                'type' => PARAM_TEXT,
                'default' => '{}',
                'null' => NULL_NOT_ALLOWED,
            ],
        ];
    }
}
