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
class capquiz_badge_registry {
    private $capquiz;
    private $levels;

    public function __construct(capquiz $capquiz, int $levels = 5) {
        $this->capquiz = $capquiz;
        $this->levels = $levels;
    }

    public function award(capquiz_user $user, int $level) {
        $badge = $this->badge($level);
        if ($badge->is_awarded_to($user))
            return;
        $badge->award($user);
        if ($level > 1) {
            $previous_badge = $this->badge($level - 1);
            $previous_badge->withdraw($user);
        }
    }

    public function number_of_levels() {
        return $this->levels;
    }

    public function number_of_stars_for_user(capquiz_user $user) {
        for ($level = 1; $level < 6; $level++) {
            $badge = $this->badge($level);
            if ($badge->is_awarded_to($user->moodle_user_id())) {
                return $level;
            }
        }
        return 0;
    }

    public function has_badge_for_level(int $level) {
        try {
            return $this->badge($level) !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function create_badges() {
        for ($level = 1; $level < $this->levels + 1; $level++) {
            if (!$this->has_badge_for_level($level)) {
                $this->create_badge_for_level($level);
            }
        }
    }

    private function badge(int $level) {
        return new capquiz_badge($this->capquiz, $level);
    }

    private function create_badge_for_level(int $level) {
        $id = $this->create_badge_db_entry($level);
        $this->create_capquiz_badge_db_entry($id, $level);
        $this->trigger_badge_create_event($id);
        $this->setup_moodle_badge($id, $level);
    }

    private function setup_moodle_badge(int $id, int $level) {
        $badge = new \badge($id);
        $this->set_badge_image($badge, $level);
        $this->set_badge_criteria($badge);
        $badge->set_status(BADGE_STATUS_ACTIVE);
    }

    private function create_badge_db_entry(int $level) {
        global $DB, $USER, $CFG;
        $time_now = time();
        $moodle_badge_db_entry = new \stdClass();;
        $moodle_badge_db_entry->id = null;
        $moodle_badge_db_entry->name = $this->badge_name($level);
        $moodle_badge_db_entry->description = $this->badge_description($level);
        $moodle_badge_db_entry->timecreated = $time_now;
        $moodle_badge_db_entry->timemodified = $time_now;
        $moodle_badge_db_entry->usercreated = $USER->id;
        $moodle_badge_db_entry->usermodified = $USER->id;
        $moodle_badge_db_entry->issuername = get_string('modulename', 'capquiz');
        $moodle_badge_db_entry->issuerurl = '';
        $moodle_badge_db_entry->issuercontact = '';
        $moodle_badge_db_entry->expiredate = null;
        $moodle_badge_db_entry->expireperiod = null;
        $moodle_badge_db_entry->type = BADGE_TYPE_COURSE;
        $moodle_badge_db_entry->courseid = $this->capquiz->course_module_id();
        $moodle_badge_db_entry->messagesubject = get_string('messagesubject', 'badges');
        $moodle_badge_db_entry->message = get_string('messagebody', 'badges',
            \html_writer::link($CFG->wwwroot . '/badges/mybadges.php', get_string('managebadges', 'badges')));
        $moodle_badge_db_entry->attachment = 1;
        $moodle_badge_db_entry->notification = BADGE_MESSAGE_NEVER;
        $moodle_badge_db_entry->status = BADGE_STATUS_INACTIVE;
        try {
            return $DB->insert_record('badge', $moodle_badge_db_entry, true);
        } catch (\dml_exception $exception) {
            return 0;
        }
    }

    private function badge_name(int $level) {
        return $level === 1 ?
            get_string('one_star', 'capquiz') :
            get_string('level_stars', 'capquiz', $level);
    }

    private function badge_description(int $level) {
        return $level === 1 ?
            get_string('earned_first_star', 'capquiz') :
            get_string('earned_level_star', 'capquiz', $level);
    }

    private function create_capquiz_badge_db_entry(int $badge_id, int $level) {
        global $DB;
        $capquiz_badge_db_entry = new \stdClass();
        $capquiz_badge_db_entry->level = $level;
        $capquiz_badge_db_entry->badge_id = $badge_id;
        $capquiz_badge_db_entry->capquiz_id = $this->capquiz->id();
        try {
            return $DB->insert_record('capquiz_badge', $capquiz_badge_db_entry, true);
        } catch (\Exception $exception) {
            $message = "Failed to create badge for level $level. This badge is dangling and must be manually removed.\n";
            $message .= $exception->getMessage();
            throw new \Exception($message);
        }
    }

    private function trigger_badge_create_event(int $object_id) {
        $event = \core\event\badge_created::create([
            'objectid' => $object_id,
            'context' => $this->capquiz->context()
        ]);
        $event->trigger();
    }

    private function set_badge_image(\badge $badge, $level) {
        global $CFG;
        $image_file = $CFG->dirroot . "/mod/capquiz/pix/star-$level.png";
        badges_process_badge_image($badge, $image_file);
    }

    private function set_badge_criteria(\badge $badge) {
        $criteria = \award_criteria::build([
            'criteriatype' => BADGE_CRITERIA_TYPE_MANUAL,
            'badgeid' => $badge->id
        ]);
        if (count($badge->criteria) === 0) {
            $activity_criteria = \award_criteria::build([
                'criteriatype' => BADGE_CRITERIA_TYPE_ACTIVITY,
                'badgeid' => $badge->id
            ]);
            $activity_criteria->save([
                'agg' => BADGE_CRITERIA_AGGREGATION_ALL
            ]);
        }
        $criteria->save([
            'role_3' => 3
            // Teacher
        ]);
    }
}