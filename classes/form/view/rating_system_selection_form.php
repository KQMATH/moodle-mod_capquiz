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

/**
 * CAPQuiz rating system selection form definition.
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz\form\view;

use mod_capquiz\capquiz;
use mod_capquiz\capquiz_rating_system_loader;
use mod_capquiz\capquiz_rating_system_registry;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * rating_system_selection_form class
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rating_system_selection_form extends \moodleform {

    /** @var capquiz $capquiz */
    private capquiz $capquiz;

    /**
     * Constructor.
     *
     * @param capquiz $capquiz
     * @param moodle_url $url
     */
    public function __construct(capquiz $capquiz, moodle_url $url) {
        $this->capquiz = $capquiz;
        parent::__construct($url);
    }

    /**
     * Defines form
     */
    public function definition(): void {
        $form = $this->_form;
        $loader = new capquiz_rating_system_loader($this->capquiz);
        $registry = new capquiz_rating_system_registry();
        $index = 0;
        $selectedindex = -1;
        $radioarray = [];
        foreach ($registry->rating_systems() as $ratingsystem) {
            if ($loader->current_rating_system_name() === $ratingsystem) {
                $selectedindex = $index;
            }
            $radioarray[] = $form->createElement('radio', 'rating_system', '', $ratingsystem, $index, [$ratingsystem]);
            $index++;
        }
        $form->addGroup($radioarray, 'radioar', '', '</br>', false);
        $this->add_action_buttons(false);
        if ($selectedindex > -1) {
            $form->setDefault('rating_system', $selectedindex);
        }
    }

    /**
     * Validate the data from the form.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validations($data, $files): array {
        return [];
    }

}
