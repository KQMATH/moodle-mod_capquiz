<?php

namespace mod_capquiz;

require_once($CFG->dirroot . '/mod/capquiz/classes/capquiz_question_attempt.php');

defined('MOODLE_INTERNAL') || die();

abstract class capquiz_rating_system
{
    public abstract function user_loss_rating(capquiz_user $user, capquiz_question $question);

    public abstract function user_victory_rating(capquiz_user $user, capquiz_question $question);

    //Must return an array with modified ratings such as: array(first_rating, second_rating)
    public abstract function question_draw_ratings(capquiz_question $first, capquiz_question $second);

    //Must return an array with modified ratings such as: array(winner_rating, loser_rating)
    public abstract function question_victory_ratings(capquiz_question $winner, capquiz_question $loser);
}
