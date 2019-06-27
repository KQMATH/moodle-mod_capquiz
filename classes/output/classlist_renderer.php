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
use mod_capquiz\capquiz_user;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/editlib.php');

/**
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @author      Sebastian S. Gundersen <sebastian@sgundersen.com>
 * @copyright   2019 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class classlist_renderer {

    /** @var capquiz $capquiz */
    private $capquiz;

    /** @var renderer $renderer */
    private $renderer;

    public function __construct(capquiz $capquiz, renderer $renderer) {
        $this->capquiz = $capquiz;
        $this->renderer = $renderer;
    }

    public function render() {
        global $PAGE;
        $cmid = $this->capquiz->course_module()->id;
        $PAGE->requires->js_call_amd('mod_capquiz/edit_questions', 'initialize', [$cmid]);
        $users = capquiz_user::list_users($this->capquiz->id());
        $rows = [];
        for ($i = 0; $i < count($users); $i++) {
            $user = $users[$i];
            $rows[] = [
                'index' => $i + 1,
                'username' => $user->username(),
                'firstname' => $user->first_name(),
                'lastname' => $user->last_name(),
                'rating' => round($user->rating(), 2),
                'stars' => $user->highest_level(),
                'graded_stars' => $user->stars_graded(),
                'passing_grade' => $user->stars_graded() >= $this->capquiz->stars_to_pass()
            ];
        }
        $leaderboard = $this->renderer->render_from_template('capquiz/classlist', [
            'users' => $rows
        ]);
        return $leaderboard;
    }

}
