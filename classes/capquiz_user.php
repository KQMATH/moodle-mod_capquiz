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

class capquiz_user {

    private $db_entry;

    public function __construct(\stdClass $user_db_entry) {
        $this->db_entry = $user_db_entry;
    }

    public static function load_user(capquiz $capquiz, int $moodle_userid) {
        if ($user = self::load_db_entry($capquiz, $moodle_userid)) {
            return $user;
        }
        return self::insert_db_entry($capquiz, $moodle_userid);
    }

    public static function list_users(capquiz $capquiz) {
        global $DB;
        $criteria = [
            database_meta::$field_capquiz_id => $capquiz->id()
        ];
        $users = [];
        foreach ($DB->get_records(database_meta::$table_capquiz_user, $criteria) as $user) {
            $users[] = new capquiz_user($user);
        }
        return $users;
    }

    public function id() {
        return $this->db_entry->id;
    }

    public function capquiz_id() {
        return $this->db_entry->capquiz_id;
    }

    public function moodle_user_id() {
        return $this->db_entry->user_id;
    }

    public function rating() {
        return $this->db_entry->rating;
    }

    public function set_rating(float $rating) {
        global $DB;
        $db_entry = $this->db_entry;
        $db_entry->rating = $rating;
        if ($DB->update_record(database_meta::$table_capquiz_user, $db_entry)) {
            $this->db_entry = $db_entry;
            $this->ask_for_badge();
        }
    }

    private function ask_for_badge() {
        global $DB;
        $capquizid = $this->capquiz_id();
        try {
            // TODO: Either find an existing instance of the list from somewhere, or use a join to avoid two queries.
            $capquiz = $DB->get_record('capquiz', ['id' => $capquizid]);
            if (!$capquiz) {
                return;
            }
            $list = $DB->get_record('capquiz_question_list', ['id' => $capquiz->question_list_id]);
            if (!$list) {
                return;
            }
        } catch (\dml_exception $exception) {
            return;
        }
        $list = new capquiz_question_list($list);
        $badge = new capquiz_badge(0, $capquizid);
        for ($level = 1; $level < 6; $level++) { // TODO: Hardcoded loop range
            $required = $list->level_rating($level);
            if ($this->rating() >= $required) {
                $badge->award($this->moodle_user_id(), $level);
            }
        }
    }

    private static function load_db_entry(capquiz $capquiz, int $moodle_userid) {
        global $DB;
        $criteria = [
            database_meta::$field_user_id => $moodle_userid,
            database_meta::$field_capquiz_id => $capquiz->id()
        ];
        if ($entry = $DB->get_record(database_meta::$table_capquiz_user, $criteria)) {
            return new capquiz_user($entry);
        }
        return null;
    }

    private static function insert_db_entry(capquiz $capquiz, int $moodle_userid) {
        global $DB;
        $user_entry = new \stdClass();
        $user_entry->user_id = $moodle_userid;
        $user_entry->capquiz_id = $capquiz->id();
        $user_entry->rating = $capquiz->default_user_rating();
        try {
            if ($DB->insert_record(database_meta::$table_capquiz_user, $user_entry)) {
                return self::load_db_entry($capquiz, $moodle_userid);
            } else {
                throw new \Exception('Unable to persist capquiz user');
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

}
