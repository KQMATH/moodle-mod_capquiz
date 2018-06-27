<?php

namespace mod_capquiz;

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/capquiz/urls.php');

function redirect_to_plugin_index(capquiz $capquiz)
{
    $target_url = new \moodle_url(capquiz_urls::$url_view);
    $target_url->param(capquiz_urls::$param_id, $capquiz->course_module_id());
    redirect($target_url);
}

function question_attempt_async(capquiz $capquiz, string $action, int $attemptid)
{
    $user = $capquiz->user();
    $attempt = capquiz_question_attempt::load_attempt($capquiz, $user, $attemptid);
    if ($action === capquiz_actions::$action_attempt_answered) {
        $capquiz->question_engine()->attempt_answered($user, $attempt);
    } else if ($action === capquiz_actions::$action_attempt_reviewed) {
        $capquiz->question_engine()->attempt_reviewed($user, $attempt);
    }
    redirect_to_plugin_index($capquiz);
}

function capquiz_async()
{
    $cmid = required_param(capquiz_urls::$param_cmid, PARAM_INT);
    $action = required_param(capquiz_actions::$param_action, PARAM_TEXT);
    $attemptid = optional_param(capquiz_urls::$param_attempt, null, PARAM_INT);
    $capquiz = new capquiz($cmid);
    if ($attemptid !== null)
        question_attempt_async($capquiz, $action, $attemptid);
    redirect_to_front_page();
}

capquiz_async();