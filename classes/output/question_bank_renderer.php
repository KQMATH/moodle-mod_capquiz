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
 * This file defines a class used to render a question bank
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz\output;

use mod_capquiz\capquiz;
use mod_capquiz\capquiz_urls;
use mod_capquiz\bank\question_bank_view;
use moodle_page;

/**
 * Class question_bank_renderer
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_bank_renderer {

    /** @var capquiz $capquiz */
    private capquiz $capquiz;

    /** @var moodle_page $page */
    private moodle_page $page;

    /**
     * question_bank_renderer constructor.
     * @param capquiz $capquiz
     */
    public function __construct(capquiz $capquiz) {
        $this->capquiz = $capquiz;
        $this->page = $capquiz->get_page();
    }

    /**
     * Creates question bank view
     */
    public function create_view(): question_bank_view {
        list($url, $contexts, $cmid, $cm, $capquizrecord, $pagevars) = $this->setup_question_edit();
        return new question_bank_view($contexts, $url, $this->capquiz->course(), $this->capquiz->course_module(), $pagevars);
    }

    /**
     * Renders question bank
     */
    public function render(): string {
        // phpcs:disable
        // $questionsperpage = optional_param('qperpage', 10, PARAM_INT);
        // $questionpage = optional_param('qpage', 0, PARAM_INT);
        // phpcs:enable
        $questionview = $this->create_view();
        // phpcs:disable
        // $html = "<h3>" . get_string('available_questions', 'capquiz') . "</h3>";
        // phpcs:enable
        ob_start();
        $questionview->display();
        $qbank = ob_get_clean();
        return \html_writer::div(\html_writer::div($qbank, 'bd'), 'questionbankformforpopup');
    }

    /**
     * This is mostly a copy from editlib.php's question_edit_setup() function.
     * The original function expects the course module id parameter to be "cmid", but this module gets passed "id"
     * Moodle coding standard does not allow us to override $_GET or $_POST before calling question_edit_setup()
     */
    private function setup_question_edit(): array {
        $params = [];
        $params['cmid'] = capquiz_urls::require_course_module_id_param();
        $params['qpage'] = optional_param('qpage', null, PARAM_INT);
        $params['cat'] = optional_param('cat', null, PARAM_SEQUENCE);
        $params['category'] = optional_param('category', null, PARAM_SEQUENCE);
        $params['qperpage'] = optional_param('qperpage', null, PARAM_INT);
        for ($i = 1; $i <= \core_question\local\bank\view::MAX_SORTS; $i++) {
            $param = 'qbs' . $i;
            if ($sort = optional_param($param, '', PARAM_TEXT)) {
                $params[$param] = $sort;
            } else {
                break;
            }
        }
        $params['recurse'] = optional_param('recurse', null, PARAM_BOOL);
        $params['showhidden'] = optional_param('showhidden', null, PARAM_BOOL);
        $params['qbshowtext'] = optional_param('qbshowtext', null, PARAM_BOOL);
        $params['cpage'] = optional_param('cpage', null, PARAM_INT);
        $params['qtagids'] = optional_param_array('qtagids', null, PARAM_INT);
        $this->page->set_pagelayout('admin');
        $edittab = 'editq';
        return question_build_edit_resources($edittab, capquiz_urls::$urledit, $params);
    }

}
