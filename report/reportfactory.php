<?php
// This file is part of Stack - http://stack.maths.ed.ac.uk/
//
// Stack is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Stack is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Stack.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Capquiz report factory. Provides a convenient way to create an capquiz report of any type.
 *
 * @package     mod_capquiz
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz\report;

use capquiz_exception;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/capquiz/locallib.php');

/**
 * Capquiz report factory. Provides a convenient way to create an capquiz report of any type.
 *
 * @package     mod_capquiz
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_report_factory {

    /**
     * Create an capquiz report of a given type and return it.
     *
     * @param string $type the required type.
     * @return capquiz_attempts_report the requested capquiz report.
     * @throws capquiz_exception
     */
    public static function make($type) {
        $class = self::class_for_type($type);

        return new $class();
    }

    /**
     * The class name corresponding to an report type.
     * @param string $type report type name.
     * @return string corresponding class name.
     */
    protected static function class_for_type($type) {
        global $CFG;
        $typelc = strtolower($type);
        $file = $CFG->dirroot . '/mod/capquiz/report/' . $type . '/report.php';
        $class = "capquizreport_{$typelc}\\capquizreport_{$typelc}_report";
        if (!is_readable($file)) {
            throw new capquiz_exception('capquiz_report_factory: unknown report type ' . $type);
        }
        include_once($file);

        if (!class_exists($class)) {
            throw new capquiz_exception('capquiz_report_factory: report type ' . $type .
                ' does not define the expected class ' . $class);
        }
        return $class;
    }
}
