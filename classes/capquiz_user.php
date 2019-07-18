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
class capquiz_user {

    /** @var \stdClass $record */
    private $record;

    /** @var \stdClass $user  */
    private $user;

    /** @var capquiz_user_rating $rating */
    private $rating;

    /**
     * capquiz_user constructor.
     * @param \stdClass $record
     * @throws \dml_exception
     */
    public function __construct(\stdClass $record) {
        global $DB;
        $this->record = $record;
        $this->user = $DB->get_record('user', ['id' => $this->record->user_id]);

        $rating = capquiz_user_rating::latest_user_rating_by_user($record->id);
        if (is_null($rating)) {
            $this->rating = capquiz_user_rating::insert_user_rating_entry($this->id(), $this->rating());
        } else {
            $this->rating = $rating;
        }
    }

    /**
     * @param capquiz $capquiz
     * @param int $moodleuserid
     * @return capquiz_user|null
     * @throws \Exception
     */
    public static function load_user(capquiz $capquiz, int $moodleuserid) {
        global $DB;
        if ($user = self::load_db_entry($capquiz, $moodleuserid)) {
            return $user;
        }
        $record = new \stdClass();
        $record->user_id = $moodleuserid;
        $record->capquiz_id = $capquiz->id();
        $record->rating = $capquiz->default_user_rating();
        $capquizuserid = $DB->insert_record('capquiz_user', $record, true);
        capquiz_user_rating::insert_user_rating_entry($capquizuserid, $record->rating);
        return self::load_db_entry($capquiz, $moodleuserid);
    }

    public static function user_count(int $capquizid) : int {
        global $DB;
        return $DB->count_records('capquiz_user', ['capquiz_id' => $capquizid]);
    }

    /**
     * @param int $capquizid
     * @return capquiz_user[]
     * @throws \dml_exception
     */
    public static function list_users(int $capquizid) : array {
        global $DB;
        $users = [];
        foreach ($DB->get_records('capquiz_user', ['capquiz_id' => $capquizid]) as $user) {
            $users[] = new capquiz_user($user);
        }
        return $users;
    }

    public function id() : int {
        return $this->record->id;
    }

    public function username() : string {
        return $this->user->username;
    }

    public function first_name() : string {
        return $this->user->firstname;
    }

    public function last_name() : string {
        return $this->user->lastname;
    }

    public function rating() : float {
        return $this->record->rating;
    }

    public function get_capquiz_user_rating() : capquiz_user_rating {
        return $this->rating;
    }

    public function highest_stars_achieved() : int {
        return $this->record->highest_level;
    }

    public function highest_stars_graded() : int {
        return $this->record->stars_graded;
    }

    public function set_highest_star(int $higheststar) {
        global $DB;
        $this->record->highest_level = $higheststar;
        $DB->update_record('capquiz_user', $this->record);
    }

    public function set_rating($rating, bool $manual = false) {
        global $DB;
        $this->record->rating = $rating;
        $DB->update_record('capquiz_user', $this->record);

        $userrating = capquiz_user_rating::create_user_rating($this, $rating, $manual);
        $this->rating = $userrating;
    }

    /**
     * @param capquiz $capquiz
     * @param int $moodleuserid
     * @return capquiz_user|null
     * @throws \dml_exception
     */
    private static function load_db_entry(capquiz $capquiz, int $moodleuserid) {
        global $DB;
        $entry = $entry = $DB->get_record('capquiz_user', [
            'user_id' => $moodleuserid,
            'capquiz_id' => $capquiz->id()
        ]);
        return $entry ? new capquiz_user($entry) : null;
    }



}
