<?php

namespace mod_capquiz;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class create_question_set_form extends \moodleform {

    public function definition() {
        $form = $this->_form;
        $form->addElement('text', 'title', get_string('title', 'capquiz'));
        $form->setType('title', PARAM_TEXT);
        $form->addRule('title', get_string('title_required', 'capquiz'), 'required', null, 'client');

        $form->addElement('text', 'description', get_string('description', 'capquiz'));
        $form->setType('description', PARAM_TEXT);
        $form->addRule('description', get_string('description_required', 'capquiz'), 'required', null, 'client');

        $form->addElement('submit', 'submitbutton', get_string('create_question_list', 'capquiz'));
    }

    public function validations($data, $files) {
        $validation_errors = [];
        if (empty($data['title'])) {
            $validation_errors['title'] = get_string('title_required', 'capquiz');
        }
        if (empty($data['description'])) {
            $validation_errors['description'] = get_string('description_required', 'capquiz');
        }
        return $validation_errors;
    }

}
