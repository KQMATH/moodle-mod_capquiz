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

namespace mod_capquiz\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_capquiz\capquiz_slot;

/**
 * Add questions to a CAPQuiz.
 *
 * @package     mod_capquiz
 * @author      Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright   2025 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_question_rating extends external_api {
    /**
     * Describe parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT),
            'slotid' => new external_value(PARAM_INT),
            'rating' => new external_value(PARAM_FLOAT),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $cmid
     * @param int $slotid
     * @param float $rating
     * @return array
     */
    public static function execute(int $cmid, int $slotid, float $rating): array {
        [
            'cmid' => $cmid,
            'slotid' => $slotid,
            'rating' => $rating,
        ] = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'slotid' => $slotid,
            'rating' => $rating,
        ]);
        $context = \core\context\module::instance($cmid);
        self::validate_context($context);
        require_capability('mod/capquiz:instructor', $context);
        $slot = new capquiz_slot($slotid);
        $questionrating = $slot->rate($rating, true);
        return ['questionratingid' => $questionrating->get('id')];
    }

    /**
     * Describe return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(['questionratingid' => new external_value(PARAM_INT)]);
    }
}
