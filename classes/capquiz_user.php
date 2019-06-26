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
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_user {

    /** @var \stdClass $record */
    private $record;

    /** @var \stdClass $moodlerecord  */
    private $moodlerecord;

    public function __construct(\stdClass $record) {
        $this->record = $record;
        $this->moodlerecord = null;
    }

    public static function load_user(capquiz $capquiz, int $moodleuserid) {
        if ($user = self::load_db_entry($capquiz, $moodleuserid)) {
            return $user;
        }
        return self::insert_db_entry($capquiz, $moodleuserid);
    }

    public static function user_count(capquiz $capquiz) : int {
        global $DB;
        $criteria = ['capquiz_id' => $capquiz->id()];
        $count = $DB->count_records('capquiz_user', $criteria);
        return $count;
    }

    /**
     * @param capquiz $capquiz
     * @return capquiz_user[]
     * @throws \dml_exception
     */
    public static function list_users(capquiz $capquiz) : array {
        global $DB;
        $criteria = ['capquiz_id' => $capquiz->id()];
        $users = [];
        foreach ($DB->get_records('capquiz_user', $criteria) as $user) {
            $users[] = new capquiz_user($user);
        }
        return $users;
    }

    public function id() : int {
        return $this->record->id;
    }

    public function username() : string {
        if ($this->moodlerecord === null) {
            $this->load_moodle_entry();
        }
        return $this->moodlerecord->username;
    }

    public function first_name() : string {
        if ($this->moodlerecord === null) {
            $this->load_moodle_entry();
        }
        return $this->moodlerecord->firstname;
    }

    public function last_name() : string {
        if ($this->moodlerecord === null) {
            $this->load_moodle_entry();
        }
        return $this->moodlerecord->lastname;
    }

    public function rating() : float {
        return $this->record->rating;
    }

    public function highest_level() : int {
        return $this->record->highest_level;
    }

    public function stars_graded() : int {
        return $this->record->stars_graded;
    }

    public function set_highest_level(int $highestlevel) {
        global $DB;
        $record = $this->record;
        $record->highest_level = $highestlevel;
        if ($DB->update_record('capquiz_user', $record)) {
            $this->record = $record;
        }
    }

    public function set_rating(float $rating) {
        global $DB;
        $record = $this->record;
        $record->rating = $rating;
        if ($DB->update_record('capquiz_user', $record)) {
            $this->record = $record;
        }
    }

    private function load_moodle_entry() {
        global $DB;
        $criteria = ['id' => $this->moodle_user_id()];
        $record = $DB->get_record('user', $criteria);
        if ($record) {
            $this->moodlerecord = $record;
        } else {
            throw new \Exception('Unable to load user with id ' . $this->moodle_user_id());
        }
    }

    private function moodle_user_id() : int {
        return $this->record->user_id;
    }

    private static function load_db_entry(capquiz $capquiz, int $moodleuserid) {
        global $DB;
        $criteria = [
            'user_id' => $moodleuserid,
            'capquiz_id' => $capquiz->id()
        ];
        if ($entry = $DB->get_record('capquiz_user', $criteria)) {
            return new capquiz_user($entry);
        }
        return null;
    }

    private static function insert_db_entry(capquiz $capquiz, int $moodleuserid) {
        global $DB;
        $record = new \stdClass();
        $record->user_id = $moodleuserid;
        $record->capquiz_id = $capquiz->id();
        $record->rating = $capquiz->default_user_rating();
        try {
            if ($DB->insert_record('capquiz_user', $record)) {
                return self::load_db_entry($capquiz, $moodleuserid);
            } else {
                throw new \Exception('Unable to persist capquiz user');
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

}
