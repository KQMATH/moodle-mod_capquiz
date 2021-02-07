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

defined('MOODLE_INTERNAL') || die();

class restore_capquiz_activity_structure_step extends restore_questions_activity_structure_step {

    /**
     * @var \stdClass for inform_new_usage_id
     */
    private $currentquestionlist;

    protected function define_structure() {
        $paths = [];
        $paths[] = new restore_path_element('capquiz', '/activity/capquiz');
        $questionlist = new restore_path_element('capquiz_question_list', '/activity/capquiz/questionlist');
        $paths[] = $questionlist;
        $paths[] = new restore_path_element('capquiz_question', '/activity/capquiz/questionlist/questions/question');
        $paths[] = new restore_path_element(
            'capquiz_question_rating', '/activity/capquiz/questionlist/questions/question/questionratings/question_rating');
        $paths[] = new restore_path_element('capquiz_question_selection', '/activity/capquiz/questionselections/questionselection');
        $paths[] = new restore_path_element('capquiz_rating_system', '/activity/capquiz/ratingsystems/ratingsystem');
        if ($this->get_setting_value('userinfo')) {
            $capuser = new restore_path_element('capquiz_user', '/activity/capquiz/users/user');
            $this->add_question_usages($capuser, $paths);
            $paths[] = $capuser;
            $paths[] = new restore_path_element('capquiz_user_rating', '/activity/capquiz/users/user/userratings/user_rating');
            $paths[] = new restore_path_element('capquiz_attempt', '/activity/capquiz/users/user/attempts/attempt');
        }
        return $this->prepare_activity_structure($paths);
    }

    protected function process_capquiz($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $newitemid = $DB->insert_record('capquiz', $data);
        $this->apply_activity_instance($newitemid);
        $this->set_mapping('capquiz', $oldid, $newitemid);
    }

    protected function process_capquiz_question_list($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->capquiz_id = $this->get_new_parentid('capquiz');
        $data->time_created = $this->apply_date_offset($data->time_created);
        $data->time_modified = $this->apply_date_offset($data->time_modified);
        $data->context_id = \context_course::instance($this->get_courseid())->id;
        $newitemid = $DB->insert_record('capquiz_question_list', $data);
        $this->set_mapping('capquiz_question_list', $oldid, $newitemid);
    }

    protected function process_capquiz_question($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->question_id = $this->get_mappingid('question', $data->question_id);
        if (!$data->question_id) {
            return;
        }
        $data->question_list_id = $this->get_new_parentid('capquiz_question_list');
        $newitemid = $DB->insert_record('capquiz_question', $data);
        $this->set_mapping('capquiz_question', $oldid, $newitemid);
    }

    protected function process_capquiz_question_rating($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->capquiz_question_id = $this->get_new_parentid('capquiz_question');
        $newitemid = $DB->insert_record('capquiz_question_rating', $data);
        $this->set_mapping('capquiz_question_rating', $oldid, $newitemid);
    }

    protected function process_capquiz_question_selection($data) {
        global $DB;
        $data = (object)$data;
        $data->capquiz_id = $this->get_new_parentid('capquiz');
        $oldid = $data->id;
        $newitemid = $DB->insert_record('capquiz_question_selection', $data);
        $this->set_mapping('capquiz_question_selection', $oldid, $newitemid);
    }

    protected function process_capquiz_rating_system($data) {
        global $DB;
        $data = (object)$data;
        $data->capquiz_id = $this->get_new_parentid('capquiz');
        $oldid = $data->id;
        $newitemid = $DB->insert_record('capquiz_rating_system', $data);
        $this->set_mapping('capquiz_rating_system', $oldid, $newitemid);
    }

    protected function process_capquiz_user($data) {
        $data = (object)$data;
        $data->user_id = $this->get_mappingid('user', $data->user_id);
        $data->capquiz_id = $this->get_new_parentid('capquiz');
        $this->currentcapuser = clone($data);
    }

    protected function process_capquiz_user_rating($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->capquiz_user_id = $this->get_new_parentid('capquiz_user');
        $newitemid = $DB->insert_record('capquiz_user_rating', $data);
        $this->set_mapping('capquiz_user_rating', $oldid, $newitemid);
    }

    protected function process_capquiz_attempt($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->user_id = $this->get_new_parentid('capquiz_user');
        $data->question_id = $this->get_mappingid('capquiz_question', $data->question_id);
        $data->question_rating_id = $this->get_mappingid('capquiz_question_rating', $data->question_rating_id);
        $data->question_prev_rating_id = $this->get_mappingid('capquiz_question_rating', $data->question_prev_rating_id);
        $data->prev_question_rating_id = $this->get_mappingid('capquiz_question_rating', $data->prev_question_rating_id);
        $data->prev_question_prev_rating_id = $this->get_mappingid('capquiz_question_rating', $data->prev_question_prev_rating_id);
        $data->user_rating_id = $this->get_mappingid('capquiz_user_rating', $data->user_rating_id);
        $data->user_prev_rating_id = $this->get_mappingid('capquiz_user_rating', $data->user_prev_rating_id);
        $newitemid = $DB->insert_record('capquiz_attempt', $data);
        $this->set_mapping('capquiz_attempt', $oldid, $newitemid);
    }

    protected function inform_new_usage_id($newusageid) {
        global $DB;
        $data = $this->currentcapuser;
        $oldid = $data->id;
        $data->question_usage_id = $newusageid;
        $newitemid = $DB->insert_record('capquiz_user', $data);
        $this->set_mapping('capquiz_user', $oldid, $newitemid);
    }

    protected function after_execute() {
        $this->add_related_files('mod_capquiz', 'intro', null);
    }

}
