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
 * @package mod_capquiz
 */
class capquiz_badge {

    /**
     * @var int $courseid
     */
    private $courseid;

    /**
     * @param int $courseid
     */
    public function __construct($courseid) {
        $this->courseid = $courseid;
    }

    /**
     * Create a badge for a specified level.
     * @param int $level
     */
    private function create_badge(int $level) {
        global $CFG, $DB, $PAGE;

        // TODO: Avoid duplication.

        $userid = 1; // TODO: hmmm
        $type = BADGE_TYPE_SITE;// BADGE_TYPE_COURSE;
        $now = time();

        // Add database row
        $fordb = new \stdClass();
        $fordb->id = null;
        $fordb->name = "Level $level";
        $fordb->description = "You have achieved level $level!";
        $fordb->timecreated = $now;
        $fordb->timemodified = $now;
        $fordb->usercreated = $userid;
        $fordb->usermodified = $userid;
        $fordb->issuername = 'CapQuiz';
        $fordb->issuerurl = '';
        $fordb->issuercontact = '';
        $fordb->expiredate = null;
        $fordb->expireperiod = null;
        $fordb->type = $type;
        $fordb->courseid = ($type == BADGE_TYPE_COURSE) ? $this->courseid : null;
        $fordb->messagesubject = get_string('messagesubject', 'badges');
        $managebadges = get_string('managebadges', 'badges');
        $link = \html_writer::link($CFG->wwwroot . '/badges/mybadges.php', $managebadges);
        $fordb->message = get_string('messagebody', 'badges', $link);
        $fordb->attachment = 1;
        $fordb->notification = BADGE_MESSAGE_NEVER;
        $fordb->status = BADGE_STATUS_INACTIVE;
        $newid = $DB->insert_record('badge', $fordb, true);

        // Trigger event, badge created.
        $eventparams = [
            'objectid' => $newid,
            'context' => $PAGE->context
        ];
        $event = \core\event\badge_created::create($eventparams);
        $event->trigger();
        $newbadge = new \badge($newid);
        $iconfile = $CFG->dirroot . '/mod/capquiz/pix/badge-level-' . $level . '.png';
        badges_process_badge_image($newbadge, $iconfile);

        // Add the criteria
        $criteria = \award_criteria::build([
            'criteriatype' => BADGE_CRITERIA_TYPE_MANUAL,
            'badgeid' => $newbadge->id
        ]);
        if (count($newbadge->criteria) == 0) {
            $criteria_overall = \award_criteria::build([
                'criteriatype' => BADGE_CRITERIA_TYPE_OVERALL,
                'badgeid' => $newbadge->id
            ]);
            $criteria_overall->save([
                'agg' => BADGE_CRITERIA_AGGREGATION_ALL
            ]);
        }
        $criteria->save([
            'role_3' => 3 // Teacher
        ]);

        // Enable the badge
        $newbadge->set_status(BADGE_STATUS_ACTIVE);

        // TODO: Make capquiz_badge table to relate to this badge.
    }

    public function create_badges() {
        for ($level = 1; $level < 4; $level++) {
            $this->create_badge($level);
        }
    }

    /**
     * Return a badge for a specified level.
     * @param int $level (1-5)
     * @return \badge | null
     */
    private function get_badge(int $level) {
        if ($level < 1 || $level > 5) {
            return null;
        }
        $badgeid = 18; // TODO: Check eventual capquiz_badge table for badge id.
        return new \badge($badgeid);
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
        if (!$badge->is_issued($studentuserid)) {
            $badge->issue($studentuserid);
        }
    }

}