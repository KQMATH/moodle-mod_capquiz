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
 * Strings for the capquiz plugin, language: english
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'CAPQuiz';
$string['modulename'] = 'CAPQuiz';
$string['modulenameplural'] = 'CAPQuizzes';
$string['modulename_help'] = '<p>The CAPQuiz activity enables a teacher to create quizzes comprising of questions of various types. Questions can be rated to different difficulties while students are given an initial rating. Using these ratings CAPQuiz will match questions to individual students based on their skill level.</p>';
$string['pluginadministration'] = 'CAPQuiz administration';

$string['capquiz:addinstance'] = 'Add an instance of CAPQuiz';
$string['capquiz:instructor'] = 'Edit CAPQuiz instances';
$string['capquiz:student'] = 'Attempt CAPQuiz instances';
$string['capquiz:viewreports'] = 'View capquiz reports';
$string['capquiz:deleteattempts'] = 'Delete capquiz attempts';
$string['capquiz:manage'] = 'Manage capquizzes';

$string['questions_in_list'] = 'Questions in the list';
$string['add_a_quiz_question'] = 'Add a question to the list';
$string['add_the_quiz_question'] = 'Add the question to the list';
$string['add_to_quiz'] = 'Add to capquiz';

$string['question_list'] = 'Question list';
$string['question_lists'] = 'Question lists';
$string['configure_grading'] = 'Configure grading';

$string['add_star'] = 'Add star';
$string['delete_star'] = 'Delete star';
$string['stars_to_pass'] = 'Number of stars required for passing grade';
$string['stars_to_pass_required'] = 'Stars for passing grade is required (0-5)';
$string['due_time_grading'] = 'Time for final grading';
$string['due_date_grading'] = 'Due date for final grading was {$a}.';
$string['grading_is_completed'] = 'Grading is completed.';
$string['grade_has_been_set_pass'] = 'You achieved {$a} stars, which is a <b>passing</b> grade.';
$string['grade_has_been_set_fail'] = 'You achieved {$a} stars, which is a <b>failing</b> grade.';
$string['graded_stars'] = 'Grade';
$string['pass_or_fail'] = 'Pass / Fail';
$string['grade_pass'] = 'Passed';
$string['grade_fail'] = 'Failed';
$string['regrade_all'] = 'Regrade all';

$string['import'] = 'Import';
$string['home'] = 'Home';
$string['name'] = 'Name';
$string['next'] = 'Next';
$string['merge'] = 'Merge';
$string['stars'] = 'Stars';
$string['title'] = 'Title';
$string['author'] = 'Author';
$string['created'] = 'Created';
$string['grading'] = 'Grading';
$string['remove'] = 'Remove';
$string['rating'] = 'Rating';
$string['action'] = 'Action';
$string['select'] = 'Select';
$string['status'] = 'Status';
$string['created'] = 'Created';
$string['publish'] = 'Publish';
$string['template'] = 'Template';
$string['username'] = 'Username';
$string['lastname'] = 'Last name';
$string['firstname'] = 'First name';
$string['configure'] = 'Configure';
$string['dashboard'] = 'Dashboard';
$string['questions'] = 'Questions';
$string['classlist'] = 'Class list';
$string['description'] = 'Description';
$string['matchmaking'] = 'Matchmaking';
$string['rating_system'] = 'Rating system';
$string['name_required'] = 'Name is required';
$string['title_required'] = 'Title is required';
$string['question_count'] = 'Question count';
$string['create_template'] = 'Make template';
$string['enrolled_students'] = 'Enrolled students';
$string['configure_capquiz'] = 'Configure CAPQuiz';
$string['create_question_list'] = 'Create question list';
$string['other_question_lists'] = 'Other question lists';
$string['nothing_here_yet'] = 'Nothing here yet';
$string['reports'] = 'Reports';
$string['attempts'] = 'Attempts';
$string['attempt'] = 'Attempt';

$string['missing_question'] = '<b>This question is missing.</b>';

$string['field_required'] = 'This field is required';
$string['description_required'] = 'Description is required.';
$string['student_k_factor_required'] = 'Student k-factor is required.';
$string['default_user_rating_required'] = 'Default user rating is required.';
$string['question_k_factor_required'] = 'Question k-factor is required.';

$string['your_rating'] = 'Your rating';
$string['question_rating'] = 'Question rating';

$string['need_to_log_in'] = 'You need to log in';

$string['no_questions'] = 'No questions';
$string['no_enrolled_students'] = 'No students are enrolled';
$string['no_questions_added_to_list'] = 'No questions added to the list';
$string['no_matchmaking_strategy_selected'] = 'No selection strategy has been specified';
$string['nothing_to_configure_for_strategy'] = 'There is nothing to configure for this strategy';

$string['update_rating_explanation'] = '<p>The question ratings can be edited below. Changes are saved automatically.</p>';
$string['question_list_no_questions'] = 'This capquiz has no questions. Add some questions from the list to the right';
$string['n_closest'] = 'N-closest';
$string['chronological'] = 'Chronological';
$string['no_strategy_specified'] = 'No strategy specified';

$string['one_star'] = '1 Star';
$string['level_stars'] = '{$a} Stars';
$string['earned_first_star'] = 'You earned your first star in this activity!';
$string['earned_level_star'] = 'You earned {$a} stars in this activity!';
$string['level_rating'] = 'Rating required for {$a} stars';
$string['level_rating_required'] = 'Rating required for {$a} stars is a required field';

$string['user_win_probability'] = 'Desired user win probability';
$string['user_win_probability_required'] = 'Desired user win probability is required';
$string['user_win_probability_help'] = 'This specifies the probability of a student answering the question correctly. A probability of 0.5 will make the matchmaking engine try to find a question with a similar rating as the student.';
$string['choose_rating_system'] = 'Choose rating system';
$string['choose_selection_strategy'] = 'Choose selection strategy';
$string['number_of_questions_to_select'] = 'Number of questions to draw';
$string['number_of_questions_to_select_help'] = 'This indicates how many questions are to be drawn from the question bank before matchmaking. A match is made by selecting a question randomly from these.';
$string['number_of_questions_to_select_required'] = 'Number of questions to draw is required';
$string['prevent_question_n_times'] = 'Prevent the same question to be used for N attempts';
$string['prevent_question_n_times_help'] = 'This will prevent a student from being matched with the same question for the specified number of attempts.';

$string['available_questions'] = 'Available questions';

$string['tooltip_achieved_star'] = 'You have achieved this star!';
$string['tooltip_lost_star'] = 'You have achieved this star, but your rating is currently below the star\'s threshold.';
$string['tooltip_no_star'] = 'You have yet to achieve this star.';
$string['tooltip_help_star'] = 'Every student has a proficiency rating in the CAPQuiz activity.  This increases when successfully answering a question, and decreases with wrong answers.  Stars are achieved at certain rating levels, and never lost. I.e. a student can sometimes lose rating points and fall below a star\'s threshold, without losing the star.  It is suggested that a certain number of stars are required for a compulsory assignment.  Hover your mouse over a star to see rating details.';

$string['select_template'] = 'Select one of these templates for your capquiz';
$string['no_templates_created'] = 'No templates have been created.';
$string['create_own_template'] = 'You can also create your own';

$string['default_user_rating'] = 'Default user rating';
$string['default_question_rating'] = 'Default question rating';

$string['default_rating_specified_rule'] = 'Default rating must be specified';
$string['default_rating_numeric_rule'] = 'Default rating must be a numeric value';

$string['student_k_factor'] = 'Student k-factor';
$string['question_k_factor'] = 'Question k-factor';
$string['student_k_factor_help'] = 'A higher student k-factor will make ratings change faster. If a student answers correctly, the student will receive a higher rating gain and similarly will lose more rating if the question was answered correctly';
$string['question_k_factor_help'] = 'A higher question k-factor will make question ratings change faster. Question ratings will only change if one question is answered correctly and the other question is answered incorrectly, in no particular order. The question that is answered incorrectly will gain rating, since it \'won\' over the question that was answered correctly.';

$string['student_k_factor_specified_rule'] = 'Student k-factor must be specified';
$string['question_k_factor_specified_rule'] = 'Question k-factor must be specified';
$string['k_factor_numeric_rule'] = 'K-factor must be a numeric value';

$string['publish_explanation'] = '<p>Students are unable to answer questions as long as the capquiz is not published. This is useful if you\'re still building your question list and modifying question ratings. Similarly, modifying the default student rating before a capquiz has been published ensures that all students are given the same initial rating.</p><p>Students can answer questions once the capquiz has been published. After this point you can still modify your question list and assign different rating to questions. However, modifying the default student rating will not influence rating of students that has already entered the capquiz, but will influence the initial rating of students that has yet to enter the capquiz.</p><p>Once CAPQuiz has been published, it can not be reverted and will be visible to students.</p>';
$string['template_explanation'] = '<p>A template is a read-only copy of a question list. Templates allow instructors to reuse question lists between courses or semesters, and can be shared with other instructors. Since a template is a copy of it\'s original question list, instructors can be sure that ratings won\'t be influenced when sharing between CAPQuiz instances. However, if multiple question lists are created from the same template, any changes made to the original <em>question</em> in the question bank will be visible in all templates and question lists. This includes renaming the question title, changing correct answers, descriptions and marks.<p>';
$string['template_no_questions_in_list'] = '<p>There doesn\'t seem to be any questions in the question list for this CAPQuiz instance. Creating a template requires questions in the question list. Add some questions and come back to create your template.</p>';
$string['publish_no_questions_in_list'] = '<p>There doesn\'t seem to be any questions in the question list for this CAPQuiz instance. You must have at least one question before you can publish</p>';
$string['publish_already_published'] = '<p>This CAPQuiz is already published</p>';

$string['no_question_list_assigned'] = 'No question list has been assigned';
$string['published'] = 'Published';
$string['not_published'] = 'Not published';
$string['question_list_not_published'] = 'The question list is not yet published';

$string['question_list_settings'] = 'Question list settings';
$string['you_finished_capquiz'] = 'You have finished this capquiz!';

$string['deleted_grades'] = 'Deleted grades';
$string['deleted_attempts'] = 'Deleted attempts';

$string['privacy:metadata:core_question'] = 'The CAPQuiz activity stores question usage information in the core_question subsystem.';
$string['privacy:metadata:capquiz_attempt'] = 'Details about each attempt on a CAPQuiz.';
$string['privacy:metadata:capquiz_attempt:userid'] = 'The user who made the attempt.';
$string['privacy:metadata:capquiz_attempt:time_answered'] = 'The time that the attempt was answered.';
$string['privacy:metadata:capquiz_attempt:time_reviewed'] = 'The time that the attempt was reviewed.';
$string['privacy:metadata:capquiz_user'] = 'Additional details stored about the user';
$string['privacy:metadata:capquiz_user:userid'] = 'The CAPQuiz user.';
$string['privacy:metadata:capquiz_user:rating'] = 'The rating of the user.';
$string['privacy:metadata:capquiz_user:highest_level'] = 'The user\'s highest number of stars achieved.';

$string['privacy:metadata:capquiz_user_rating'] = 'Details about each user rating created in a CAPQuiz.';
$string['privacy:metadata:capquiz_user_rating:capquiz_user_id'] = 'The user who\'s rating it is.';
$string['privacy:metadata:capquiz_user_rating:rating'] = 'The user\'s rating.';
$string['privacy:metadata:capquiz_user_rating:manual'] = 'Whether or not the user rating was created manually';
$string['privacy:metadata:capquiz_user_rating:timecreated'] = 'The time that the user rating was created';

$string['userratings'] = 'User ratings';
$string['userrating'] = 'User rating';
$string['questionrating'] = 'Question rating';
$string['report'] = 'report';

$string['settings'] = 'CAPQuiz settings';
$string['hideshow'] = 'Hide/Show';
$string['noreports'] = 'No reports accessible';

$string['subplugintype_capquizreport'] = 'Report';
$string['subplugintype_capquizreport_plural'] = 'Reports';
$string['reportplugin'] = 'Report plugins';
$string['managecapquizreportplugins'] = 'Manage report plugins';
$string['capquizreporttype'] = 'Report type';
$string['erroraccessingreport'] = 'You cannot access this report';

$string['true'] = 'True';
$string['false'] = 'False';
$string['questionid'] = 'Question id';
$string['moodlequestionid'] = 'Moodle question id';
$string['capquizquestionid'] = 'CAPQuiz question id';
$string['userid'] = 'User id';
$string['timeanswered'] = 'Time answered';
$string['timereviewed'] = 'Time reviewed';
$string['strftimedatetimeseconds'] = '%d %B %Y, %I:%M:%S %p';
$string['reportshowonlyanswered'] = 'Show only answered attempts';
$string['reportshowonlyanswered_help'] = 'Show only attempts by students that have been answered and submitted.';
