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

defined('MOODLE_INTERNAL') || die();

abstract class capquiz_rating_system {

    public abstract function update_user_loss_rating(capquiz_user $user, capquiz_question $question);

    public abstract function update_user_victory_rating(capquiz_user $user, capquiz_question $question);

    public abstract function question_victory_ratings(capquiz_question $winner, capquiz_question $loser);

}
