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
 * @author      Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright   2024 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['add_star'] = 'Add star';
$string['add_to_quiz'] = 'Add to capquiz';
$string['attempt'] = 'Attempt';
$string['attemptquiz'] = 'Attempt quiz';
$string['attemptquizinfo'] = 'Question ratings will not be affected while previewing the quiz.';
$string['attempts'] = 'Attempts';

$string['capquiz:addinstance'] = 'Add an instance of CAPQuiz';
$string['capquiz:deleteattempts'] = 'Delete CAPQuiz attempts';
$string['capquiz:instructor'] = 'Edit CAPQuiz instances';
$string['capquiz:manage'] = 'Manage CAPQuiz instances';
$string['capquiz:student'] = 'Attempt CAPQuiz instances';
$string['capquiz:viewreports'] = 'View CAPQuiz reports';

$string['classlist'] = 'Class list';

$string['default_question_rating'] = 'Default question rating';
$string['default_rating_numeric_rule'] = 'Default rating must be a numeric value';
$string['default_user_rating'] = 'Default user rating';
$string['delete_star'] = 'Delete star';
$string['deleted_attempts'] = 'Deleted attempts';
$string['deleted_grades'] = 'Deleted grades';
$string['due_date_grading'] = 'Due date for final grading was {$a}.';
$string['due_time_grading'] = 'Time for final grading';

$string['enrolled_students'] = 'Enrolled students';
$string['erroraccessingreport'] = 'You cannot access this report';
$string['errorvalidatestarratings'] = 'The star ratings list is invalid';
$string['errorvalidatestarstopass'] = 'Stars to pass can\'t be higher than the number of configured stars';

$string['false'] = 'False';
$string['fromquestionbank'] = 'from question bank';

$string['grade_fail'] = 'Failed';
$string['grade_has_been_set_fail'] = 'You achieved {$a} stars, which is a <b>failing</b> grade.';
$string['grade_has_been_set_pass'] = 'You achieved {$a} stars, which is a <b>passing</b> grade.';
$string['grade_pass'] = 'Passed';
$string['graded_stars'] = 'Grade';
$string['grading'] = 'Grading';
$string['grading_is_completed'] = 'Grading is completed.';

$string['hideshow'] = 'Hide/Show';

$string['k_factor_numeric_rule'] = 'K-factor must be a numeric value';

$string['level_rating'] = 'Rating for {$a} stars';
$string['level_stars'] = '{$a} Stars';

$string['managecapquizreportplugins'] = 'Manage report plugins';
$string['modulename'] = 'CAPQuiz';
$string['modulename_help'] = '<p>The CAPQuiz activity enables a teacher to create quizzes comprising of questions of various types. Questions can be rated to different difficulties while students are given an initial rating. Using these ratings CAPQuiz will match questions to individual students based on their skill level.</p>';
$string['modulenameplural'] = 'CAPQuizzes';
$string['moodlequestionid'] = 'Moodle question id';

$string['name'] = 'Name';
$string['no_enrolled_students'] = 'No students are enrolled';
$string['no_questions_added_to_list'] = 'No questions added to the list';
$string['noreports'] = 'No reports accessible';
$string['notopenforstudents'] = 'Not open for students';
$string['number_of_questions_to_select'] = 'Number of questions to draw';
$string['number_of_questions_to_select_help'] = 'This indicates how many questions are to be drawn from the question bank before matchmaking. A match is made by selecting a question randomly from these.';
$string['number_of_questions_to_select_required'] = 'Number of questions to draw is required';

$string['pass_or_fail'] = 'Pass / Fail';
$string['pluginadministration'] = 'CAPQuiz administration';
$string['pluginname'] = 'CAPQuiz';
$string['prevent_question_n_times'] = 'Prevent the same question to be used for N attempts';
$string['prevent_question_n_times_help'] = 'This will prevent a student from being matched with the same question for the specified number of attempts.';
$string['privacy:metadata:capquiz_attempt'] = 'Details about each attempt on a CAPQuiz.';
$string['privacy:metadata:capquiz_attempt:capquizuserid'] = 'The CAPQuiz user who made the attempt.';
$string['privacy:metadata:capquiz_attempt:timeanswered'] = 'The time that the attempt was answered.';
$string['privacy:metadata:capquiz_attempt:timereviewed'] = 'The time that the attempt was reviewed.';
$string['privacy:metadata:capquiz_user'] = 'Additional details stored about the user';
$string['privacy:metadata:capquiz_user:higheststars'] = 'The user\'s highest number of stars achieved.';
$string['privacy:metadata:capquiz_user:rating'] = 'The rating of the user.';
$string['privacy:metadata:capquiz_user:starsgraded'] = 'How many stars the user has got as a grade.';
$string['privacy:metadata:capquiz_user:userid'] = 'The user.';
$string['privacy:metadata:capquiz_user_rating'] = 'Details about each user rating created in a CAPQuiz.';
$string['privacy:metadata:capquiz_user_rating:capquizuserid'] = 'The user who\'s rating it is.';
$string['privacy:metadata:capquiz_user_rating:manual'] = 'Whether or not the user rating was created manually';
$string['privacy:metadata:capquiz_user_rating:rating'] = 'The user\'s rating.';
$string['privacy:metadata:capquiz_user_rating:timecreated'] = 'The time that the user rating was created';
$string['privacy:metadata:core_question'] = 'The CAPQuiz activity stores question usage information in the core_question subsystem.';

$string['question_k_factor'] = 'Question k-factor';
$string['question_k_factor_help'] = 'A higher question k-factor will make question ratings change faster. Question ratings will only change if one question is answered correctly and the other question is answered incorrectly, in no particular order. The question that is answered incorrectly will gain rating, since it \'won\' over the question that was answered correctly.';
$string['question_k_factor_required'] = 'Question k-factor is required.';
$string['question_k_factor_specified_rule'] = 'Question k-factor must be specified';
$string['questionbehaviourwarningadaptive'] = '\'Adaptive mode\' is not recommended for CAPQuiz. Please consider \'Interactive with multiple tries\' instead.';
$string['questionid'] = 'Question id';
$string['questionrating'] = 'Question rating';
$string['questions'] = 'Questions';
$string['questionselection'] = 'Question selection';

$string['rating'] = 'Rating';
$string['rating_system'] = 'Rating system';
$string['regrade_all'] = 'Regrade all';
$string['reportplugin'] = 'Report plugins';
$string['reportshowonlyanswered'] = 'Show only answered attempts';
$string['reportshowonlyanswered_help'] = 'Show only attempts by students that have been answered and submitted.';
$string['reviewattemptdisplayoptions'] = 'Feedback shown during review';
$string['reviewattemptdisplayoptions_help'] = 'Select the types of feedback that are visible to students after submitting an attempt.';

$string['settings'] = 'CAPQuiz settings';
$string['stars'] = 'Stars';
$string['stars_to_pass'] = 'Number of stars required for passing grade';
$string['stars_to_pass_required'] = 'Stars for passing grade is required';
$string['strftimedatetimeseconds'] = '%d %B %Y, %I:%M:%S %p';
$string['student_k_factor'] = 'Student k-factor';
$string['student_k_factor_help'] = 'A higher student k-factor will make ratings change faster. If a student answers correctly, the student will receive a higher rating gain and similarly will lose more rating if the question was answered correctly';
$string['student_k_factor_required'] = 'Student k-factor is required.';
$string['student_k_factor_specified_rule'] = 'Student k-factor must be specified';
$string['subplugintype_capquizreport'] = 'Report';
$string['subplugintype_capquizreport_plural'] = 'Reports';

$string['timeanswered'] = 'Time answered';
$string['timeduenotset'] = 'No due time has been set';
$string['timeopen'] = 'Open for students';
$string['timeopen_help'] = 'Opens the quiz for students only after the open time. The quiz will not open if this field is disabled.';
$string['timereviewed'] = 'Time reviewed';
$string['title'] = 'Title';
$string['title_required'] = 'Title is required';
$string['tooltip_achieved_star'] = 'You have achieved this star!';
$string['tooltip_help_star'] = 'Every student has a proficiency rating in the CAPQuiz activity.  This increases when successfully answering a question, and decreases with wrong answers.  Stars are achieved at certain rating levels, and never lost. I.e. a student can sometimes lose rating points and fall below a star\'s threshold, without losing the star.  It is suggested that a certain number of stars are required for a compulsory assignment.  Hover your mouse over a star to see rating details.';
$string['tooltip_lost_star'] = 'You have achieved this star, but your rating is currently below the star\'s threshold.';
$string['tooltip_no_star'] = 'You have yet to achieve this star.';
$string['true'] = 'True';

$string['update_rating_explanation'] = '<p>The question ratings can be edited below. Changes are saved automatically.</p>';
$string['user_win_probability'] = 'Desired user win probability';
$string['user_win_probability_help'] = 'This specifies the probability of a student answering the question correctly. A probability of 0.5 will make the matchmaking engine try to find a question with a similar rating as the student.';
$string['user_win_probability_required'] = 'Desired user win probability is required';
$string['userid'] = 'User id';
$string['userrating'] = 'User rating';
$string['userratings'] = 'User ratings';

$string['you_finished_capquiz'] = 'You have finished this capquiz!';
