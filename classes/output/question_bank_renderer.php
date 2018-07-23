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

class question_bank_renderer {
    private $capquiz;
    private $renderer;

    public function __construct(capquiz $capquiz, renderer $renderer) {
        $this->capquiz = $capquiz;
        $this->renderer = $renderer;
    }

    public function render() {
        global $PAGE;
        if (isset($_GET[capquiz_urls::$param_id])) {
            $_GET['cmid'] = $_GET['id'];
        }
        list($url, $contexts, $cmid, $cm, $capquizrecord, $pagevars) = question_edit_setup('editq', $PAGE->url, false);
        $questionsperpage = optional_param('qperpage', 10, PARAM_INT);
        $questionpage = optional_param('qpage', 0, PARAM_INT);
        $questionview = new question_bank_view($contexts, capquiz_urls::view_question_list_url(), $this->capquiz->context(), $this->capquiz->course_module());
        return $questionview->render('editq', $questionpage, $questionsperpage, $pagevars['cat'], true, true, true);
    }
}
