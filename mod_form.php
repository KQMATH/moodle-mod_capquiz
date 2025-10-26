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

declare(strict_types=1);

use mod_capquiz\capquiz;
use mod_capquiz\local\helpers\questions;
use mod_capquiz\local\helpers\stars;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * CAPQuiz settings form.
 *
 * @package     mod_capquiz
 * @author      Sebastian Gundersen <sebastian@sgundersen.com>
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2024 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_capquiz_mod_form extends moodleform_mod {
    /**
     * Defines the form.
     */
    public function definition(): void {
        global $CFG;
        require_once($CFG->dirroot . '/question/engine/lib.php');

        $form = $this->_form;

        $form->addElement('header', 'general', get_string('general', 'form'));
        $form->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $form->setType('name', PARAM_TEXT);
        $form->addRule('name', null, 'required', null, 'client');
        $form->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        $isnewinstance = empty($this->_cm);
        $cm = $this->_cm;
        $capquiz = new capquiz((int)$cm?->instance);

        // Open and due dates.
        $form->addElement('date_time_selector', 'timeopen', get_string('timeopen', 'capquiz'), ['optional' => true]);
        $form->addHelpButton('timeopen', 'timeopen', 'capquiz');

        $form->addElement('date_time_selector', 'timedue', get_string('due_time_grading', 'capquiz'), ['optional' => true]);

        $unsupportedbehaviours = implode(',', questions::get_unsupported_question_behaviours());
        $currentbehaviour = $capquiz->get('questionbehaviour');
        $behaviours = question_engine::get_behaviour_options($currentbehaviour);
        $behaviours = question_engine::sort_behaviours($behaviours, '', $unsupportedbehaviours, $currentbehaviour);

        $form->addElement('select', 'questionbehaviour', get_string('howquestionsbehave', 'question'), $behaviours);
        $form->addHelpButton('questionbehaviour', 'howquestionsbehave', 'question');
        $form->setDefault('questionbehaviour', $currentbehaviour);
        $form->addElement('static', 'questionbehaviourwarning', '', get_string('questionbehaviourwarningadaptive', 'capquiz'));
        $form->hideIf('questionbehaviourwarning', 'questionbehaviour', 'ne', 'adaptive');

        $group = [
            $form->createElement('checkbox', 'reviewfeedback', '', get_string('specificfeedback', 'question')),
            $form->createElement('checkbox', 'reviewgeneralfeedback', '', get_string('generalfeedback', 'question')),
            $form->createElement('checkbox', 'reviewrightanswer', '', get_string('rightanswer', 'question')),
            $form->createElement('checkbox', 'reviewcorrectness', '', get_string('whethercorrect', 'question')),
        ];
        $displayoptions = questions::get_question_display_options($capquiz);
        $form->setDefault('reviewfeedback', $displayoptions->feedback);
        $form->setDefault('reviewgeneralfeedback', $displayoptions->generalfeedback);
        $form->setDefault('reviewrightanswer', $displayoptions->rightanswer);
        $form->setDefault('reviewcorrectness', $displayoptions->correctness);
        $form->addGroup($group, 'reviewoptions', get_string('reviewattemptdisplayoptions', 'capquiz'), null, false);
        $form->addHelpButton('reviewoptions', 'reviewattemptdisplayoptions', 'capquiz');

        // Grading.
        $form->addElement('header', 'gradingheader', get_string('grading', 'capquiz'));

        $defaultratingnumericrule = get_string('default_rating_numeric_rule', 'capquiz');

        $form->addElement('text', 'defaultuserrating', get_string('default_user_rating', 'capquiz'));
        $form->setType('defaultuserrating', PARAM_FLOAT);
        $form->setDefault('defaultuserrating', (int)$capquiz->get('defaultuserrating'));
        $form->addRule('defaultuserrating', get_string('requiredelement', 'form'), 'required', null, 'client');
        $form->addRule('defaultuserrating', $defaultratingnumericrule, 'numeric', null, 'client');

        $form->addElement('text', 'defaultquestionrating', get_string('default_question_rating', 'capquiz'));
        $form->setType('defaultquestionrating', PARAM_FLOAT);
        $form->setDefault('defaultquestionrating', (int)$capquiz->get('defaultquestionrating'));
        $form->addRule('defaultquestionrating', get_string('requiredelement', 'form'), 'required', null, 'client');
        $form->addRule('defaultquestionrating', $defaultratingnumericrule, 'numeric', null, 'client');

        $form->addElement('text', 'starstopass', get_string('stars_to_pass', 'capquiz'));
        $form->setType('starstopass', PARAM_INT);
        $form->setDefault('starstopass', $capquiz->get('starstopass'));
        $form->addRule('starstopass', get_string('stars_to_pass_required', 'capquiz'), 'required', null, 'client');

        $maxstars = $this->optional_param('maxstars', $capquiz->get_max_stars(), PARAM_INT);
        if (!empty($this->optional_param('addstar', '', PARAM_TEXT))) {
            $maxstars++;
        }
        $form->addElement('hidden', 'maxstars', $maxstars);
        $form->setType('maxstars', PARAM_INT);
        $form->setConstants(['maxstars' => $maxstars]);
        $previousrating = 0;
        $star = 1;
        for ($i = 0; $i < $maxstars; $i++) {
            $deleted = $this->optional_param("stargroup[$i][deletestar]", 0, PARAM_RAW);
            if ($deleted) {
                continue;
            }
            $starrating = (int)stars::get_required_rating_for_star($capquiz->get('starratings'), $i + 1);
            $starrating = $this->optional_param("stargroup[$i][rating]", $starrating, PARAM_INT);
            if ($previousrating >= $starrating) {
                $starrating = $previousrating + 100;
            }
            $previousrating = $starrating;
            $starelements = [
                $form->createElement('text', 'rating', '', ['size' => 10]),
                $form->createElement('hidden', 'star', $star),
            ];
            if ($maxstars > 1 && !$isnewinstance) {
                $starelements[] = $form->createElement('submit', 'deletestar', get_string('delete_star', 'capquiz'), [
                    'data-skip-validation' => 1,
                    'data-no-submit' => 1,
                    'onclick' => 'skipClientValidation = true;',
                ], false);
            }
            $form->addGroup($starelements, "stargroup[$i]", get_string('level_rating', 'capquiz', $star));
            $form->setType("stargroup[$i][star]", PARAM_INT);
            $form->setType("stargroup[$i][rating]", PARAM_INT);
            $form->setDefault("stargroup[$i][rating]", $starrating);
            $star++;
        }
        $form->addElement('static', 'starratingserror', '');
        if (!$isnewinstance) {
            $form->registerNoSubmitButton('addstar');
            $form->addElement('submit', 'addstar', get_string('add_star', 'capquiz'), [
                'data-skip-validation' => 1,
                'data-no-submit' => 1,
                'onclick' => 'skipClientValidation = true;',
            ], false);
        }

        // Question selection.
        $form->addElement('header', 'questionselectionheader', get_string('questionselection', 'capquiz'));

        $form->addElement('text', 'numquestioncandidates', get_string('number_of_questions_to_select', 'capquiz'));
        $form->setType('numquestioncandidates', PARAM_INT);
        $form->setDefault('numquestioncandidates', $capquiz->get('numquestioncandidates'));
        $numquestioncandidatesstr = get_string('number_of_questions_to_select_required', 'capquiz');
        $form->addRule('numquestioncandidates', $numquestioncandidatesstr, 'required', null, 'client');
        $form->addHelpButton('numquestioncandidates', 'number_of_questions_to_select', 'capquiz');

        $form->addElement('text', 'minquestionsuntilreappearance', get_string('prevent_question_n_times', 'capquiz'));
        $form->setType('minquestionsuntilreappearance', PARAM_INT);
        $form->setDefault('minquestionsuntilreappearance', $capquiz->get('minquestionsuntilreappearance'));
        $form->addRule('minquestionsuntilreappearance', get_string('requiredelement', 'form'), 'required', null, 'client');
        $form->addHelpButton('minquestionsuntilreappearance', 'prevent_question_n_times', 'capquiz');

        $form->addElement('text', 'userwinprobability', get_string('user_win_probability', 'capquiz'));
        $form->setType('userwinprobability', PARAM_FLOAT);
        $form->setDefault('userwinprobability', $capquiz->get('userwinprobability'));
        $form->addRule('userwinprobability', get_string('user_win_probability_required', 'capquiz'), 'required', null, 'client');
        $form->addHelpButton('userwinprobability', 'user_win_probability', 'capquiz');

        // Rating system.
        $form->addElement('header', 'ratingsystemheader', get_string('rating_system', 'capquiz'));

        $form->addElement('text', 'userkfactor', get_string('student_k_factor', 'capquiz'));
        $form->setType('userkfactor', PARAM_FLOAT);
        $form->addRule('userkfactor', get_string('student_k_factor_specified_rule', 'capquiz'), 'required', null, 'client');
        $form->addRule('userkfactor', get_string('k_factor_numeric_rule', 'capquiz'), 'numeric', null, 'client');
        $form->setDefault('userkfactor', $capquiz->get('userkfactor'));
        $form->addHelpButton('userkfactor', 'student_k_factor', 'capquiz');

        $form->addElement('text', 'questionkfactor', get_string('question_k_factor', 'capquiz'));
        $form->setType('questionkfactor', PARAM_FLOAT);
        $form->addRule('questionkfactor', get_string('question_k_factor_specified_rule', 'capquiz'), 'required', null, 'client');
        $form->addRule('questionkfactor', get_string('k_factor_numeric_rule', 'capquiz'), 'numeric', null, 'client');
        $form->setDefault('questionkfactor', $capquiz->get('questionkfactor'));
        $form->addHelpButton('questionkfactor', 'question_k_factor', 'capquiz');

        // Standard elements.
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Validate the data from the form.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if ($data['timeopen'] !== 0 && $data['timedue'] !== 0 && $data['timeopen'] > $data['timedue']) {
            $errors['timedue'] = get_string('closebeforeopen', 'quiz');
        }
        if (empty($data['defaultuserrating'])) {
            $errors['defaultuserrating'] = get_string('requiredelement', 'form');
        }
        if (empty($data['defaultquestionrating'])) {
            $errors['defaultquestionrating'] = get_string('requiredelement', 'form');
        }
        if (!isset($data['starstopass']) || $data['starstopass'] < 0) {
            $errors['starstopass'] = get_string('stars_to_pass_required', 'capquiz');
        } else if ($data['starstopass'] > $data['maxstars']) {
            $errors['starstopass'] = get_string('errorvalidatestarstopass', 'capquiz');
        }
        if (empty($data['userwinprobability'])) {
            $errors['userwinprobability'] = get_string('user_win_probability_required', 'capquiz');
        }
        if (empty($data['numquestioncandidates'])) {
            $errors['numquestioncandidates'] = get_string('number_of_questions_to_select_required', 'capquiz');
        }
        if (!isset($data['minquestionsuntilreappearance']) || $data['minquestionsuntilreappearance'] < 0) {
            $errors['minquestionsuntilreappearance'] = get_string('requiredelement', 'form');
        }
        if (!array_key_exists($data['questionbehaviour'], question_engine::get_archetypal_behaviours())) {
            $errors['questionbehaviour'] = get_string('error');
        } else if (in_array($data['questionbehaviour'], questions::get_unsupported_question_behaviours())) {
            $errors['questionbehaviour'] = get_string('error');
        }
        $previous = 0.0;
        foreach ($data['stargroup'] as $stargroup) {
            $previous = filter_var($stargroup['rating'], FILTER_VALIDATE_FLOAT, [
                'options' => ['min_range' => $previous + 1.0],
            ]);
            if (!$previous) {
                $errors['starratingserror'] = get_string('errorvalidatestarratings', 'capquiz');
            }
        }
        return $errors;
    }

    /**
     * Process data after default module form processing.
     *
     * @param stdClass $data
     * @return void
     */
    public function data_postprocessing($data): void {
        parent::data_postprocessing($data);

        // Process question display options.
        $visible = question_display_options::VISIBLE;
        $hidden = question_display_options::HIDDEN;
        $data->questiondisplayoptions = json_encode([
            'feedback' => isset($data->reviewfeedback) ? $visible : $hidden,
            'generalfeedback' => isset($data->reviewgeneralfeedback) ? $visible : $hidden,
            'rightanswer' => isset($data->reviewrightanswer) ? $visible : $hidden,
            'correctness' => isset($data->reviewcorrectness) ? $visible : $hidden,
        ]);

        // Process star ratings.
        $ratings = [];
        foreach ($data->stargroup as $stargroup) {
            $ratings[$stargroup['star'] - 1] = (int)$stargroup['rating'];
        }
        $data->starratings = implode(',', $ratings);
    }
}
