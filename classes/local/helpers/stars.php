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

namespace mod_capquiz\local\helpers;

use mod_capquiz\capquiz;
use mod_capquiz\capquiz_user;

/**
 * Helper functions for the star level system.
 *
 * @package     mod_capquiz
 * @author      Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright   2024 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class stars {
    /**
     * Returns the completion level to the next rating as a percent value.
     *
     * @param capquiz $capquiz
     * @param float $userrating
     */
    public static function get_percent_to_next_star(capquiz $capquiz, float $userrating): int {
        $defaultrating = $capquiz->get('defaultuserrating');
        $ratings = $capquiz->get('starratings');
        $nextrating = 0;
        for ($star = 1; $star <= $capquiz->get_max_stars(); $star++) {
            $nextrating = self::get_required_rating_for_star($ratings, $star);
            if ($nextrating > $userrating) {
                $previous = $star === 1 ? $defaultrating : self::get_required_rating_for_star($ratings, $star - 1);
                $userrating -= $previous;
                $nextrating -= $previous;
                break;
            }
        }
        return $nextrating >= 1 ? (int)($userrating / $nextrating * 100.0) : 0;
    }

    /**
     * Get the required rating for a given star.
     *
     * @param string $ratingscsv CSV of star ratings (e.g. 1200,1400,1600,1800,2000)
     * @param int $star 1..n
     */
    public static function get_required_rating_for_star(string $ratingscsv, int $star): float {
        $index = $star - 1;
        $ratings = explode(',', $ratingscsv);
        if ($index >= count($ratings)) {
            return (float)$ratings[count($ratings) - 1];
        }
        return (float)$ratings[$index];
    }

    /**
     * Returns the number of stars that can be achieved.
     *
     * @param string $ratingscsv CSV of star ratings (e.g. 1200,1400,1600,1800,2000)
     */
    public static function get_max_stars(string $ratingscsv): int {
        return substr_count($ratingscsv, ',') + 1;
    }

    /**
     * Check if user has achieved a passing grade.
     *
     * @param capquiz_user $user
     * @param capquiz $capquiz
     * @return bool
     */
    public static function is_user_passing(capquiz_user $user, capquiz $capquiz): bool {
        return $user->get('starsgraded') >= $capquiz->get('starstopass');
    }
}
