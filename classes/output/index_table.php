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

/**
 * Display overview of a list of CAPQuizzes.
 *
 * @package     mod_capquiz
 * @author      Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright   2024 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class index_table implements \core\output\renderable, \core\output\templatable {
    /**
     * Constructor.
     *
     * @param capquiz[] $capquizzes
     */
    public function __construct(
        /** @var capquiz[] CAPQuizzes */
        private readonly array $capquizzes,
    ) {
    }

    /**
     * Render the index table.
     *
     * @param renderer_base $output
     * @return bool|string
     */
    public function render(renderer_base $output): bool|string {
        return $output->render_from_template('capquiz/index_table', $this->export_for_template($output));
    }

    /**
     * Export parameters for template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        return [
            'capquizzes' => array_map(fn(capquiz $capquiz) => [
                'viewurl' => (new \core\url('/mod/capquiz/view.php', [
                    'id' => (int)$capquiz->get_cm()->id,
                ]))->out(false),
                'name' => $capquiz->get('name'),
                'timeopen' => $capquiz->get('timeopen'),
                'timedue' => $capquiz->get('timedue'),
                'isopen' => $capquiz->is_open(),
                'defaultuserrating' => $capquiz->get('defaultuserrating'),
                'defaultquestionrating' => $capquiz->get('defaultquestionrating'),
            ], $this->capquizzes),
        ];
    }
}
