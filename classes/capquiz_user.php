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
 * This file defines a class representing a capquiz user
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @author      Sebastian S. Gundersen <sebastian@sgundersen.com>
 * @copyright   2019 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz;

defined('MOODLE_INTERNAL') || die();

/**
 * capquiz_user class
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @author      Sebastian S. Gundersen <sebastian@sgundersen.com>
 * @copyright   2019 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_user {

    /** @var \stdClass $record */
    private $record;

    /** @var \stdClass $user  */
    private $user;

    /** @var capquiz_user_rating $rating */
    private $rating;

    /** @var \question_usage_by_activity $quba */
    private $quba;

    /**
     * capquiz_user constructor.
     *
     * @param \stdClass $record
     * @param \context_module $context
     * @throws \dml_exception
     */
    public function __construct(\stdClass $record, \context_module $context) {
        global $DB;
        $this->record = $record;
        $this->user = $DB->get_record('user', ['id' => $this->record->user_id]);

        $rating = capquiz_user_rating::latest_user_rating_by_user($record->id);
        if (is_null($rating)) {
            $this->rating = capquiz_user_rating::insert_user_rating_entry($this->id(), $this->rating());
        } else {
            $this->rating = $rating;
        }
        $this->create_question_usage($context);
        try {
            $this->quba = \question_engine::load_questions_usage_by_activity($this->record->question_usage_id);
        } catch (\coding_exception $e) {
            $this->quba = null;
        }
    }

    /**
     * Verify if user has permission to use question
     *
     * @return bool
     */
    private function has_question_usage() : bool {
        return $this->record->question_usage_id !== null;
    }


    /**
     * Create question usage
     *
     * @param  \context_module $context
     * @throws \dml_exception
     */
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
        $DB->update_record('capquiz_user', $this->record);
    }


    /**
     * Return this users quba
     *
     * @return \question_usage_by_activity|null
     */
    public function question_usage() : ?\question_usage_by_activity {
        return $this->quba;
    }

    /**
     * Loads capquiz user
     *
     * @param capquiz $capquiz
     * @param int $moodleuserid
     * @param \context_module $context
     * @return capquiz_user|null
     * @throws \Exception
     */
    public static function load_user(capquiz $capquiz, int $moodleuserid, \context_module $context) {
        global $DB;
        if ($user = self::load_db_entry($capquiz, $moodleuserid, $context)) {
            return $user;
        }
        $record = new \stdClass();
        $record->user_id = $moodleuserid;
        $record->capquiz_id = $capquiz->id();
        $record->rating = $capquiz->default_user_rating();
        $capquizuserid = $DB->insert_record('capquiz_user', $record, true);
        capquiz_user_rating::insert_user_rating_entry($capquizuserid, $record->rating);
        return self::load_db_entry($capquiz, $moodleuserid, $context);
    }

    /**
     * Returns count of users in this capquiz
     *
     * @param int $capquizid
     * @return int count of users in this capquiz
     * @throws \dml_exception
     */
    public static function user_count(int $capquizid) : int {
        global $DB;
        return $DB->count_records('capquiz_user', ['capquiz_id' => $capquizid]);
    }

    /**
     * Returns list of all users in this capquiz
     *
     * @param int $capquizid
     * @param \context_module $context
     * @return capquiz_user[]
     * @throws \dml_exception
     */
    public static function list_users(int $capquizid, \context_module $context) : array {
        global $DB;
        $users = [];
        foreach ($DB->get_records('capquiz_user', ['capquiz_id' => $capquizid]) as $user) {
            $users[] = new capquiz_user($user, $context);
        }
        return $users;
    }

    /**
     * Return this users id
     *
     * @return int users id
     */
    public function id() : int {
        return $this->record->id;
    }

    /**
     * Returns this users username
     *
     * @return string username
     */
    public function username() : string {
        return $this->user->username;
    }

    /**
     * Returns this users first name
     *
     * @return string first name
     */
    public function first_name() : string {
        return $this->user->firstname;
    }

    /**
     * Returns this users last name
     *
     * @return string last name
     */
    public function last_name() : string {
        return $this->user->lastname;
    }

    /**
     * Return users rating
     *
     * @return float
     */
    public function rating() : float {
        return $this->record->rating;
    }

    /**
     * Get this users capquiz rating
     *
     * @return capquiz_user_rating
     */
    public function get_capquiz_user_rating() : capquiz_user_rating {
        return $this->rating;
    }

    /**
     * Return the highest star rating this user has achieved
     *
     * @return int highest star rating
     */
    public function highest_stars_achieved() : int {
        return $this->record->highest_level;
    }

    /**
     * Return the highest star grade
     *
     * @return int highest star grade
     */
    public function highest_stars_graded() : int {
        return $this->record->stars_graded;
    }

    /**
     * Set this users highest star rating
     *
     * @param int $higheststar
     * @throws \dml_exception
     */
    public function set_highest_star(int $higheststar) {
        global $DB;
        $this->record->highest_level = $higheststar;
        $DB->update_record('capquiz_user', $this->record);
    }

    /**
     * Set this users rating
     *
     * @param $rating
     * @param bool $manual
     * @throws \dml_exception
     */
    public function set_rating($rating, bool $manual = false) {
        global $DB;
        $this->record->rating = $rating;
        $DB->update_record('capquiz_user', $this->record);

        $userrating = capquiz_user_rating::create_user_rating($this, $rating, $manual);
        $this->rating = $userrating;
    }

    /**
     * Load user entry from database
     *
     * @param capquiz $capquiz
     * @param int $moodleuserid
     * @return capquiz_user|null
     * @throws \dml_exception
     */
    private static function load_db_entry(capquiz $capquiz, int $moodleuserid, \context_module $context) {
        global $DB;
        $entry = $entry = $DB->get_record('capquiz_user', [
            'user_id' => $moodleuserid,
            'capquiz_id' => $capquiz->id()
        ]);
        return $entry ? new capquiz_user($entry, $context) : null;
    }



}
