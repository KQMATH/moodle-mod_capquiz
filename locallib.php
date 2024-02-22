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
 * Library of internal classes and functions for module CAPQuiz
 *
 * @package     mod_capquiz
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Base class for all the types of exception we throw.
 *
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_exception extends moodle_exception {
}

/**
 * A {@link qubaid_condition} for finding all the question usages belonging to
 * a particular capquiz.
 *
 * @author      sumaiya Javed <sumaiya.javed@catalyst.net.nz>
 * @copyright   2023 Catalyst IT Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qubaids_for_capquiz extends qubaid_join {
    public function __construct($capquizid) {
        $where = 'capquiza.capquiz_id = :quizaquiz';
        $params = array('quizaquiz' => $capquizid);

        parent::__construct('{capquiz_user} capquiza', 'capquiza.question_usage_id', $where, $params);
    }
}
