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

/**
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class database_meta {

    public static $table_capquiz = 'capquiz';
    public static $table_capquiz_user = 'capquiz_user';
    public static $table_capquiz_badge = 'capquiz_badge';
    public static $table_capquiz_attempt = 'capquiz_attempt';
    public static $table_capquiz_question = 'capquiz_question';
    public static $table_capquiz_rating_system = 'capquiz_rating_system';
    public static $table_capquiz_question_list = 'capquiz_question_list';
    public static $table_capquiz_question_selection = 'capquiz_question_selection';

    public static $field_id = 'id';
    public static $field_level = 'level';
    public static $field_user_id = 'user_id';
    public static $field_badge_id = 'badge_id';
    public static $field_course_id = 'course_id';
    public static $field_attempt_id = 'attempt_id';
    public static $field_capquiz_id = 'capquiz_id';
    public static $field_is_template = 'is_template';
    public static $field_question_id = 'question_id';
    public static $field_question_list_id = 'question_list_id';

    public static $field_title = 'title';
    public static $field_rating = 'rating';
    public static $field_answered = 'answered';
    public static $field_reviewed = 'reviewed';
    public static $field_description = 'description';

    public static $field_time_answered = 'time_answered';
    public static $field_time_reviewed = 'time_reviewed';

    public static $table_moodle_user = 'user';
    public static $table_moodle_course = 'course';
    public static $table_moodle_question = 'question';

}