<?php

namespace mod_capquiz;

defined('MOODLE_INTERNAL') || die();

class capquiz_add_question_to_list_column extends \core_question\bank\action_column_base {

    public function get_name() {
        return 'question_include';
    }

    public function get_required_fields() {
        return ['q.id'];
    }

    protected function display_content($question, $css_row_classes) {
        $this->print_icon($this->icon_id(), $this->icon_hover_text(), $this->icon_action_url($question));
    }

    private function icon_id() {
        return 't/add';
    }

    private function icon_hover_text() {
        return get_string('add_the_quiz_question', 'capquiz');
    }

    private function icon_action_url(\stdClass $question) {
        return capquiz_urls::create_add_question_to_list_url($question->id);
    }

}

class question_bank_widget extends \core_question\bank\view {

    protected function wanted_columns() {
        $this->requiredcolumns = [
            new capquiz_add_question_to_list_column($this),
            new \core_question\bank\checkbox_column($this),
            new \core_question\bank\question_type_column($this),
            new \core_question\bank\question_name_column($this),
            new \core_question\bank\preview_action_column($this)
        ];
        return $this->requiredcolumns;
    }

    public function show(string $tabname, int $page, int $perpage, string $category, bool $show_subcategories, bool $showhidden, bool $showquestiontext) {
        if ($this->process_actions_needing_ui()) {
            return;
        }
        $this->display_question_list(
            $this->contexts->having_one_edit_tab_cap($tabname),
            $this->baseurl,
            $category,
            $this->cm,
            null,
            $page,
            $perpage,
            $showhidden,
            $showquestiontext,
            $this->contexts->having_cap('moodle/question:add')
        );
    }

}
