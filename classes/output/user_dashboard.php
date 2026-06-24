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

declare(strict_types=1);

namespace mod_capquiz\output;

use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use core\url;
use mod_capquiz\capquiz;

/**
 * Learner dashboard.
 *
 * @package   mod_capquiz
 * @author    Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright 2026 Norwegian University of Science and Technology (NTNU)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_dashboard implements renderable, templatable {
    /**
     * Constructor.
     *
     * @param capquiz $capquiz
     */
    public function __construct(
        /** @var capquiz CAPQuiz */
        private readonly capquiz $capquiz,
    ) {
    }

    /**
     * Render the question attempt.
     *
     * @param renderer_base $output
     * @return bool|string
     */
    public function render(renderer_base $output): bool|string {
        return $output->render_from_template('capquiz/user_dashboard', $this->export_for_template($output));
    }

    /**
     * Export parameters for template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $cm = $this->capquiz->get_cm();
        return [
            'isopen' => $this->capquiz->is_open(),
            'timeopen' => $this->capquiz->get('timeopen'),
            'timedue' => $this->capquiz->get('timedue'),
            'attemptlink' => [
                'url' => (new url('/mod/capquiz/attempt.php', ['id' => $cm->id]))->out(false),
                'classes' => 'btn btn-link',
                'text' => get_string('attemptquiz', 'capquiz'),
            ],
        ];
    }
}
