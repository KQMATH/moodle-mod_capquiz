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

require_once($CFG->dirroot . '/mod/capquiz/backup/moodle2/restore_capquiz_stepslib.php');

/**
 * CAPQuiz restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_capquiz_activity_task extends restore_activity_task {

    /**
     * This should define settings. Not used at the moment.
     */
    protected function define_my_settings() {

    }

    /**
     * Define the structure steps.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_capquiz_activity_structure_step('capquiz_structure', 'capquiz.xml'));
    }

    /**
     * @return restore_decode_content[]
     */
    static public function define_decode_contents() {
        return [
            new restore_decode_content('capquiz', ['intro'])
        ];
    }

    /**
     * @return restore_decode_rule[]
     */
    static public function define_decode_rules() {
        return [
            new restore_decode_rule('CAPQUIZVIEWBYID', '/mod/capquiz/view.php?id=$1', 'course_module'),
            new restore_decode_rule('CAPQUIZINDEX', '/mod/capquiz/index.php?id=$1', 'course')
        ];
    }

    /**
     * @return restore_log_rule[]
     */
    static public function define_restore_log_rules() {
        return [];
    }

    /**
     * @return restore_log_rule[]
     */
    static public function define_restore_log_rules_for_course() {
        return [];
    }

}
