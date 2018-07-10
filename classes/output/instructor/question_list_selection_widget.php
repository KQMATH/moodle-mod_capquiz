<?php

namespace mod_capquiz;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/capquiz/urls.php');
require_once($CFG->dirroot . '/mod/capquiz/actions.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/widget/list_widget.php');

class question_lists_model extends list_model {

    private $capquiz;
    private $question_lists;

    public function __construct(capquiz $capquiz, array $question_lists) {
        $this->capquiz = $capquiz;
        $this->question_lists = $question_lists;
    }

    public function row_count() {
        $cnt = count($this->question_lists);
        return $cnt;
    }

    public function row(int $index) {
        $question_list = $this->question_lists[$index];
        $html = \html_writer::start_div("capquiz-question-list-item");
        $html .= \html_writer::start_div();
        $html .= $this->question_list_title_html($question_list);
        $html .= $this->question_list_description_html($question_list);
        $html .= \html_writer::end_div();
        $html .= $this->question_list_select_action_html($question_list);
        $html .= \html_writer::end_div();
        return $html;
    }

    private function question_list_title_html(capquiz_question_list $question_list) {
        $html = \html_writer::start_div();
        $html .= '<h4>' . $question_list->title() . '</h4>';
        $html .= \html_writer::end_div();
        return $html;
    }

    private function question_list_description_html(capquiz_question_list $question_list) {
        $html = \html_writer::start_div();
        $html .= '<p>' . $question_list->description() . '</p>';
        $html .= \html_writer::end_div();
        return $html;
    }

    private function question_list_select_action_html(capquiz_question_list $question_list) {
        $html = \html_writer::start_div("capquiz-question-list-item-title-action");
        $html .= $this->capquiz->output()->action_icon(capquiz_urls::create_question_list_select_url($this->capquiz, $question_list), new \pix_icon("t/check", get_string('select', 'capquiz')));
        $html .= \html_writer::end_div();
        return $html;
    }

}

class question_list_selection_widget {

    private $capquiz;
    private $renderer;
    private $question_registry;

    public function __construct(capquiz $capquiz, \core_renderer $renderer) {
        $this->capquiz = $capquiz;
        $this->renderer = $renderer;
        $this->question_registry = new capquiz_question_registry($capquiz);
    }

    public function show() {
        echo "<h2>" . get_string("question_lists", "capquiz") . "</h2>";
        if (empty($this->question_registry->has_question_lists())) {
            echo "No question sets have been created";
        } else {
            $this->show_question_lists();
        }
        $this->show_create_question_list_button();
    }

    private function show_question_lists() {
        $list_view = new list_widget(new question_lists_model($this->capquiz, $this->question_registry->question_lists()));
        $list_view->show();
    }

    private function show_create_question_list_button() {
        $url = new \moodle_url("/mod/capquiz/create_question_list.php");
        $id = $this->capquiz->course_module_id();
        $url->param(capquiz_urls::$param_id, $id);
        echo \html_writer::div($this->renderer->single_button($url, get_string('create_question_list', 'capquiz')));
    }

}
