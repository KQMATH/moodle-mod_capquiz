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
     * @var int $capquizid
     */
    private $capquizid;

    /**
     * @param int $courseid
     * @param int $capquizid
     */
    public function __construct($courseid, $capquizid) {
        $this->courseid = $courseid;
        $this->capquizid = $capquizid;
    }

    /**
     * Check if badge for level exists for the capquiz specified in constructor.
     * @param int $level
     * @return bool | null
     */
    public function exists($level) {
        global $DB;
        try {
            $badge = $DB->get_record('capquiz_badge', [
                'capquiz_id' => $this->capquizid,
                'level' => $level
            ]);
            return $badge !== false;
        } catch (\dml_exception $exception) {
            // TODO: Should this exception be handled here? It is unclear which return value is appropriate.
            return null;
        }
    }

    /**
     * Create a badge for a specified level in the course and activity specified in constructor.
     * @param int $level
     * @throws \coding_exception
     */
    private function create_badge(int $level) {
        global $CFG, $DB, $PAGE;
        if ($this->courseid === null || $this->capquizid === null) {
            return;
        }

        // Check for duplication
        if ($this->exists($level)) {
            return;
        }

        // TODO: hmmmmmmm... Usually this is the default admin, but this must be fixed before release.
        $userid = 1;

        // Add database row
        $fordb = new \stdClass();
        $fordb->id = null;
        $fordb->name = "Level $level";
        $fordb->description = "You have achieved level $level!";
        $fordb->timecreated = time();
        $fordb->timemodified = time();
        $fordb->usercreated = $userid;
        $fordb->usermodified = $userid;
        $fordb->issuername = 'CapQuiz';
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
            $newid = $DB->insert_record('badge', $fordb, true);
        } catch (\dml_exception $exception) {
            return;
        }

        // Add the capquiz badge
        $fordb = new \stdClass();
        $fordb->id = null;
        $fordb->capquiz_id = $this->capquizid;
        $fordb->badge_id = $newid;
        $fordb->level = $level;
        try {
            $DB->insert_record('capquiz_badge', $fordb);
        } catch (\dml_exception $exception) {
            // At this point, we have an inactive rogue badge, which must be removed manually.
            return;
        }

        // Trigger event, badge created.
        $eventparams = [
            'objectid' => $newid,
            'context' => $PAGE->context
        ];
        $event = \core\event\badge_created::create($eventparams);
        $event->trigger();
        $newbadge = new \badge($newid);
        // TODO: Fix hardcodedness of path?
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
        // TODO: Is there a better way to set the criteria?
        $criteria->save([
            'role_3' => 3 // Teacher
        ]);

        // Enable the badge
        $newbadge->set_status(BADGE_STATUS_ACTIVE);
    }

    /**
     * Create badges for the course and activity specified in constructor.
     */
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
        global $DB;
        if ($level < 1 || $level > 5) {
            return null;
        }
        try {
            $badge = $DB->get_record('capquiz_badge', [
                'capquiz_id' => $this->capquizid,
                'level' => $level
            ], 'badge_id', MUST_EXIST);
            return new \badge($badge->badge_id);
        } catch (\dml_exception $exception) {
            return null;
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
        if (!$badge->is_issued($studentuserid)) {
            $badge->issue($studentuserid);
        }
    }

}