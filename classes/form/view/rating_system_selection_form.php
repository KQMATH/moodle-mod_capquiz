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

namespace mod_capquiz\form\view;

use mod_capquiz\capquiz;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rating_system_selection_form extends \moodleform {
    private $capquiz;

    public function __construct(capquiz $capquiz, \moodle_url $url) {
        $this->capquiz = $capquiz;
        parent::__construct($url);
    }

    public function definition() {
        $form = $this->_form;
        $loader = $this->capquiz->rating_system_loader();
        $registry = $this->capquiz->rating_system_registry();
        $index = 0;
        $selectedindex = -1;
        $radioarray = [];
        foreach ($registry->rating_systems() as $ratingsystem) {
            if ($loader->current_rating_system_name() === $ratingsystem) {
                $selectedindex = $index;
            }
            $radioarray[] = $form->createElement('radio', 'rating_system', '', $ratingsystem, $index, [
                $ratingsystem
            ]);
            $index++;
        }
        $form->addGroup($radioarray, 'radioar', '', '</br>', false);
        $this->add_action_buttons(false);
        if ($selectedindex > -1) {
            $form->setDefault('rating_system', $selectedindex);
        }
    }

    public function validations($data, $files) {
        return [];
    }

}