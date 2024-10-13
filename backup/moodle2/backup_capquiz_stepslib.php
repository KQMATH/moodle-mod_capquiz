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
 * Defines backup_capquiz_acivity_structure_step class
 *
 * @package     mod_capquiz
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define the complete choice structure for backup, with file and id annotations.
 *
 * @package mod_capquiz
 */
class backup_capquiz_activity_structure_step extends backup_questions_activity_structure_step {
    /**
     * Define the structure to be processed by this backup step.
     */
    protected function define_structure() {
        $capquiz = new backup_nested_element('capquiz', ['id'], [
            'name',
            'intro',
            'introformat',
            'timecreated',
            'timemodified',
            'usermodified',
            'defaultuserrating',
            'starstopass',
            'timedue',
            'numquestioncandidates',
            'minquestionsuntilreappearance',
            'userwinprobability',
            'userkfactor',
            'questionkfactor',
        ]);

        $slots = new backup_nested_element('questions');
        $slot = new backup_nested_element('question', ['id'], [
            'capquizid',
            'rating',
            'timecreated',
            'timemodified',
            'usermodified',
        ]);
        $this->add_question_references($slot, 'mod_capquiz', 'slot');
        $this->add_question_set_references($slot, 'mod_capquiz', 'slot');

        $questionratings = new backup_nested_element('qratings');
        $questionrating = new backup_nested_element('qrating', ['id'], [
            'slotid',
            'rating',
            'manual',
            'timecreated',
            'timemodified',
            'usermodified',
        ]);

        $users = new backup_nested_element('users');
        $user = new backup_nested_element('user', ['id'], [
            'userid',
            'capquizid',
            'questionusageid',
            'rating',
            'higheststars',
            'starsgraded',
            'timecreated',
            'timemodified',
            'usermodified',
        ]);
        $this->add_question_usages($user, 'questionusageid');

        $userratings = new backup_nested_element('userratings');
        $userrating = new backup_nested_element('userrating', ['id'], [
            'capquizuserid',
            'rating',
            'manual',
            'timecreated',
            'timemodified',
            'usermodified',
        ]);

        $attempts = new backup_nested_element('attempts');
        $attempt = new backup_nested_element('attempt', ['id'], [
            'slot',
            'capquizuserid',
            'slotid',
            'reviewed',
            'answered',
            'timeanswered',
            'timereviewed',
            'questionratingid',
            'questionprevratingid',
            'prevquestionratingid',
            'prevquestionprevratingid',
            'userratingid',
            'userprevratingid',
            'timecreated',
            'timemodified',
            'usermodified',
        ]);

        // Build the tree.
        $capquiz->add_child($slots);
        $slots->add_child($slot);
        $slot->add_child($questionratings);
        $questionratings->add_child($questionrating);

        $capquiz->add_child($users);
        $users->add_child($user);
        $user->add_child($userratings);
        $userratings->add_child($userrating);
        $user->add_child($attempts);
        $attempts->add_child($attempt);

        // Define sources.
        $capquiz->set_source_table('capquiz', ['id' => backup::VAR_ACTIVITYID]);
        $slot->set_source_table('capquiz_slot', ['capquizid' => backup::VAR_PARENTID]);
        $questionrating->set_source_table('capquiz_question_rating', ['slotid' => backup::VAR_PARENTID]);
        if ($this->get_setting_value('userinfo')) {
            $user->set_source_table('capquiz_user', ['capquizid' => backup::VAR_PARENTID]);
            $userrating->set_source_table('capquiz_user_rating', ['capquizuserid' => backup::VAR_PARENTID]);
            $attempt->set_source_table('capquiz_attempt', ['capquizuserid' => backup::VAR_PARENTID]);
        }

        // Define id annotations.
        $user->annotate_ids('user', 'userid');

        // Define file annotations.
        $capquiz->annotate_files('mod_capquiz', 'intro', null);

        // Return the root element (choice), wrapped into standard activity structure.
        return $this->prepare_activity_structure($capquiz);
    }
}
