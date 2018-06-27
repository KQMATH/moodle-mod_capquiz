<?php

namespace mod_capquiz;

require_once($CFG->dirroot . '/mod/capquiz/classes/capquiz_question_attempt.php');

defined('MOODLE_INTERNAL') || die();

abstract class capquiz_question_selector
{
    public abstract function next_question_for_user(capquiz_user $user, capquiz_question_list $question_list, array $inactive_capquiz_attempts);
}
