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
use mod_capquiz\local\helpers\stars;

/**
 * Question attempt.
 *
 * @package     mod_capquiz
 * @author      Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright   2024 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_attempt extends persistent {
    /** @var string The table name. */
    const TABLE = 'capquiz_attempt';

    /**
     * Get the question state for this attempt.
     *
     * @param \question_usage_by_activity $quba
     */
    public function get_state(\question_usage_by_activity $quba): \question_state {
        return $quba->get_question_attempt($this->get('slot'))->get_state();
    }

    /**
     * Get fraction for this attempt if finished, or null otherwise.
     *
     * @param \question_usage_by_activity $quba
     */
    public function get_fraction(\question_usage_by_activity $quba): ?float {
        $fraction = $quba->get_question_attempt($this->get('slot'))->get_fraction();
        return $fraction === null ? null : (float)$fraction;
    }

    /**
     * Mark the question attempt as reviewed by the user.
     *
     * @return bool false if attempt has already been reviewed, true otherwise
     */
    public function mark_as_reviewed(): bool {
        if (!$this->get('answered') || $this->get('reviewed')) {
            return false;
        }
        $this->set_many([
            'reviewed' => true,
            'timereviewed' => \core\di::get(\core\clock::class)->time(),
        ]);
        $this->save();
        return true;
    }

    /**
     * Submit question attempt.
     *
     * @param capquiz $capquiz
     * @param capquiz_user $user
     * @param ?array $postdata Only intended for testing. Use this data instead of the data from $_POST.
     * @return bool false if attempt has already been answered, true otherwise
     */
    public function submit(capquiz $capquiz, capquiz_user $user, ?array $postdata = null): bool {
        global $DB;
        if ($this->get('answered') && $this->get('reviewed')) {
            return false;
        }
        $transaction = $DB->start_delegated_transaction();
        $quba = $user->get_question_usage();
        $quba->process_action($this->get('slot'), $quba->extract_responses($this->get('slot'), $postdata));
        $quba->update_question_flags();

        // Some question behaviours let users try again, so we need to return early before we finish the question.
        // We don't want to touch any user or question ratings until the question attempt is actually finished.
        // The reason we check for question_state::$complete is due to the adaptive mode question behaviour.
        // It seems to expect some final action before finishing the attempt, but we'll just treat it as finished.
        $score = $quba->get_question_attempt($this->get('slot'))->get_fraction();
        $attemptstate = $this->get_state($quba);
        if (!$attemptstate->is_finished() && $attemptstate !== \question_state::$complete) {
            \question_engine::save_questions_usage_by_activity($quba);
            $transaction->allow_commit();
            return true;
        }

        // At this point, we're certain that the question attempt is finished. Finish the question and save.
        $quba->finish_question($this->get('slot'));
        \question_engine::save_questions_usage_by_activity($quba);

        // Get the current user rating now, since we might need to return early.
        $userratingbeforeattempt = capquiz_user_rating::get_latest_by_user($user->get('id'));
        $this->set_many([
            'answered' => true,
            'timeanswered' => \core\di::get(\core\clock::class)->time(),
            'userprevratingid' => $userratingbeforeattempt->get('id'),
        ]);

        // The slot may be deleted for various reasons, so attempt to load it by allowing missing records.
        $slot = capquiz_slot::get_record(['id' => $this->get('slotid')]);
        if (!$slot) {
            // We can't calculate a new user rating when the slot has been deleted.
            $this->set('userratingid', $userratingbeforeattempt->get('id'));
            $this->save();
            $transaction->allow_commit();
            return true;
        }

        // Adjust user rating.
        $newuserrating = elo::new_rating($capquiz->get('userkfactor'), (float)$score, $user->get('rating'), $slot->get('rating'));
        $userratingafterattempt = $user->rate($newuserrating, false);
        $this->set('userratingid', $userratingafterattempt->get('id'));
        $this->save();
        for ($star = $capquiz->get_max_stars(); $star > 0; $star--) {
            $requiredrating = stars::get_required_rating_for_star($capquiz->get('starratings'), $star);
            if ($user->get('rating') >= $requiredrating && $user->get('higheststars') < $star) {
                $user->set('higheststars', $star);
                // Users may continue the quiz after the due time, but grades shouldn't be affected.
                if (!$capquiz->is_past_due_time()) {
                    $user->set('starsgraded', $star);
                    capquiz_update_grades($capquiz->to_record(), $user->get('userid'));
                }
                $user->save();
                break;
            }
        }
        $transaction->allow_commit();

        // Don't update question ratings when an instructor attempts the quiz.
        $context = $capquiz->get_context();
        if (has_capability('mod/capquiz:instructor', $context)) {
            return true;
        }

        // The question ratings are updated based on the user's previous attempt.
        // If this is the user's first attempt, the question ratings remain the same.
        $previousattempt = $user->find_previously_reviewed_attempt();
        if (empty($previousattempt)) {
            return true;
        }

        // Update question ratings.
        $transaction = $DB->start_delegated_transaction();
        $previouscorrect = $previousattempt->get_state($quba)->is_correct();
        $currentcorrect = $attemptstate->is_correct();
        $previousslot = new capquiz_slot($previousattempt->get('slotid'));
        $this->set_many([
            'prevquestionprevratingid' => capquiz_question_rating::get_latest_by_slot($previousslot)->get('id'),
            'questionprevratingid' => capquiz_question_rating::get_latest_by_slot($slot)->get('id'),
        ]);
        if ($previouscorrect !== $currentcorrect) {
            $hardslot = $previouscorrect ? $slot : $previousslot;
            $easyslot = $previouscorrect ? $previousslot : $slot;
            $easyrating = $easyslot->get('rating');
            $hardrating = $hardslot->get('rating');
            $questionkfactor = $capquiz->get('questionkfactor');
            $easyslot->rate(elo::new_rating($questionkfactor, 0.0, $easyrating, $hardrating), false);
            $hardslot->rate(elo::new_rating($questionkfactor, 1.0, $hardrating, $easyrating), false);
        }
        $this->set_many([
            'prevquestionratingid' => capquiz_question_rating::get_latest_by_slot($previousslot)->get('id'),
            'questionratingid' => capquiz_question_rating::get_latest_by_slot($slot)->get('id'),
        ]);
        $this->save();
        $transaction->allow_commit();
        return true;
    }

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties(): array {
        return [
            'slot' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
            ],
            'capquizid' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
            ],
            'capquizuserid' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
            ],
            'slotid' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
            ],
            'reviewed' => [
                'type' => PARAM_BOOL,
                'default' => false,
                'null' => NULL_NOT_ALLOWED,
            ],
            'answered' => [
                'type' => PARAM_BOOL,
                'default' => false,
                'null' => NULL_NOT_ALLOWED,
            ],
            'timeanswered' => [
                'type' => PARAM_INT,
                'default' => 0,
                'null' => NULL_NOT_ALLOWED,
            ],
            'timereviewed' => [
                'type' => PARAM_INT,
                'default' => 0,
                'null' => NULL_NOT_ALLOWED,
            ],
            'questionratingid' => [
                'type' => PARAM_INT,
                'default' => null,
                'null' => NULL_ALLOWED,
            ],
            'questionprevratingid' => [
                'type' => PARAM_INT,
                'default' => null,
                'null' => NULL_ALLOWED,
            ],
            'prevquestionratingid' => [
                'type' => PARAM_INT,
                'default' => null,
                'null' => NULL_ALLOWED,
            ],
            'prevquestionprevratingid' => [
                'type' => PARAM_INT,
                'default' => null,
                'null' => NULL_ALLOWED,
            ],
            'userratingid' => [
                'type' => PARAM_INT,
                'default' => null,
                'null' => NULL_ALLOWED,
            ],
            'userprevratingid' => [
                'type' => PARAM_INT,
                'default' => null,
                'null' => NULL_ALLOWED,
            ],
        ];
    }
}
