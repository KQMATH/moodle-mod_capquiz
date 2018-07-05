<?php

namespace mod_capquiz;

defined('MOODLE_INTERNAL') || die();

require_once('../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/instructor/question_bank_widget.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/instructor/question_list_widget.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/instructor/enrolled_users_widget.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/output/instructor/question_list_selection_widget.php');

class instructor_view {

    private $renderer;
    private $capquiz;

    public function __construct(capquiz $capquiz, \core_renderer $renderer) {
        $this->renderer = $renderer;
        $this->capquiz = $capquiz;
    }

    public function show() {
        echo $this->renderer->header();
        if ($this->capquiz->has_question_list()) {
            $this->show_question_list();
        } else {
            $this->show_question_set_selection();
        }
        $this->show_user_list();
        echo $this->renderer->footer();
    }

    private function show_question_set_selection() {
        $set_view = new question_list_selection_widget($this->capquiz, $this->renderer);
        $set_view->show();
    }

    private function show_question_list() {
        echo '<h2>Question list</h2>';
        $this->show_question_list_view();
        if (!$this->capquiz->is_published()) {
            $this->show_publish_button();
            $this->show_question_bank_view();
        }
    }

    private function show_question_list_view() {
        $question_view = new question_list_widget($this->capquiz);
        $question_view->show();
    }

    private function show_publish_button() {
        $url = capquiz_urls::create_question_list_publish_url($this->capquiz, $this->capquiz->question_list());
        echo \html_writer::div($this->renderer->single_button($url, get_string('publish', 'capquiz')));
    }

    private function show_question_bank_view() {
        if (isset($_GET[capquiz_urls::$param_id])) {
            $_GET['cmid'] = $_GET['id'];
        }
        list($url, $contexts, $cmid, $cm, $capquiz_module_db_record, $pagevars) = question_edit_setup('editq', '/mod/capquiz/view.php', false);

        $questionsperpage = optional_param('qperpage', 10, PARAM_INT);
        $questionpage = optional_param('qpage', 0, PARAM_INT);
        $question_view = new question_bank_widget($contexts, $url, $this->capquiz->context(), $this->capquiz->course_module());
        $question_view->show('editq', $questionpage, $questionsperpage, $pagevars['cat'], true, true, true);
    }

    private function show_user_list() {
        $widget = new enrolled_users_widget($this->capquiz);
        $widget->show();
    }

}
