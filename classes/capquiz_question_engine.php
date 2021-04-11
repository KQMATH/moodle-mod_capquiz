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
 * This file defines a class represeting a capquiz question engine
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace mod_capquiz;

defined('MOODLE_INTERNAL') || die();

/**
 * Class capquiz_question_engine
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_question_engine {

    /** @var capquiz $capquiz */
    private $capquiz;

    /** @var \question_usage_by_activity $quba */
    private $quba;

    /** @var capquiz_matchmaking_strategy_loader $matchmakingloader */
    private $matchmakingloader;

    /** @var capquiz_rating_system_loader $ratingsystemloader */
    private $ratingsystemloader;

    /**
     * capquiz_question_engine constructor.
     * @param capquiz $capquiz
     * @param \question_usage_by_activity $quba
     * @param capquiz_matchmaking_strategy_loader $strategyloader
     * @param capquiz_rating_system_loader $ratingsystemloader
     */
    public function __construct(capquiz $capquiz, \question_usage_by_activity $quba,
                                capquiz_matchmaking_strategy_loader $strategyloader,
                                capquiz_rating_system_loader $ratingsystemloader) {
        $this->capquiz = $capquiz;
        $this->quba = $quba;
        $this->matchmakingloader = $strategyloader;
        $this->ratingsystemloader = $ratingsystemloader;
    }

    /**
     * Checks if the user has finished their attempt
     *
     * @param capquiz_user $user
     * @return bool
     */
    public function user_is_completed(capquiz_user $user) : bool {
        if (capquiz_question_attempt::active_attempt($user)) {
            return false;
        }
        if ($this->find_question_for_user($user)) {
            return false;
        }
        return true;
    }

    /**
     * Gets an attempt for the user, returns a new one if there are no active attempts
     *
     * @param capquiz_user $user
     * @return capquiz_question_attempt|null
     */
    public function attempt_for_user(capquiz_user $user) {
        if ($attempt = capquiz_question_attempt::active_attempt($user)) {
            return $attempt;
        }
        return $this->new_attempt_for_user($user);
    }

    /**
     * Calls attempt_for_user with the user parameter as the current user
     *
     * @return capquiz_question_attempt|null
     * @throws \Exception
     */
    public function attempt_for_current_user() {
        return $this->attempt_for_user($this->capquiz->user());
    }

    /**
     * Deletes attempt if it is invalid
     *
     * @param capquiz_user $user
     */
    public function delete_invalid_attempt(capquiz_user $user) {
        $attempt = $this->attempt_for_user($user);

        if (!$attempt->is_question_valid()) {
            $attempt->delete();
        }
    }

    /**
     * Handles answer
     *
     * @param capquiz_user $user
     * @param capquiz_question_attempt $attempt
     */
    public function attempt_answered(capquiz_user $user, capquiz_question_attempt $attempt) {
        if (!$attempt->is_question_valid()) {
            return;
        }
        $ratingsystem = $this->ratingsystemloader->rating_system();
        $attempt->mark_as_answered();
        $attempt->set_user_rating($user->get_capquiz_user_rating(), true);
        $question = $this->capquiz->question_list()->question($attempt->question_id());
        if ($attempt->is_correctly_answered()) {
            $ratingsystem->update_user_rating($user, $question, 1);
            $this->set_new_highest_star_if_attained($user);
        } else {
            $ratingsystem->update_user_rating($user, $question, 0);
        }
        $attempt->set_user_rating($user->get_capquiz_user_rating());
        $previousattempt = capquiz_question_attempt::previous_attempt($user);
        if ($previousattempt) {
            $this->update_question_rating($previousattempt, $attempt);
        }
    }

    /**
     * Sets a new "highest star" score if the new score is the highest score yet
     *
     * @param capquiz_user $user
     * @throws \dml_exception
     */
    private function set_new_highest_star_if_attained(capquiz_user $user) {
        $qlist = $this->capquiz->question_list();
        for ($star = $qlist->max_stars(); $star > 0; $star--) {
            $required = $qlist->star_rating($star);
            if ($user->rating() >= $required && $user->highest_stars_achieved() < $star) {
                $user->set_highest_star($star);
                break;
            }
        }
    }

    /**
     * Marks attempt as reviewed
     *
     * @param capquiz_question_attempt $attempt
     */
    public function attempt_reviewed(capquiz_question_attempt $attempt) {
        $attempt->mark_as_reviewed();
    }

    /**
     * Creates a new attempt for the user
     *
     * @param capquiz_user $user
     * @return capquiz_question_attempt|null
     */
    private function new_attempt_for_user(capquiz_user $user) {
        $question = $this->find_question_for_user($user);
        return $question ? capquiz_question_attempt::create_attempt($user, $question) : null;
    }

    /**
     * Finds a new question for the user
     *
     * @param capquiz_user $user
     * @return mixed
     * @throws \dml_exception
     */
    private function find_question_for_user(capquiz_user $user) {
        $selector = $this->matchmakingloader->selector();
        $questionlist = $this->capquiz->question_list();
        $inactiveattempts = capquiz_question_attempt::inactive_attempts($user);
        return $selector->next_question_for_user($user, $questionlist, $inactiveattempts);
    }

    /**
     * Updates the question ratings
     *
     * @param capquiz_question_attempt $previous
     * @param capquiz_question_attempt $current
     */
    private function update_question_rating(capquiz_question_attempt $previous, capquiz_question_attempt $current) {
        $ratingsystem = $this->ratingsystemloader->rating_system();
        $currentcorrect = $current->is_correctly_answered();
        $previouscorrect = $previous->is_correctly_answered();
        $currentquestion = $this->capquiz->question_list()->question($current->question_id());
        $previousquestion = $this->capquiz->question_list()->question($previous->question_id());

        $current->set_previous_question_rating($previousquestion->get_capquiz_question_rating(), true);
        $current->set_question_rating($currentquestion->get_capquiz_question_rating(), true);

        if (!$currentquestion || !$previousquestion) {
            return;
        }
        if ($previouscorrect && !$currentcorrect) {
            $ratingsystem->question_victory_ratings($currentquestion, $previousquestion);
        } else if (!$previouscorrect && $currentcorrect) {
            $ratingsystem->question_victory_ratings($previousquestion, $currentquestion);
        } else {
            $previousquestion->set_rating($previousquestion->rating());
            $currentquestion->set_rating($currentquestion->rating());
        }

        $current->set_previous_question_rating($previousquestion->get_capquiz_question_rating());
        $current->set_question_rating($currentquestion->get_capquiz_question_rating());
    }

}
