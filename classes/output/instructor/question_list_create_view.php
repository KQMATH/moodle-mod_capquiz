<?php

namespace mod_capquiz;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/capquiz/classes/form/view/create_question_set_form.php');

class question_list_create_view {

    private $capquiz;

    public function __construct(capquiz $capquiz) {
        $this->capquiz = $capquiz;
    }

    public function show() {
        global $PAGE;
        echo $this->capquiz->renderer()->header();
        echo '<h3>' . get_string('create_question_list', 'capquiz') . '</h3>';
        $url = $PAGE->url;
        $form = new create_question_set_form($url);

        if ($form_data = $form->get_data()) {
            $registry = $this->capquiz->question_registry();
            if ($registry->create_question_list($form_data->title, $form_data->description)) {
                $url = new \moodle_url(capquiz_urls::$url_view);
                $url->param(capquiz_urls::$param_id, $this->capquiz->course_module_id());
                redirect($url);
            } else {
                redirect_to_front_page();
            }
        } else {
            echo $form->display();
        }
        echo $this->capquiz->renderer()->footer();
    }

}