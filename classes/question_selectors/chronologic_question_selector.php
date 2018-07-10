<?php

namespace mod_capquiz;

require_once($CFG->dirroot . '/mod/capquiz/classes/capquiz_question_selector.php');

defined('MOODLE_INTERNAL') || die();

class chronologic_question_selector extends capquiz_question_selector {

    public function next_question_for_user(capquiz_user $user, capquiz_question_list $question_list, array $inactive_capquiz_attempts) {
        $is_answered = function (capquiz_question $q) use ($inactive_capquiz_attempts) {
            foreach ($inactive_capquiz_attempts as $attempt) {
                if ($attempt->question_id() === $q->id()) {
                    return true;
                }
            }
            return false;
        };
        foreach ($question_list->questions() as $question) {
            if (!$is_answered($question)) {
                return $question;
            }
        }
        return null;
    }

}
