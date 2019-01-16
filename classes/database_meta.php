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

    public static $tablecapquiz = 'capquiz';
    public static $tableuser = 'capquiz_user';
    public static $tableattempt = 'capquiz_attempt';
    public static $tablequestion = 'capquiz_question';
    public static $tableratingsystem = 'capquiz_rating_system';
    public static $tablequestionlist = 'capquiz_question_list';
    public static $tablequestionselection = 'capquiz_question_selection';

    public static $fieldid = 'id';
    public static $fieldlevel = 'level';
    public static $fielduserid = 'user_id';
    public static $fieldcourseid = 'course_id';
    public static $fieldattemptid = 'attempt_id';
    public static $fieldcapquizid = 'capquiz_id';
    public static $fieldistemplate = 'is_template';
    public static $fieldquestionid = 'question_id';
    public static $fieldqlistid = 'question_list_id';

    public static $fieldtitle = 'title';
    public static $fieldrating = 'rating';
    public static $fieldanswered = 'answered';
    public static $fieldreviewed = 'reviewed';
    public static $fielddescription = 'description';

    public static $fieldtimeanswered = 'time_answered';
    public static $fieldtimereviewed = 'time_reviewed';

    public static $tablemoodleuser = 'user';
    public static $tablemoodlecourse = 'course';
    public static $tablemoodlequestion = 'question';

}