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
 * Define all the restore steps that will be used by the restore_capquiz_activity_task
 *
 * @package     mod_capquiz
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Structure step to restore one assignment activity
 *
 * @package mod_capquiz
 */
class restore_capquiz_activity_structure_step extends restore_questions_activity_structure_step {
    /**
     * @var \stdClass for inform_new_usage_id
     */
    private $currentcapuser;

    /**
     * Define the structure to be processed by this backup step.
     */
    protected function define_structure() {
        $paths = [];
        $paths[] = new restore_path_element('capquiz', '/activity/capquiz');
        $paths[] = new restore_path_element('capquiz_slot', '/activity/capquiz/slots/slot');
        $paths[] = new restore_path_element('capquiz_question_rating', '/activity/capquiz/slots/slot/qratings/qrating');

        if ($this->get_setting_value('userinfo')) {
            $capuser = new restore_path_element('capquiz_user', '/activity/capquiz/users/user');
            $this->add_question_usages($capuser, $paths);
            $paths[] = $capuser;
            $paths[] = new restore_path_element('capquiz_user_rating', '/activity/capquiz/users/user/userratings/userrating');
            $paths[] = new restore_path_element('capquiz_attempt', '/activity/capquiz/users/user/attempts/attempt');
        }
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Processes and backs up capquiz
     *
     * @param object $data
     */
    protected function process_capquiz($data): void {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->timeopen = $this->apply_date_offset($data->timeopen);
        $data->timedue = $this->apply_date_offset($data->timedue);
        $newitemid = $DB->insert_record('capquiz', $data);
        $this->apply_activity_instance($newitemid);
        $this->set_mapping('capquiz', $oldid, $newitemid);
    }

    /**
     * Processes and backs up capquiz slot
     *
     * @param object $data
     */
    protected function process_capquiz_slot($data): void {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->capquizid = $this->get_new_parentid('capquiz');
        $newitemid = $DB->insert_record('capquiz_slot', $data);
        $this->set_mapping('capquiz_slot', $oldid, $newitemid);
    }

    /**
     * Processes and backs up capquiz question rating
     *
     * @param object $data
     */
    protected function process_capquiz_question_rating($data): void {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->slotid = $this->get_new_parentid('capquiz_slot');
        $newitemid = $DB->insert_record('capquiz_question_rating', $data);
        $this->set_mapping('capquiz_question_rating', $oldid, $newitemid);
    }

    /**
     * Processes and backs up capquiz user
     *
     * @param object $data
     */
    protected function process_capquiz_user($data) {
        $data = (object)$data;
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->capquizid = $this->get_new_parentid('capquiz');
        $this->currentcapuser = clone($data);
    }

    /**
     * Processes and backs up capquiz user rating
     *
     * @param object $data
     */
    protected function process_capquiz_user_rating($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->capquizuserid = $this->get_new_parentid('capquiz_user');
        $newitemid = $DB->insert_record('capquiz_user_rating', $data);
        $this->set_mapping('capquiz_user_rating', $oldid, $newitemid);
    }

    /**
     * Processes and backs up capquiz question attempt
     *
     * @param object $data
     */
    protected function process_capquiz_attempt($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->capquizuserid = $this->get_new_parentid('capquiz_user');
        $data->slotid = $this->get_mappingid('capquiz_slot', $data->slotid);
        $data->questionratingid = $this->get_mappingid('capquiz_question_rating', $data->questionratingid);
        $data->questionprevratingid = $this->get_mappingid('capquiz_question_rating', $data->questionprevratingid);
        $data->prevquestionratingid = $this->get_mappingid('capquiz_question_rating', $data->prevquestionratingid);
        $data->prevquestionprevratingid = $this->get_mappingid('capquiz_question_rating', $data->prevquestionprevratingid);
        $data->userratingid = $this->get_mappingid('capquiz_user_rating', $data->userratingid);
        $data->userprevratingid = $this->get_mappingid('capquiz_user_rating', $data->userprevratingid);
        $newitemid = $DB->insert_record('capquiz_attempt', $data);
        $this->set_mapping('capquiz_attempt', $oldid, $newitemid);
    }

    /**
     * Updates a users usageid and maps the users old and new ids
     *
     * @param int $newusageid
     */
    protected function inform_new_usage_id($newusageid): void {
        global $DB;
        $data = $this->currentcapuser;
        $oldid = $data->id;
        $data->questionusageid = $newusageid;
        $newitemid = $DB->insert_record('capquiz_user', $data);
        $this->set_mapping('capquiz_user', $oldid, $newitemid);
    }

    /**
     * Add all the existing file, given their component and filearea and one backup_ids itemname to match with
     */
    protected function after_execute() {
        $this->add_related_files('mod_capquiz', 'intro', null);
    }
}
