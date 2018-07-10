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

require_once($CFG->dirroot . '/mod/capquiz/classes/capquiz_question_attempt.php');

defined('MOODLE_INTERNAL') || die();

abstract class capquiz_rating_system {

    public abstract function user_loss_rating(capquiz_user $user, capquiz_question $question);

    public abstract function user_victory_rating(capquiz_user $user, capquiz_question $question);

    //Must return an array with modified ratings such as: array(first_rating, second_rating)
    public abstract function question_draw_ratings(capquiz_question $first, capquiz_question $second);

    //Must return an array with modified ratings such as: array(winner_rating, loser_rating)
    public abstract function question_victory_ratings(capquiz_question $winner, capquiz_question $loser);

}
