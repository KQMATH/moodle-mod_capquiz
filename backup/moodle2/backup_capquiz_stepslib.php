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

/**
 * Define the complete choice structure for backup, with file and id annotations.
 */
class backup_capquiz_activity_structure_step extends backup_questions_activity_structure_step {

    protected function define_structure() {
        $capquiz = new backup_nested_element('capquiz', ['id'], [
            'name', 'intro', 'introformat', 'timecreated', 'timemodified', 'published', 'default_user_rating'
        ]);
        $questionlist = new backup_nested_element('questionlist', null, [
            'id', 'capquiz_id', 'question_usage_id', 'title', 'author', 'description',
            'star_ratings', 'is_template', 'time_created', 'time_modified', 'default_question_rating'
        ]);
        $this->add_question_usages($questionlist, 'question_usage_id');
        $questions = new backup_nested_element('questions');
        $question = new backup_nested_element('question', ['id'], [
            'question_id', 'question_list_id', 'rating'
        ]);
        $questionratings = new backup_nested_element('questionratings');
        $questionrating = new backup_nested_element('question_rating', ['id'], [
            'capquiz_question_id', 'rating', 'manual', 'timecreated'
        ]);
        $questionselections = new backup_nested_element('questionselections');
        $questionselection = new backup_nested_element('questionselection', ['id'], [
            'capquiz_id', 'strategy', 'configuration'
        ]);
        $ratingsystems = new backup_nested_element('ratingsystems');
        $ratingsystem = new backup_nested_element('ratingsystem', ['id'], [
            'capquiz_id', 'rating_system', 'configuration'
        ]);
        $users = new backup_nested_element('users');
        $user = new backup_nested_element('user', ['id'], [
            'user_id', 'capquiz_id', 'rating', 'highest_level'
        ]);
        $userratings = new backup_nested_element('userratings');
        $userrating = new backup_nested_element('user_rating', ['id'], [
            'capquiz_user_id', 'rating', 'manual', 'timecreated'
        ]);
        $attempts = new backup_nested_element('attempts');
        $attempt = new backup_nested_element('attempt', ['id'], [
            'slot', 'user_id', 'question_id', 'reviewed', 'answered', 'time_answered', 'time_reviewed',
            'question_rating_id', 'question_prev_rating_id', 'prev_question_rating_id',
            'prev_question_prev_rating_id', 'user_rating_id', 'user_prev_rating_id'
        ]);

        // Build the tree.
        $capquiz->add_child($questionlist);
        $questionlist->add_child($questions);
        $questions->add_child($question);
        $question->add_child($questionratings);
        $questionratings->add_child($questionrating);

        $capquiz->add_child($questionselections);
        $questionselections->add_child($questionselection);

        $capquiz->add_child($ratingsystems);
        $ratingsystems->add_child($ratingsystem);

        $capquiz->add_child($users);
        $users->add_child($user);
        $user->add_child($userratings);
        $userratings->add_child($userrating);
        $user->add_child($attempts);
        $attempts->add_child($attempt);

        // Define sources.
        $capquiz->set_source_table('capquiz', ['id' => backup::VAR_ACTIVITYID]);
        $questionlist->set_source_table('capquiz_question_list', ['capquiz_id' => backup::VAR_PARENTID]);
        $question->set_source_table('capquiz_question', ['question_list_id' => backup::VAR_PARENTID]);
        $questionrating->set_source_table('capquiz_question_rating', ['capquiz_question_id' => backup::VAR_PARENTID]);
        $questionselection->set_source_table('capquiz_question_selection', ['capquiz_id' => backup::VAR_PARENTID]);
        $ratingsystem->set_source_table('capquiz_rating_system', ['capquiz_id' => backup::VAR_PARENTID]);
        if ($this->get_setting_value('userinfo')) {
            $user->set_source_table('capquiz_user', ['capquiz_id' => backup::VAR_PARENTID]);
            $userrating->set_source_table('capquiz_user_rating', ['capquiz_user_id' => backup::VAR_PARENTID]);
            $attempt->set_source_table('capquiz_attempt', ['user_id' => backup::VAR_PARENTID]);
        }

        // Define id annotations.
        $user->annotate_ids('user', 'user_id');
        $question->annotate_ids('question', 'question_id');

        // Define file annotations.
        $capquiz->annotate_files('mod_capquiz', 'intro', null);

        // Return the root element (choice), wrapped into standard activity structure.
        return $this->prepare_activity_structure($capquiz);
    }

}
