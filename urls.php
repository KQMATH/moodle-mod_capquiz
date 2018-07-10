<?php

namespace mod_capquiz;

require_once($CFG->dirroot . '/mod/capquiz/actions.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/capquiz_question_attempt.php');

class capquiz_urls {

    public static $param_id = "id";
    public static $param_cmid = "cmid";
    public static $param_action = "action";
    public static $param_attempt = "attempt";
    public static $param_rating = "rating";
    public static $param_question_id = 'question-id';
    public static $param_question_list_id = "question-list-id";

    public static $url_view = "/mod/capquiz/view.php";
    public static $url_async = "/mod/capquiz/async.php";
    public static $url_action = "/mod/capquiz/action.php";
    public static $url_create_question_list = '/mod/capquiz/create_question_list.php';

    public static function create_add_question_to_list_url(int $question_id) {
        $url = new \moodle_url(capquiz_urls::$url_action);
        $url->param(capquiz_actions::$param_action, capquiz_actions::$action_add_question_to_list);
        $url->param(capquiz_urls::$param_cmid, required_param(capquiz_urls::$param_cmid, PARAM_INT));
        $url->param(capquiz_urls::$param_question_id, $question_id);
        return $url;
    }

    public static function create_question_list_publish_url(capquiz $capquiz, capquiz_question_list $question_list) {
        $url = new \moodle_url(capquiz_urls::$url_action);
        $url->param(capquiz_urls::$param_cmid, $capquiz->course_module_id());
        $url->param(capquiz_urls::$param_question_list_id, $question_list->id());
        $url->param(capquiz_actions::$param_action, capquiz_actions::$action_publish_question_list);

        return $url;
    }

    public static function create_question_list_select_url(capquiz $capquiz, capquiz_question_list $question_list) {
        $url = new \moodle_url(capquiz_urls::$url_action);
        $url->param(capquiz_actions::$param_action, capquiz_actions::$action_set_question_list);
        $url->param(capquiz_urls::$param_cmid, $capquiz->course_module_id());
        $url->param(capquiz_urls::$param_question_list_id, $question_list->id());
        return $url;
    }

    public static function create_set_question_rating_url(capquiz $capquiz, int $question_id) {
        $url = new \moodle_url(capquiz_urls::$url_action);
        $url->param(capquiz_urls::$param_cmid, $capquiz->course_module_id());
        $url->param(capquiz_urls::$param_action, capquiz_actions::$action_set_question_rating);
        $url->param(capquiz_urls::$param_question_id, $question_id);
        $raw = $url->out_as_local_url();
        return $raw;
    }

    public static function create_response_submit_url(capquiz $capquiz, capquiz_question_attempt $attempt) {
        $url = new \moodle_url(capquiz_urls::$url_async);
        $url->param(capquiz_urls::$param_cmid, $capquiz->course_module_id());
        $url->param(capquiz_urls::$param_attempt, $attempt->id());
        $url->param(capquiz_urls::$param_action, capquiz_actions::$action_attempt_answered);
        return $url;
    }

    public static function create_response_reviewed_url(capquiz $capquiz, capquiz_question_attempt $attempt) {
        $url = new \moodle_url(capquiz_urls::$url_async);
        $url->param(capquiz_urls::$param_cmid, $capquiz->course_module_id());
        $url->param(capquiz_urls::$param_attempt, $attempt->id());
        $url->param(capquiz_urls::$param_action, capquiz_actions::$action_attempt_reviewed);
        return $url;
    }

}