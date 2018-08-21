<?php

class badge {
    /** @var int Badge id */
    public $id;

    /** Values from the table 'badge' */
    public $name;
    public $description;
    public $timecreated;
    public $timemodified;
    public $usercreated;
    public $usermodified;
    public $issuername;
    public $issuerurl;
    public $issuercontact;
    public $expiredate;
    public $expireperiod;
    public $type;
    public $courseid;
    public $message;
    public $messagesubject;
    public $attachment;
    public $notification;
    public $status = 0;
    public $nextcron;

    /** @var array Badge criteria */
    public $criteria = array();

    /**
     * Constructs with badge details.
     *
     * @param int $badgeid badge ID.
     */
    public function __construct($badgeid) {
        global $DB;
        $this->id = $badgeid;

        $data = $DB->get_record('badge', array('id' => $badgeid));

        if (empty($data)) {
            print_error('error:nosuchbadge', 'badges', $badgeid);
        }

        foreach ((array)$data as $field => $value) {
            if (property_exists($this, $field)) {
                $this->{$field} = $value;
            }
        }

        $this->criteria = self::get_criteria();
    }

    /**
     * Use to get context instance of a badge.
     * @return context instance.
     */
    public function get_context() {
        if ($this->type == BADGE_TYPE_SITE) {
            return context_system::instance();
        } else if ($this->type == BADGE_TYPE_COURSE) {
            return context_course::instance($this->courseid);
        } else {
            debugging('Something is wrong...');
        }
    }

    /**
     * Return array of aggregation methods
     * @return array
     */
    public static function get_aggregation_methods() {
        return array(
            BADGE_CRITERIA_AGGREGATION_ALL => get_string('all', 'badges'),
            BADGE_CRITERIA_AGGREGATION_ANY => get_string('any', 'badges'),
        );
    }

    /**
     * Return array of accepted criteria types for this badge
     * @return array
     */
    public function get_accepted_criteria() {
        $criteriatypes = array();

        if ($this->type == BADGE_TYPE_COURSE) {
            $criteriatypes = array(
                BADGE_CRITERIA_TYPE_OVERALL,
                BADGE_CRITERIA_TYPE_MANUAL,
                BADGE_CRITERIA_TYPE_COURSE,
                BADGE_CRITERIA_TYPE_BADGE,
                BADGE_CRITERIA_TYPE_ACTIVITY
            );
        } else if ($this->type == BADGE_TYPE_SITE) {
            $criteriatypes = array(
                BADGE_CRITERIA_TYPE_OVERALL,
                BADGE_CRITERIA_TYPE_MANUAL,
                BADGE_CRITERIA_TYPE_COURSESET,
                BADGE_CRITERIA_TYPE_BADGE,
                BADGE_CRITERIA_TYPE_PROFILE,
            );
        }

        return $criteriatypes;
    }

    /**
     * Save/update badge information in 'badge' table only.
     * Cannot be used for updating awards and criteria settings.
     *
     * @return bool Returns true on success.
     */
    public function save() {
        global $DB;

        $fordb = new stdClass();
        foreach (get_object_vars($this) as $k => $v) {
            $fordb->{$k} = $v;
        }
        unset($fordb->criteria);

        $fordb->timemodified = time();
        if ($DB->update_record_raw('badge', $fordb)) {
// Trigger event, badge updated.
            $eventparams = array('objectid' => $this->id, 'context' => $this->get_context());
            $event = \core\event\badge_updated::create($eventparams);
            $event->trigger();
            return true;
        } else {
            throw new moodle_exception('error:save', 'badges');
            return false;
        }
    }

    /**
     * Creates and saves a clone of badge with all its properties.
     * Clone is not active by default and has 'Copy of' attached to its name.
     *
     * @return int ID of new badge.
     */
    public function make_clone() {
        global $DB, $USER, $PAGE;

        $fordb = new stdClass();
        foreach (get_object_vars($this) as $k => $v) {
            $fordb->{$k} = $v;
        }

        $fordb->name = get_string('copyof', 'badges', $this->name);
        $fordb->status = BADGE_STATUS_INACTIVE;
        $fordb->usercreated = $USER->id;
        $fordb->usermodified = $USER->id;
        $fordb->timecreated = time();
        $fordb->timemodified = time();
        unset($fordb->id);

        if ($fordb->notification > 1) {
            $fordb->nextcron = badges_calculate_message_schedule($fordb->notification);
        }

        $criteria = $fordb->criteria;
        unset($fordb->criteria);

        if ($new = $DB->insert_record('badge', $fordb, true)) {
            $newbadge = new badge($new);

// Copy badge image.
            $fs = get_file_storage();
            if ($file = $fs->get_file($this->get_context()->id, 'badges', 'badgeimage', $this->id, '/', 'f1.png')) {
                if ($imagefile = $file->copy_content_to_temp()) {
                    badges_process_badge_image($newbadge, $imagefile);
                }
            }

// Copy badge criteria.
            foreach ($this->criteria as $crit) {
                $crit->make_clone($new);
            }

// Trigger event, badge duplicated.
            $eventparams = array('objectid' => $new, 'context' => $PAGE->context);
            $event = \core\event\badge_duplicated::create($eventparams);
            $event->trigger();

            return $new;
        } else {
            throw new moodle_exception('error:clone', 'badges');
            return false;
        }
    }

    /**
     * Checks if badges is active.
     * Used in badge award.
     *
     * @return bool A status indicating badge is active
     */
    public function is_active() {
        if (($this->status == BADGE_STATUS_ACTIVE) ||
            ($this->status == BADGE_STATUS_ACTIVE_LOCKED)) {
            return true;
        }
        return false;
    }

    /**
     * Use to get the name of badge status.
     *
     */
    public function get_status_name() {
        return get_string('badgestatus_' . $this->status, 'badges');
    }

    /**
     * Use to set badge status.
     * Only active badges can be earned/awarded/issued.
     *
     * @param int $status Status from BADGE_STATUS constants
     */
    public function set_status($status = 0) {
        $this->status = $status;
        $this->save();
        if ($status == BADGE_STATUS_ACTIVE) {
// Trigger event, badge enabled.
            $eventparams = array('objectid' => $this->id, 'context' => $this->get_context());
            $event = \core\event\badge_enabled::create($eventparams);
            $event->trigger();
        } else if ($status == BADGE_STATUS_INACTIVE) {
// Trigger event, badge disabled.
            $eventparams = array('objectid' => $this->id, 'context' => $this->get_context());
            $event = \core\event\badge_disabled::create($eventparams);
            $event->trigger();
        }
    }

    /**
     * Checks if badges is locked.
     * Used in badge award and editing.
     *
     * @return bool A status indicating badge is locked
     */
    public function is_locked() {
        if (($this->status == BADGE_STATUS_ACTIVE_LOCKED) ||
            ($this->status == BADGE_STATUS_INACTIVE_LOCKED)) {
            return true;
        }
        return false;
    }

    /**
     * Checks if badge has been awarded to users.
     * Used in badge editing.
     *
     * @return bool A status indicating badge has been awarded at least once
     */
    public function has_awards() {
        global $DB;
        $awarded = $DB->record_exists_sql('SELECT b.uniquehash
FROM {badge_issued} b INNER JOIN {user} u ON b.userid = u.id
WHERE b.badgeid = :badgeid AND u.deleted = 0', array('badgeid' => $this->id));

        return $awarded;
    }

    /**
     * Gets list of users who have earned an instance of this badge.
     *
     * @return array An array of objects with information about badge awards.
     */
    public function get_awards() {
        global $DB;

        $awards = $DB->get_records_sql(
            'SELECT b.userid, b.dateissued, b.uniquehash, u.firstname, u.lastname
FROM {badge_issued} b INNER JOIN {user} u
ON b.userid = u.id
WHERE b.badgeid = :badgeid AND u.deleted = 0', array('badgeid' => $this->id));

        return $awards;
    }

    /**
     * Indicates whether badge has already been issued to a user.
     *
     */
    public function is_issued($userid) {
        global $DB;
        return $DB->record_exists('badge_issued', array('badgeid' => $this->id, 'userid' => $userid));
    }

    /**
     * Issue a badge to user.
     *
     * @param int $userid User who earned the badge
     * @param bool $nobake Not baking actual badges (for testing purposes)
     */
    public function issue($userid, $nobake = false) {
        global $DB, $CFG;

        $now = time();
        $issued = new stdClass();
        $issued->badgeid = $this->id;
        $issued->userid = $userid;
        $issued->uniquehash = sha1(rand() . $userid . $this->id . $now);
        $issued->dateissued = $now;

        if ($this->can_expire()) {
            $issued->dateexpire = $this->calculate_expiry($now);
        } else {
            $issued->dateexpire = null;
        }

// Take into account user badges privacy settings.
// If none set, badges default visibility is set to public.
        $issued->visible = get_user_preferences('badgeprivacysetting', 1, $userid);

        $result = $DB->insert_record('badge_issued', $issued, true);

        if ($result) {
// Trigger badge awarded event.
            $eventdata = array(
                'context' => $this->get_context(),
                'objectid' => $this->id,
                'relateduserid' => $userid,
                'other' => array('dateexpire' => $issued->dateexpire, 'badgeissuedid' => $result)
            );
            \core\event\badge_awarded::create($eventdata)->trigger();

// Lock the badge, so that its criteria could not be changed any more.
            if ($this->status == BADGE_STATUS_ACTIVE) {
                $this->set_status(BADGE_STATUS_ACTIVE_LOCKED);
            }

// Update details in criteria_met table.
            $compl = $this->get_criteria_completions($userid);
            foreach ($compl as $c) {
                $obj = new stdClass();
                $obj->id = $c->id;
                $obj->issuedid = $result;
                $DB->update_record('badge_criteria_met', $obj, true);
            }

            if (!$nobake) {
// Bake a badge image.
                $pathhash = badges_bake($issued->uniquehash, $this->id, $userid, true);

// Notify recipients and badge creators.
                badges_notify_badge_award($this, $userid, $issued->uniquehash, $pathhash);
            }
        }
    }

    /**
     * Reviews all badge criteria and checks if badge can be instantly awarded.
     *
     * @return int Number of awards
     */
    public function review_all_criteria() {
        global $DB, $CFG;
        $awards = 0;

// Raise timelimit as this could take a while for big web sites.
        core_php_time_limit::raise();
        raise_memory_limit(MEMORY_HUGE);

        foreach ($this->criteria as $crit) {
// Overall criterion is decided when other criteria are reviewed.
            if ($crit->criteriatype == BADGE_CRITERIA_TYPE_OVERALL) {
                continue;
            }

            list($extrajoin, $extrawhere, $extraparams) = $crit->get_completed_criteria_sql();
// For site level badges, get all active site users who can earn this badge and haven't got it yet.
            if ($this->type == BADGE_TYPE_SITE) {
                $sql = "SELECT DISTINCT u.id, bi.badgeid
FROM {user} u
{$extrajoin}
LEFT JOIN {badge_issued} bi
ON u.id = bi.userid AND bi.badgeid = :badgeid
WHERE bi.badgeid IS NULL AND u.id != :guestid AND u.deleted = 0 " . $extrawhere;
                $params = array_merge(array('badgeid' => $this->id, 'guestid' => $CFG->siteguest), $extraparams);
                $toearn = $DB->get_fieldset_sql($sql, $params);
            } else {
// For course level badges, get all users who already earned the badge in this course.
// Then find the ones who are enrolled in the course and don't have a badge yet.
                $earned = $DB->get_fieldset_select('badge_issued', 'userid AS id', 'badgeid = :badgeid', array('badgeid' => $this->id));
                $wheresql = '';
                $earnedparams = array();
                if (!empty($earned)) {
                    list($earnedsql, $earnedparams) = $DB->get_in_or_equal($earned, SQL_PARAMS_NAMED, 'u', false);
                    $wheresql = ' WHERE u.id ' . $earnedsql;
                }
                list($enrolledsql, $enrolledparams) = get_enrolled_sql($this->get_context(), 'moodle/badges:earnbadge', 0, true);
                $sql = "SELECT DISTINCT u.id
FROM {user} u
{$extrajoin}
JOIN ({$enrolledsql}) je ON je.id = u.id " . $wheresql . $extrawhere;
                $params = array_merge($enrolledparams, $earnedparams, $extraparams);
                $toearn = $DB->get_fieldset_sql($sql, $params);
            }

            foreach ($toearn as $uid) {
                $reviewoverall = false;
                if ($crit->review($uid, true)) {
                    $crit->mark_complete($uid);
                    if ($this->criteria[BADGE_CRITERIA_TYPE_OVERALL]->method == BADGE_CRITERIA_AGGREGATION_ANY) {
                        $this->criteria[BADGE_CRITERIA_TYPE_OVERALL]->mark_complete($uid);
                        $this->issue($uid);
                        $awards++;
                    } else {
                        $reviewoverall = true;
                    }
                } else {
// Will be reviewed some other time.
                    $reviewoverall = false;
                }
// Review overall if it is required.
                if ($reviewoverall && $this->criteria[BADGE_CRITERIA_TYPE_OVERALL]->review($uid)) {
                    $this->criteria[BADGE_CRITERIA_TYPE_OVERALL]->mark_complete($uid);
                    $this->issue($uid);
                    $awards++;
                }
            }
        }

        return $awards;
    }

    /**
     * Gets an array of completed criteria from 'badge_criteria_met' table.
     *
     * @param int $userid Completions for a user
     * @return array Records of criteria completions
     */
    public function get_criteria_completions($userid) {
        global $DB;
        $completions = array();
        $sql = "SELECT bcm.id, bcm.critid
FROM {badge_criteria_met} bcm
INNER JOIN {badge_criteria} bc ON bcm.critid = bc.id
WHERE bc.badgeid = :badgeid AND bcm.userid = :userid ";
        $completions = $DB->get_records_sql($sql, array('badgeid' => $this->id, 'userid' => $userid));

        return $completions;
    }

    /**
     * Checks if badges has award criteria set up.
     *
     * @return bool A status indicating badge has at least one criterion
     */
    public function has_criteria() {
        if (count($this->criteria) > 0) {
            return true;
        }
        return false;
    }

    /**
     * Returns badge award criteria
     *
     * @return array An array of badge criteria
     */
    public function get_criteria() {
        global $DB;
        $criteria = array();

        if ($records = (array)$DB->get_records('badge_criteria', array('badgeid' => $this->id))) {
            foreach ($records as $record) {
                $criteria[$record->criteriatype] = award_criteria::build((array)$record);
            }
        }

        return $criteria;
    }

    /**
     * Get aggregation method for badge criteria
     *
     * @param int $criteriatype If none supplied, get overall aggregation method (optional)
     * @return int One of BADGE_CRITERIA_AGGREGATION_ALL or BADGE_CRITERIA_AGGREGATION_ANY
     */
    public function get_aggregation_method($criteriatype = 0) {
        global $DB;
        $params = array('badgeid' => $this->id, 'criteriatype' => $criteriatype);
        $aggregation = $DB->get_field('badge_criteria', 'method', $params, IGNORE_MULTIPLE);

        if (!$aggregation) {
            return BADGE_CRITERIA_AGGREGATION_ALL;
        }

        return $aggregation;
    }

    /**
     * Checks if badge has expiry period or date set up.
     *
     * @return bool A status indicating badge can expire
     */
    public function can_expire() {
        if ($this->expireperiod || $this->expiredate) {
            return true;
        }
        return false;
    }

    /**
     * Calculates badge expiry date based on either expirydate or expiryperiod.
     *
     * @param int $timestamp Time of badge issue
     * @return int A timestamp
     */
    public function calculate_expiry($timestamp) {
        $expiry = null;

        if (isset($this->expiredate)) {
            $expiry = $this->expiredate;
        } else if (isset($this->expireperiod)) {
            $expiry = $timestamp + $this->expireperiod;
        }

        return $expiry;
    }

    /**
     * Checks if badge has manual award criteria set.
     *
     * @return bool A status indicating badge can be awarded manually
     */
    public function has_manual_award_criteria() {
        foreach ($this->criteria as $criterion) {
            if ($criterion->criteriatype == BADGE_CRITERIA_TYPE_MANUAL) {
                return true;
            }
        }
        return false;
    }

    /**
     * Fully deletes the badge or marks it as archived.
     *
     * @param $archive bool Achive a badge without actual deleting of any data.
     */
    public function delete($archive = true) {
        global $DB;

        if ($archive) {
            $this->status = BADGE_STATUS_ARCHIVED;
            $this->save();

// Trigger event, badge archived.
            $eventparams = array('objectid' => $this->id, 'context' => $this->get_context());
            $event = \core\event\badge_archived::create($eventparams);
            $event->trigger();
            return;
        }

        $fs = get_file_storage();

// Remove all issued badge image files and badge awards.
// Cannot bulk remove area files here because they are issued in user context.
        $awards = $this->get_awards();
        foreach ($awards as $award) {
            $usercontext = context_user::instance($award->userid);
            $fs->delete_area_files($usercontext->id, 'badges', 'userbadge', $this->id);
        }
        $DB->delete_records('badge_issued', array('badgeid' => $this->id));

// Remove all badge criteria.
        $criteria = $this->get_criteria();
        foreach ($criteria as $criterion) {
            $criterion->delete();
        }

// Delete badge images.
        $badgecontext = $this->get_context();
        $fs->delete_area_files($badgecontext->id, 'badges', 'badgeimage', $this->id);

// Finally, remove badge itself.
        $DB->delete_records('badge', array('id' => $this->id));

// Trigger event, badge deleted.
        $eventparams = array('objectid' => $this->id,
            'context' => $this->get_context(),
            'other' => array('badgetype' => $this->type, 'courseid' => $this->courseid)
        );
        $event = \core\event\badge_deleted::create($eventparams);
        $event->trigger();
    }
}
