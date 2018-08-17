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

namespace mod_capquiz\bank;

defined('MOODLE_INTERNAL') || die();

class question_bank_view extends \core_question\bank\view {

    protected function wanted_columns() {
        $this->requiredcolumns = [
            new add_question_to_list_column($this),
//            new \core_question\bank\checkbox_column($this),
            new \core_question\bank\question_type_column($this),
            new \core_question\bank\question_name_column($this),
            new \core_question\bank\creator_name_column($this),
//            new \core_question\bank\delete_action_column($this),
            new \core_question\bank\preview_action_column($this)
        ];
        return $this->requiredcolumns;
    }

    public function render(string $tabname, int $page, int $perpage, string $category, bool $show_subcategories, bool $showhidden, bool $showquestiontext) {
        if ($this->process_actions_needing_ui()) {
            return '';
        }
        $contexts = $this->contexts->having_one_edit_tab_cap($tabname);
        $html = $this->render_display_category_form($contexts, $category);
        $form_html = $this->render_question_selection_form($contexts, $page, $perpage, $category, $show_subcategories, $showhidden, $showquestiontext);
        return $html . $form_html;
    }

    private function render_display_category_form(array $contexts, string $category) {
        ob_start();
        $this->display_category_form($contexts, $this->baseurl, $category);
        return ob_get_clean();
    }

    private function render_question_selection_form(array $contexts, int $page, int $perpage, string $category, bool $show_subcategories, bool $showhidden, bool $showquestiontext) {
        $this->add_searchcondition(new \core_question\bank\search\category_condition($category, false, $contexts, $this->baseurl, $this->cm));

        ob_start();
        $this->display_question_list(
            $contexts,
            $this->baseurl,
            $category,
            $this->cm,
            false,
            $page,
            $perpage,
            $showhidden,
            $showquestiontext,
            $this->contexts->having_cap('moodle/question:add')
        );
        return ob_get_clean();
    }

}
