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
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'CAPQuiz';
$string['modulename'] = 'CAPQuiz';
$string['modulenameplural'] = 'CAPQuizzes';
$string['modulename_help'] = '<p>The CAPQuiz activity enables a teacher to create quizzes comprising of questions of various types.' .
    'Questions can be rated to different difficulties while students are given an initial rating. ' .
    'Using these ratings CAPQuiz will match questions to individual students based on their skill level.</p>';
$string['pluginadministration'] = 'CAPQuiz administration';

$string['capquiz:addinstance'] = 'Add an instance of CAPQuiz';
$string['capquiz:attempt'] = 'Attempt an CAPQuiz';
$string['capquiz:control'] = 'Control a CAPQuiz. (Usually for instructors only)';
$string['capquiz:editquestions'] = 'Edit questions for an CAPQuiz.';
$string['capquiz:seeresponses'] = 'View student responses to grade them';

$string['privacy:metadata:core_question'] = 'Question attempts are stored.';

$string['questions_in_list'] = 'Questions in the list';
$string['add_a_quiz_question'] = 'Add a question to the list';
$string['add_the_quiz_question'] = 'Add the question to the list';

$string['question_list'] = 'Question list';
$string['question_lists'] = 'Question lists';
$string['configure_badge_rating'] = 'Configure badge rating';

$string['home'] = 'Home';
$string['name'] = 'Name';
$string['next'] = 'Next';
$string['stars'] = 'Stars';
$string['title'] = 'Title';
$string['author'] = 'Author';
$string['created'] = 'Created';
$string['badges'] = 'Badges';
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

$string['update_rating_explanation'] = '<p>The question ratings can be edited below. Changes are saved automatically.</p>';
$string['question_list_no_questions'] = 'This quiz has no questions. Add some questions from the list to the right';

$string['one_star'] = '1 Star';
$string['level_stars'] = '{$a} Stars';
$string['earned_first_star'] = 'You earned your first star in this activity!';
$string['earned_level_star'] = 'You earned {$a} stars in this activity!';
$string['level_rating'] = 'Level {$a} rating';
$string['level_rating_required'] = 'Rating for level {$a} is required';

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

$string['select_template'] = 'Select one of these templates for your quiz';
$string['no_templates_created'] = 'No templates have been created.';
$string['create_own_template'] = 'You can also create your own';

$string['default_user_rating'] = 'Default user rating';
$string['default_question_rating'] = 'Default question rating';

$string['default_rating_specified_rule'] = 'Default rating must be specified';
$string['default_rating_numeric_rule'] = 'Default rating must be a numeric value';

$string['student_k_factor'] = 'Student k-factor';
$string['question_k_factor'] = 'Question k-factor';
$string['student_k_factor_help'] = 'A higher student k-factor will make ratings change faster. If a student answers correctly, the student will receive a higher rating gain and similarly will lose more rating if the question was answered correctly';
$string['question_k_factor_help'] = "A higher question k-factor will make question ratings change faster. Question ratings will only change if one question is answered correctly and the other question is answered incorrectly, in no particular order. The question that is answered incorrectly will gain rating, since it 'won' over the question that was answered correctly.";

$string['student_k_factor_specified_rule'] = 'Student k-factor must be specified';
$string['question_k_factor_specified_rule'] = 'Question k-factor must be specified';
$string['k_factor_numeric_rule'] = 'K-factor must be a numeric value';

$string['publish_explanation'] =
    "<p>Students are unable to answer questions as long as the quiz is not published. This is useful if you're still building your question list and modifying question ratings. " .
    "Similarly, modifying the default student rating before a quiz has been published ensures that all students are given the same initial rating.</p>" .
    "<p>Students can answer questions once the quiz has been published. " .
    "After this point you can still modify your question list and assign different rating to questions. " .
    "However, modifying the default student rating will not influence rating of students that has already entered the quiz, but will influence the initial rating of students that has yet to enter the quiz. </p>" .
    "<p>Once CAPQuiz has been published, it can not be reverted and will be visible to students.</p>";

$string['template_explanation'] =
    "<p>A template is a read-only copy of a question list. " .
    "Templates allow instructors to reuse question lists between courses or semesters, and can be shared with other instructors. " .
    "Since a template is a copy of it's original question list, instructors can be sure that ratings won't be influenced when sharing between CAPQuiz instances. " .
    "However, if multiple question lists are created from the same template, any changes made to the original <em>question</em> in the question bank will be visible in all templates and question lists. " .
    "This includes renaming the question title, changing correct answers, descriptions and marks.<p>";

$string['template_no_questions_in_list'] = "<p>There doesn't seem to be any questions in the question list for this CAPQuiz instance. Creating a template requires questions in the question list. Add some questions and come back to create your template.</p>";
$string['publish_no_questions_in_list'] = "<p>There doesn't seem to be any questions in the question list for this CAPQuiz instance. You must have at least one question before you can publish</p>";
$string['publish_already_published'] = "<p>This CAPQuiz is already published</p>";

$string['no_question_list_assigned'] = 'No question list has been assigned';
$string['published'] = 'Published';
$string['not_published'] = 'Not published';

$string['privacy:metadata:core_question'] = 'The CAPQuiz activity stores question usage information in the core_question subsystem.';
$string['privacy:metadata:capquiz_attempt'] = 'Details about each attempt on a CAPQuiz.';
$string['privacy:metadata:capquiz_attempt:userid'] = 'The user who made the attempt.';
$string['privacy:metadata:capquiz_attempt:time_answered'] = 'The time that the attempt was answered.';
$string['privacy:metadata:capquiz_attempt:time_reviewed'] = 'The time that the attempt was reviewed.';
$string['privacy:metadata:capquiz_user'] = 'Additional details stored about the user';
$string['privacy:metadata:capquiz_user:userid'] = 'The CAPQuiz user.';
$string['privacy:metadata:capquiz_user:rating'] = 'The rating of the user.';
$string['privacy:metadata:capquiz_user:highest_level'] = "The user's highest achieved level.";