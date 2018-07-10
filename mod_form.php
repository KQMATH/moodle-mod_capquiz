<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

class mod_capquiz_mod_form extends moodleform_mod {

    function definition() {
        $form = $this->_form;
        $form->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $form->setType('name', PARAM_TEXT);
        $form->addRule('name', null, 'required', null, 'client');
        $form->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $form->addElement('text', 'default_user_rating', get_string('default_user_rating', 'capquiz'));
        $form->setType('default_user_rating', PARAM_INT);
        $form->addRule('default_user_rating', get_string('default_rating_specified_rule', 'capquiz'), 'required', null, 'client');
        $form->addRule('default_user_rating', get_string('default_rating_numeric_rule', 'capquiz'), 'numeric', null, 'client');
        $form->setDefault('default_user_rating', 1200);

        $form->addElement('text', 'default_user_k_factor', get_string('default_user_k_factor', 'capquiz'));
        $form->setType('default_user_k_factor', PARAM_INT);
        $form->addRule('default_user_k_factor', get_string('default_k_factor_specified_rule', 'capquiz'), 'required', null, 'client');
        $form->addRule('default_user_k_factor', get_string('default_k_factor_numeric_rule', 'capquiz'), 'numeric', null, 'client');
        $form->setDefault('default_user_k_factor', 32);

        $form->addElement('text', 'default_question_k_factor', get_string('default_question_k_factor', 'capquiz'));
        $form->setType('default_question_k_factor', PARAM_INT);
        $form->addRule('default_question_k_factor', get_string('default_k_factor_specified_rule', 'capquiz'), 'required', null, 'client');
        $form->addRule('default_question_k_factor', get_string('default_k_factor_numeric_rule', 'capquiz'), 'numeric', null, 'client');
        $form->setDefault('default_question_k_factor', 8);

        $this->standard_intro_elements(get_string('description'));
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

}