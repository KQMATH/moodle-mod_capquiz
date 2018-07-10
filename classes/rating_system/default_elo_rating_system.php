<?php

namespace mod_capquiz;

require_once($CFG->dirroot . '/mod/capquiz/classes/capquiz_rating_system.php');

defined('MOODLE_INTERNAL') || die();

class default_elo_rating_system extends capquiz_rating_system {

    private $student_k_factor;

    public function __construct(float $student_k_factor) {
        $this->student_k_factor = $student_k_factor;
    }

    public function user_loss_rating(capquiz_user $user, capquiz_question $question) {
        return $user->rating() + $this->student_k_factor * (0 - $this->expected_result($user->rating(), $question->rating()));
    }

    public function user_victory_rating(capquiz_user $user, capquiz_question $question) {
        return $user->rating() + $this->student_k_factor * (1 - $this->expected_result($user->rating(), $question->rating()));
    }

    //Must return an array with modified ratings such as: array(first_rating, second_rating)
    public function question_draw_ratings(capquiz_question $first, capquiz_question $second) {
        return [$first->rating(), $second->rating()];
    }

    //Must return an array with modified ratings such as: array(winner_rating, loser_rating)
    public function question_victory_ratings(capquiz_question $winner, capquiz_question $loser) {
        return [$winner->rating(), $loser->rating()];
    }

    private function expected_result(float $rating_a, float $rating_b) {
        $exponent = ($rating_b - $rating_a) / 400.0;
        return 1.0 / (1.0 + pow(10.0, $exponent));
    }

}
