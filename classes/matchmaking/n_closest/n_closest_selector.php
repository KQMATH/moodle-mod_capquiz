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
 * This file defines a class which acts as a selector for the n_closest matchmaking strategy
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz;

/**
 * Class n_closest_selector
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class n_closest_selector extends capquiz_matchmaking_strategy {

    /** @var capquiz The capquiz */
    private $capquiz;

    /** @var float The propability of the user winning */
    private $userwinprobability;

    /** @var int The number of questions to select */
    private $numquestionstoselect;

    /** @var int The number of turns between each time a question can be selected */
    private $preventsamequestionforturns;

    /**
     * n_closest_selector constructor.
     * @param capquiz $capquiz
     */
    public function __construct(capquiz $capquiz) {
        $this->capquiz = $capquiz;
        $this->configure($this->default_configuration());
    }

    /**
     * Configure the strategy
     *
     * @param \stdClass $configuration
     * @return mixed|void
     */
    public function configure(\stdClass $configuration) /*: void*/ {
        if ($configuration->user_win_probability > 0) {
            $this->userwinprobability = $configuration->user_win_probability;
        }
        if ($configuration->number_of_questions_to_select > 0) {
            $this->numquestionstoselect = $configuration->number_of_questions_to_select;
        }
        if ($configuration->prevent_same_question_for_turns >= 0) {
            $this->preventsamequestionforturns = $configuration->prevent_same_question_for_turns;
        }
    }

    /**
     * Returns the current strategy configuration
     *
     * @return \stdClass
     */
    public function configuration() : \stdClass {
        $config = new \stdClass;
        $config->prevent_same_question_for_turns = $this->preventsamequestionforturns;
        $config->user_win_probability = $this->userwinprobability;
        $config->number_of_questions_to_select = $this->numquestionstoselect;
        return $config;
    }

    /**
     * Returns the default strategy configuration
     *
     * @return \stdClass
     */
    public function default_configuration() : \stdClass {
        $config = new \stdClass;
        $config->user_win_probability = 0.75;
        $config->prevent_same_question_for_turns = 0;
        $config->number_of_questions_to_select = 10;
        return $config;
    }

    /**
     * Selects the next question for the user based on the configuration
     *
     * @param capquiz_user $user
     * @param capquiz_question_list $qlist
     * @param array $inactiveattempts
     * @return capquiz_question|null
     */
    public function next_question_for_user(capquiz_user $user, capquiz_question_list $qlist, array $inactiveattempts) {
        $excluded = $this->determine_excluded_questions($inactiveattempts);
        $candidates = $this->find_questions_closest_to_rating($user, $excluded);
        if (count($candidates) === 0) {
            return null;
        }
        $index = mt_rand(0, count($candidates) - 1);
        if ($question = $candidates[$index]) {
            return $question;
        }
        return null;
    }

    /**
     * Finds the questions closest to the users rating
     *
     * @param capquiz_user $user
     * @param array $excludedquestions
     * @return array
     * @throws \dml_exception
     */
    private function find_questions_closest_to_rating(capquiz_user $user, array $excludedquestions) : array {
        global $DB;
        $sql = 'SELECT * FROM {capquiz_question} WHERE question_list_id = ?';
        $sql .= str_repeat(' AND id <> ?', count($excludedquestions));
        $sql .= ' ORDER BY ABS(? - rating)';
        $params = [];
        $params[] = $this->capquiz->question_list()->id();
        if (count($excludedquestions) > 0) {
            array_push($params, ...$excludedquestions);
        }
        $params[] = $this->ideal_question_rating($user);
        $questionentries = $DB->get_records_sql($sql, $params, 0, $this->numquestionstoselect);
        $questions = [];
        foreach ($questionentries as $questionentry) {
            $questions[] = new capquiz_question($questionentry);
        }
        return $questions;
    }

    /**
     * Returns the ideal question rating
     *
     * @param capquiz_user $user
     * @return float
     */
    private function ideal_question_rating(capquiz_user $user) : float {
        return 400.0 * log((1.0 / $this->userwinprobability) - 1.0, 10.0) + $user->rating();
    }

    /**
     * Identifies questions to exclude and returns them in an array
     *
     * @param array $inactiveattempts
     * @return array
     */
    private function determine_excluded_questions(array $inactiveattempts) : array {
        $it = new \ArrayIterator(array_reverse($inactiveattempts, true));
        $excluded = [];
        for ($i = 0; $i < $this->preventsamequestionforturns; $i++) {
            if (!$it->valid()) {
                break;
            }
            $excluded[] = $it->current()->question_id();
            $it->next();
        }
        return array_unique($excluded);
    }

}
