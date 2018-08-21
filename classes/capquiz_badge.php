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

require_once('../../config.php');
require_once($CFG->dirroot . '/lib/badgeslib.php');

/**
 * @package     mod_capquiz
 * @author      Sebastian S. Gundersen <sebastsg@stud.ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_badge {
    private $badge;
    private $capquiz;
    private $badge_level;

    public function __construct(capquiz $capquiz, int $level) {
        $this->capquiz = $capquiz;
        $this->badge_level = $level;
        if ($level < 1 || $level > 5) {
            throw new \Exception("Badge does not exist for the specified level $level");
        } else {
            $this->badge = $this->get_badge();
        }
    }

    public function level() {
        return $this->badge_level;
    }

    public function is_awarded_to(capquiz_user $user) {
        return $this->badge->is_issued($user->moodle_user_id());
    }

    public function award(capquiz_user $user) {
        $userid = $user->moodle_user_id();
        if (!$this->badge->is_issued($userid)) {
            $this->badge->issue($userid);
        }
    }

    public function withdraw(capquiz_user $user) {
        global $DB;
        if ($this->badge->is_issued($user->moodle_user_id())) {
            $fs = get_file_storage();
            $user_context = \context_user::instance($user->moodle_user_id());
            $fs->delete_area_files($user_context->id, 'badges', 'userbadge', $this->badge->id);
            $DB->delete_records('badge_issued', [
                'badgeid' => $this->badge->id,
                'userid' => $user->moodle_user_id()
            ]);
        }
    }

    private function get_badge() {
        global $DB;
        $level = $this->badge_level;
        try {
            $criteria = [
                database_meta::$field_capquiz_id => $this->capquiz->id(),
                database_meta::$field_level => $level
            ];
            if ($badge = $DB->get_record(database_meta::$table_capquiz_badge, $criteria, database_meta::$field_badge_id, MUST_EXIST))
                return new \badge($badge->badge_id);
            throw new \Exception("Badge does not exist for level $level");
        } catch (\Exception $e) {
            throw $e;
        }
    }
}