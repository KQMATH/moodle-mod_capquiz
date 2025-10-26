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

namespace capquizreport_questions;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * This is the settings form for the capquiz questions report.
 *
 * @package     capquizreport_questions
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class form extends \moodleform {
    /**
     * Defines the form
     */
    protected function definition(): void {
        $mform = $this->_form;

        $mform->addElement('hidden', 'attempts', \mod_capquiz\local\reports\options::ALL_WITH);
        $mform->setType('attempts', PARAM_ALPHAEXT);

        $mform->addElement('hidden', 'onlyanswered', 1);
        $mform->setType('onlyanswered', PARAM_INT);

        $mform->addElement('text', 'pagesize', get_string('pagesize', 'quiz'));
        $mform->setType('pagesize', PARAM_INT);

        $mform->addGroup([
            $mform->createElement('advcheckbox', 'qtext', '', get_string('questiontext', 'quiz_responses')),
        ], 'coloptions', get_string('showthe', 'quiz_responses'), [' '], false);

        $mform->addElement('submit', 'submitbutton', get_string('showreport', 'quiz'));
    }
}
