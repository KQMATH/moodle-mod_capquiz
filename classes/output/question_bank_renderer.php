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

namespace mod_capquiz\output;

use mod_capquiz\capquiz;
use mod_capquiz\capquiz_urls;
use mod_capquiz\bank\question_bank_view;

defined('MOODLE_INTERNAL') || die();

require_once('../../config.php');

/**
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_bank_renderer {
    private $capquiz;
    private $renderer;
    private $pagevars;

    public function __construct(capquiz $capquiz, renderer $renderer) {
        $this->capquiz = $capquiz;
        $this->renderer = $renderer;
    }

    public function create_view() {
        $this->set_missing_id_param();
        $baseurl = '/mod/capquiz/edit.php';
        list($url, $contexts, $cmid, $cm, $capquizrecord, $pagevars) = question_edit_setup('editq', $baseurl, true);
        $this->pagevars = $pagevars;
        return new question_bank_view($contexts, $url, $this->capquiz->course(), $this->capquiz->course_module());
    }

    public function render() {
        $questionsperpage = optional_param('qperpage', 10, PARAM_INT);
        $questionpage = optional_param('qpage', 0, PARAM_INT);
        $questionview = $this->create_view();
        $html = "<h3>" . get_string('available_questions', 'capquiz') . "</h3>";
        $html .= $questionview->render('editq',
            $questionpage,
            $questionsperpage,
            $this->pagevars['cat'],
            true,
            true,
            true);
        return $html;
    }

    /**
     * Solves apparent inconsistency in question_edit_setup()
     */
    private function set_missing_id_param() {
        if (isset($_GET[capquiz_urls::$param_id])) {
            $_GET[capquiz_urls::$param_course_module_id] = $_GET[capquiz_urls::$param_id];
        }
        if (isset($_POST[capquiz_urls::$param_id])) {
            $_POST[capquiz_urls::$param_course_module_id] = $_POST[capquiz_urls::$param_id];
        }
    }
}
