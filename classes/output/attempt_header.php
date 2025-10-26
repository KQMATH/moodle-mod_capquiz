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

use core\output\renderable;
use core\output\renderer_base;
use mod_capquiz\capquiz;
use mod_capquiz\capquiz_user;
use mod_capquiz\local\helpers\stars;
use templatable;

/**
 * Question attempt header displaying an overview of stars achieved, lost, and not yet reached.
 *
 * @package     mod_capquiz
 * @author      Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright   2024 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt_header implements renderable, templatable {
    /**
     * Constructor.
     *
     * @param capquiz_user $user
     */
    public function __construct(
        /** @var capquiz_user User */
        private readonly capquiz_user $user,
    ) {
    }

    /**
     * Render the classlist.
     *
     * @param renderer_base $output
     * @return bool|string
     */
    public function render(renderer_base $output): bool|string {
        return $output->render_from_template('capquiz/attempt_header', $this->export_for_template($output));
    }

    /**
     * Export parameters for template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $capquiz = new capquiz($this->user->get('capquizid'));
        $percent = stars::get_percent_to_next_star($capquiz, $this->user->get('rating'));
        $stars = [];
        $starratings = $capquiz->get('starratings');
        for ($star = 1; $star <= $capquiz->get_max_stars(); $star++) {
            if ($this->user->get('higheststars') >= $star) {
                if ($this->user->get('rating') >= stars::get_required_rating_for_star($starratings, $star)) {
                    $stars[] = ['icon' => 'star', 'tooltip' => get_string('tooltip_achieved_star', 'capquiz')];
                } else {
                    $stars[] = ['icon' => 'blank-star', 'tooltip' => get_string('tooltip_lost_star', 'capquiz')];
                }
            } else {
                $stars[] = ['icon' => 'no-star', 'tooltip' => get_string('tooltip_no_star', 'capquiz')];
            }
        }
        return [
            'percentup' => max($percent, 0),
            'percentdown' => min($percent, 0),
            'stars' => $stars,
        ];
    }
}
