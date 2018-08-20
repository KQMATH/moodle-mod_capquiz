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

    /**
     * @var int $courseid
     */
    private $courseid;

    /**
     * @var int $capquizid
     */
    private $capquizid;

    /**
     * @param int $courseid can be 0 if not creating a new badge
     * @param int $capquizid
     */
    public function __construct(int $courseid, int $capquizid) {
        $this->courseid = $courseid;
        $this->capquizid = $capquizid;
    }

    /**
     * Check if badge for level exists for the capquiz specified in constructor.
     * @param int $level
     * @return bool | null
     */
    public function exists(int $level) {
        global $DB;
        try {
            $badge = $DB->get_record(database_meta::$table_capquiz_badge, [
                database_meta::$field_capquiz_id => $this->capquizid,
                database_meta::$field_level => $level
            ]);
            return $badge !== false;
        } catch (\dml_exception $exception) {
            // TODO: Should this exception be handled here? It is unclear which return value is appropriate.
            return null;
        }
    }

    public function number_of_stars(capquiz_user $user) {
        for ($level = 5; $level > 0; $level--) {
            $badge = $this->get_badge($level);
            if ($badge !== null) {
                if ($badge->is_issued($user->moodle_user_id())) {
                    return $level;
                }
            }
        }
        return 0;
    }

    /**
     * @param int $level
     * @return int
     * @throws \coding_exception
     */
    private function insert_badge(int $level) {
        global $DB, $USER, $CFG;
        $fordb = new \stdClass();
        $fordb->id = null;
        if ($level === 1) {
            $fordb->name = get_string('1_star', capquiz);
            $fordb->description = get_string('earned_first_star', capquiz, $level);
        } else {
            $fordb->name = get_string('level_star', capquiz);
            $fordb->description = get_string('earned_level_star', capquiz, $level);
        }
        $fordb->timecreated = time();
        $fordb->timemodified = time();
        $fordb->usercreated = $USER->id;
        $fordb->usermodified = $USER->id;
        $fordb->issuername = get_string(modulename, 'capquiz');
        $fordb->issuerurl = '';
        $fordb->issuercontact = '';
        $fordb->expiredate = null;
        $fordb->expireperiod = null;
        $fordb->type = BADGE_TYPE_COURSE;
        $fordb->courseid = $this->courseid;
        $fordb->messagesubject = get_string('messagesubject', 'badges');
        $managebadges = get_string('managebadges', 'badges');
        $link = \html_writer::link($CFG->wwwroot . '/badges/mybadges.php', $managebadges);
        $fordb->message = get_string('messagebody', 'badges', $link);
        $fordb->attachment = 1;
        $fordb->notification = BADGE_MESSAGE_NEVER;
        $fordb->status = BADGE_STATUS_INACTIVE;
        try {
            return $DB->insert_record('badge', $fordb, true);
        } catch (\dml_exception $exception) {
            return 0;
        }
    }

    /**
     * @param int $badgeid
     * @param int $level
     * @return int
     */
    private function insert(int $badgeid, int $level) {
        global $DB;
        $fordb = new \stdClass();
        $fordb->id = null;
        $fordb->capquiz_id = $this->capquizid;
        $fordb->badge_id = $badgeid;
        $fordb->level = $level;
        try {
            return $DB->insert_record('capquiz_badge', $fordb, true);
        } catch (\dml_exception $exception) {
            // At this point, we have an inactive rogue badge, which must be removed manually.
            return 0;
        }
    }

    private function trigger_create_badge_event($objectid) {
        global $PAGE;
        $event = \core\event\badge_created::create([
            'objectid' => $objectid,
            'context' => $PAGE->context
        ]);
        $event->trigger();
    }

    private function add_badge_image($badge, $level) {
        global $CFG;
        // TODO: Fix hardcodedness of path?
        $iconfile = $CFG->dirroot . '/mod/capquiz/pix/star-' . $level . '.png';
        badges_process_badge_image($badge, $iconfile);
    }

    private function add_badge_criteria($badge) {
        $criteria = \award_criteria::build([
            'criteriatype' => BADGE_CRITERIA_TYPE_MANUAL,
            'badgeid' => $badge->id
        ]);
        if (count($badge->criteria) === 0) {
            $criteria_overall = \award_criteria::build([
                'criteriatype' => BADGE_CRITERIA_TYPE_OVERALL,
                'badgeid' => $badge->id
            ]);
            $criteria_overall->save([
                'agg' => BADGE_CRITERIA_AGGREGATION_ALL
            ]);
        }
        // TODO: Is there a better way to set the criteria?
        $criteria->save([
            'role_3' => 3
            // Teacher
        ]);
    }

    /**
     * Create a badge for a specified level in the course and activity specified in constructor.
     * @param int $level
     * @throws \coding_exception
     */
    private function create_badge(int $level) {
        if ($this->courseid === 0 || $this->capquizid === 0) {
            return;
        }
        if ($this->exists($level)) {
            return;
        }
        $newid = $this->insert_badge($level);
        $this->insert($newid, $level);
        $this->trigger_create_badge_event($newid);
        $newbadge = new \badge($newid);
        $this->add_badge_image($newbadge, $level);
        $this->add_badge_criteria($newbadge);
        $newbadge->set_status(BADGE_STATUS_ACTIVE);
    }

    /**
     * Create badges for the course and activity specified in constructor.
     */
    public function create_badges() {
        for ($level = 1; $level < 6; $level++) {
            $this->create_badge($level);
        }
    }

    /**
     * Return a badge for a specified level.
     * @param int $level (1-5)
     * @return \badge | null
     */
    private function get_badge(int $level) {
        global $DB;
        if ($level < 1 || $level > 5) {
            return null;
        }
        try {
            $badge = $DB->get_record(database_meta::$table_capquiz_badge, [
                database_meta::$field_capquiz_id => $this->capquizid,
                database_meta::$field_level => $level
            ], 'badge_id', MUST_EXIST);
            return new \badge($badge->badge_id);
        } catch (\dml_exception $exception) {
            return null;
        }
    }

    /**
     * @param int $studentuserid
     * @param int $level
     */
    private function take(int $studentuserid, int $level) {
        global $DB;
        $badge = $this->get_badge($level);
        if ($badge === null || !$badge->is_active()) {
            return;
        }
        if ($badge->is_issued($studentuserid)) {
            $fs = get_file_storage();
            $usercontext = \context_user::instance($studentuserid);
            $fs->delete_area_files($usercontext->id, 'badges', 'userbadge', $badge->id);
            $DB->delete_records('badge_issued', [
                'badgeid' => $badge->id,
                'userid' => $studentuserid
            ]);
        }
    }

    /**
     * Awards badge to a student.
     * @param int $studentuserid
     * @param int $level
     */
    public function award(int $studentuserid, int $level) {
        $badge = $this->get_badge($level);
        if ($badge === null || !$badge->is_active()) {
            return;
        }
        if ($badge->is_issued($studentuserid)) {
            return;
        }
        $badge->issue($studentuserid);
        if ($level > 1) {
            $this->take($studentuserid, $level - 1);
        }
    }

}