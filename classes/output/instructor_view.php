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
use mod_capquiz\capquiz_user;
use mod_capquiz\capquiz_question_registry;

use mod_capquiz\bank\question_bank_view;

use mod_capquiz\form\view\create_question_set_form;

defined('MOODLE_INTERNAL') || die();

require_once('../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');

class instructor_view {

    private $capquiz;
    private $renderer;

    public function __construct(capquiz $capquiz, renderer $renderer) {
        $this->capquiz = $capquiz;
        $this->renderer = $renderer;
    }

    public function render() {
        // TODO: Move somewhere else. Currently here for testing purposes, and to show usage.
        $badge = new \mod_capquiz\capquiz_badge($this->capquiz->course_module()->course, $this->capquiz->id());
        $badge->create_badges();


        if ($this->capquiz->has_question_list()) {
            $html = $this->render_question_set();
        } else {
            $html = $this->render_create_question_list();
        }
        $html .= $this->render_enrolled_students();
        return $html;
    }

    private function render_enrolled_students() {
        $users = capquiz_user::list_users($this->capquiz);
        $rows = [];
        for ($i = 0; $i < count($users); $i++) {
            $user = $users[$i];
            $rows[] = [
                'num' => $i + 1,
                'student' => $user->id(),
                'rating' => $user->rating()
            ];
        }
        return $this->renderer->render_from_template('capquiz/enrolled_students', [
            'users' => $rows
        ]);
    }

    public function render_create_question_list() {
        global $PAGE;
        $url = $PAGE->url;
        $form = new create_question_set_form($url);
        $formdata = $form->get_data();
        if ($formdata) {
            $registry = $this->capquiz->question_registry();
            $ratings = [
                $formdata->level_1_rating,
                $formdata->level_2_rating,
                $formdata->level_3_rating,
                $formdata->level_4_rating,
                $formdata->level_5_rating,
            ];
            $success = $registry->create_question_list($formdata->title, $formdata->description, $ratings);
            if ($success) {
                $url = new \moodle_url(capquiz_urls::$url_view);
                $url->param(capquiz_urls::$param_id, $this->capquiz->course_module_id());
                redirect($url);
            }
            header('Location: /');
            exit;
        }
        return $this->renderer->render_from_template('capquiz/create_question_set', [
            'form' => $form->render()
        ]);
    }

    private function render_question_list_selection() {
        $question_registry = new capquiz_question_registry($this->capquiz);
        $question_lists = $question_registry->question_lists();
        $sets = [];
        foreach ($question_lists as $list) {
            $sets[] = [
                'url' => capquiz_urls::question_list_select_url($this->capquiz, $list),
                'title' => $list->title(),
                'description' => $list->description()
            ];
        }
        return $this->renderer->render_from_template('capquiz/question_set_list', [
            'sets' => $sets,
            'create' => [
                'primary' => true,
                'method' => 'post',
                'url' => new \moodle_url('/mod/capquiz/view.php'),
                'params' => [
                    [
                        'name' => capquiz_urls::$param_id,
                        'value' => $this->capquiz->course_module_id()
                    ]
                ],
                'label' => get_string('create_question_list', 'capquiz')
            ]
        ]);
    }

    private function render_question_set() {
        $question_list = $this->capquiz->question_list();
        $rows = [];
        $questions = $question_list->questions();
        for ($i = 0; $i < $question_list->question_count(); $i++) {
            $question = $questions[$i];
            $rows[] = [
                'published' => $this->capquiz->is_published(),
                'index' => $i + 1,
                'name' => $question->name(),
                'rating' => $question->rating(),
                'url' => capquiz_urls::set_question_rating_url($this->capquiz, $question->id())->out_as_local_url(false)
            ];
        }
        $publish = false;
        if (!$this->capquiz->is_published()) {
            $publish = [
                'primary' => true,
                'method' => 'post',
                'url' => capquiz_urls::question_list_publish_url($this->capquiz, $this->capquiz->question_list())->out_as_local_url(false),
                'label' => get_string('publish', 'capquiz')
            ];
        }
        $html = $this->renderer->render_from_template('capquiz/question_set', [
            'publish' => $publish,
            'questions' => $rows
        ]);
        if ($publish) {
            $html .= '<hr>';
            $html .= $this->render_question_bank_view();
        }
        return $html;
    }

    private function render_question_bank_view() {
        if (isset($_GET[capquiz_urls::$param_id])) {
            $_GET['cmid'] = $_GET['id'];
        }
        list(
            $url,
            $contexts,
            $cmid,
            $cm,
            $capquizrecord,
            $pagevars) = question_edit_setup('editq', '/mod/capquiz/view.php', false);
        $questionsperpage = optional_param('qperpage', 10, PARAM_INT);
        $questionpage = optional_param('qpage', 0, PARAM_INT);
        $questionview = new question_bank_view($contexts, $url, $this->capquiz->context(), $this->capquiz->course_module());
        return $questionview->render('editq', $questionpage, $questionsperpage, $pagevars['cat'], true, true, true);
    }

}
