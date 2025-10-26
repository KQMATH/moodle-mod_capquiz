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

use core\output\renderer_base;
use mod_capquiz\capquiz;
use mod_capquiz\capquiz_user;
use renderable;
use templatable;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/editlib.php');

/**
 * Render student leaderboard/classlist.
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @author      Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright   2024 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class classlist implements renderable, templatable {
    /**
     * Constructor.
     *
     * @param capquiz $capquiz
     */
    public function __construct(
        /** @var capquiz CAPQuiz */
        private readonly capquiz $capquiz
    ) {
    }

    /**
     * Render the classlist.
     *
     * @param renderer_base $output
     * @return bool|string
     */
    public function render(renderer_base $output): bool|string {
        return $output->render_from_template('capquiz/classlist', $this->export_for_template($output));
    }

    /**
     * Export parameters for template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        global $DB;
        $rows = [];
        $users = capquiz_user::get_records(['capquizid' => $this->capquiz->get('id')], 'rating', 'DESC');
        $userids = array_map(fn(capquiz_user $user) => $user->get('userid'), $users);
        $coreusers = $DB->get_records_list('user', 'id', $userids, '', 'id,username,firstname,lastname');
        foreach ($users as $user) {
            $coreuser = $coreusers[$user->get('userid')] ?? null;
            $rows[] = [
                'username' => $coreuser?->username,
                'firstname' => $coreuser?->firstname,
                'lastname' => $coreuser?->lastname,
                'rating' => round($user->get('rating'), 2),
                'stars' => $user->get('higheststars'),
                'gradedstars' => $user->get('starsgraded'),
                'passinggrade' => $user->get('starsgraded') >= $this->capquiz->get('starstopass'),
            ];
        }
        $cm = $this->capquiz->get_cm();
        return [
            'usercount' => count($rows),
            'users' => $rows,
            'regrade' => [
                'type' => 'primary',
                'method' => 'post',
                'url' => (new \core\url('/mod/capquiz/edit.php', [
                    'id' => (int)$cm->id,
                    'action' => 'regradeall',
                ]))->out(false),
                'label' => get_string('regrade_all', 'capquiz'),
                'disabled' => !$this->capquiz->is_past_due_time(),
            ],
        ];
    }
}
