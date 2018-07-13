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

namespace mod_capquiz;

class capquiz_urls {

    public static $param_id = 'id';
    public static $param_cmid = 'cmid';
    public static $param_rating = 'rating';
    public static $param_attempt = 'attempt';
    public static $param_question_id = 'question-id';
    public static $param_question_list_id = 'question-list-id';

    public static $url_view = '/mod/capquiz/view.php';
    public static $url_async = '/mod/capquiz/async.php';
    public static $url_action = '/mod/capquiz/action.php';

    public static function add_question_to_list_url(int $question_id) {
        $url = new \moodle_url(capquiz_urls::$url_action);
        $url->param(capquiz_actions::$parameter, capquiz_actions::$add_question_to_list);
        $url->param(capquiz_urls::$param_cmid, required_param(capquiz_urls::$param_cmid, PARAM_INT));
        $url->param(capquiz_urls::$param_question_id, $question_id);
        return $url;
    }

    public static function question_list_publish_url(capquiz $capquiz, capquiz_question_list $question_list) {
        $url = new \moodle_url(capquiz_urls::$url_action);
        $url->param(capquiz_urls::$param_cmid, $capquiz->course_module_id());
        $url->param(capquiz_urls::$param_question_list_id, $question_list->id());
        $url->param(capquiz_actions::$parameter, capquiz_actions::$publish_question_list);

        return $url;
    }

    public static function question_list_select_url(capquiz $capquiz, capquiz_question_list $question_list) {
        $url = new \moodle_url(capquiz_urls::$url_action);
        $url->param(capquiz_actions::$parameter, capquiz_actions::$set_question_list);
        $url->param(capquiz_urls::$param_cmid, $capquiz->course_module_id());
        $url->param(capquiz_urls::$param_question_list_id, $question_list->id());
        return $url;
    }

    public static function set_question_rating_url(capquiz $capquiz, int $question_id) {
        $url = new \moodle_url(capquiz_urls::$url_action);
        $url->param(capquiz_urls::$param_cmid, $capquiz->course_module_id());
        $url->param(capquiz_urls::$param_question_id, $question_id);
        $url->param(capquiz_actions::$parameter, capquiz_actions::$set_question_rating);
        return $url;
    }

    public static function response_submit_url(capquiz $capquiz, capquiz_question_attempt $attempt) {
        $url = new \moodle_url(capquiz_urls::$url_async);
        $url->param(capquiz_urls::$param_cmid, $capquiz->course_module_id());
        $url->param(capquiz_urls::$param_attempt, $attempt->id());
        $url->param(capquiz_actions::$parameter, capquiz_actions::$attempt_answered);
        return $url;
    }

    public static function response_reviewed_url(capquiz $capquiz, capquiz_question_attempt $attempt) {
        $url = new \moodle_url(capquiz_urls::$url_async);
        $url->param(capquiz_urls::$param_cmid, $capquiz->course_module_id());
        $url->param(capquiz_urls::$param_attempt, $attempt->id());
        $url->param(capquiz_actions::$parameter, capquiz_actions::$attempt_reviewed);
        return $url;
    }

}