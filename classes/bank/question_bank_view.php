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
 * This file defines a class represeting a question bank view
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz\bank;

use \core_question\local\bank\checkbox_column;
use \qbank_viewcreator\creator_name_column;
use \qbank_editquestion\edit_action_column;
use \qbank_deletequestion\delete_action_column;
use \qbank_previewquestion\preview_action_column;
use \qbank_viewquestionname\viewquestionname_column_helper;
use \qbank_viewquestiontype\question_type_column;
use \core_question\bank\search\tag_condition as tag_condition;
use \core_question\bank\search\hidden_condition as hidden_condition;
use \core_question\bank\search\category_condition;
use mod_capquiz\local\capquiz_urls;
use mod_capquiz\bank\add_action_column;

defined('MOODLE_INTERNAL') || die();

/**
 * Class question_bank_view
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_bank_view extends \core_question\local\bank\view {

    /**
     * URL of add to quiz.
     *
     * @param $questionid
     * @return \moodle_url
     */
    public function add_to_quiz_url($questionid) {
        return \mod_capquiz\capquiz_urls::add_question_to_list_url( $questionid ) ;
    }
	
    protected function get_question_bank_plugins(): array {
        $questionbankclasscolumns = [];
        $corequestionbankcolumns = [
            'add_action_column',
            'checkbox_column',
            'question_type_column',
            'question_name_text_column',
            'preview_action_column',
            'edit_action_column'
        ];

        if (question_get_display_preference('qbshowtext', 0, PARAM_BOOL, new \moodle_url(''))) {
            $corequestionbankcolumns[] = 'question_text_row';
        }

        foreach ($corequestionbankcolumns as $fullname) {
            $shortname = $fullname;
            if (class_exists('mod_capquiz\\bank\\' . $fullname)) {
                $fullname = 'mod_capquiz\\bank\\' . $fullname;
                $questionbankclasscolumns[$shortname] = new $fullname($this);
            } else if (class_exists('core_question\\local\\bank\\' . $fullname)) {
                $fullname = 'core_question\\local\\bank\\' . $fullname;
                $questionbankclasscolumns[$shortname] = new $fullname($this);
            } else {
                $questionbankclasscolumns[$shortname] = '';
            }
        }
        $plugins = \core_component::get_plugin_list_with_class('qbank', 'plugin_feature', 'plugin_feature.php');
        foreach ($plugins as $componentname => $plugin) {
            $pluginentrypointobject = new $plugin();
            $plugincolumnobjects = $pluginentrypointobject->get_question_columns($this);
            // Don't need the plugins without column objects.
            if (empty($plugincolumnobjects)) {
                unset($plugins[$componentname]);
                continue;
            }
            foreach ($plugincolumnobjects as $columnobject) {
                $columnname = $columnobject->get_column_name();
                foreach ($corequestionbankcolumns as $key => $corequestionbankcolumn) {
                    if (!\core\plugininfo\qbank::is_plugin_enabled($componentname)) {
                        unset($questionbankclasscolumns[$columnname]);
                        continue;
                    }
                    // Check if it has custom preference selector to view/hide.
                    if ($columnobject->has_preference() && !$columnobject->get_preference()) {
                        continue;
                    }
                    if ($corequestionbankcolumn === $columnname) {
                        $questionbankclasscolumns[$columnname] = $columnobject;
                    }
                }
            }
        }

        // Mitigate the error in case of any regression.
        foreach ($questionbankclasscolumns as $shortname => $questionbankclasscolumn) {
            if (empty($questionbankclasscolumn)) {
                unset($questionbankclasscolumns[$shortname]);
            }
        }

        return $questionbankclasscolumns;
    }

    protected function heading_column(): string {
        return 'mod_capquiz\\bank\\question_name_text_column';
    }

    /**
     * Renders the html question bank (same as display, but returns the result).
     *
     * Note that you can only output this rendered result once per page, as
     * it contains IDs which must be unique.
     *
     * @param array $pagevars
     * @param string $tabname
     * @return string HTML code for the form
     */
    public function render($pagevars, $tabname): string {
        ob_start();
        $this->display($pagevars, $tabname);
        $out = ob_get_contents();
        ob_end_clean();
        return $out;
    }

    /**
     * Displays the add selected questions button
     *
     * @throws \coding_exception
     */
    private function display_add_selected_questions_button() {
        $straddtoquiz = get_string('add_to_quiz', 'capquiz');
        echo '<button class="btn btn-secondary capquiz-add-selected-questions">' . $straddtoquiz . '</button>';
    }

}
