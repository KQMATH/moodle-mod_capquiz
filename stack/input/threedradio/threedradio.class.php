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

defined('MOODLE_INTERNAL') || die();

// Input that is a radio/multiple choice with 3d-rendered equations.
//
// @copyright  2021 Norwegian University of Science and Technology.
// @author     David Rise Knotten and Simen Nesse Wiik.
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.

require_once(__DIR__ . '/../dropdown/dropdown.class.php');
class stack_threedradio_input extends stack_dropdown_input {

    protected $ddltype = '3dradio';

    protected $nonotanswered = false;

    /*
     * Default ddldisplay for radio is 'LaTeX'.
     */
    protected $ddldisplay = 'casstring';

    protected $extraoptions = array(
        'backgroundcolor' => 'white',
        'graphcolorstyle' => 'rainbow',
        'disableaxes' => false,
        'ranges' => '-3;3;-3;3',
        'graphopacity' => null,
        'canvasheight' => null,
        'canvaswidth' => null,
    );

    public function render(stack_input_state $state, $fieldname, $readonly, $tavalue) {

        if ($this->errors) {
            return $this->render_error($this->errors);
        }

        // First load in the javascript needed to render the graphs
        global $PAGE;
        $PAGE->requires->js('/question/type/stack/amd/src/mathbox-bundle.js');
        $PAGE->requires->js('/question/type/stack/amd/build/parser.min.js');

        // Create html.
        $result = '';
        $values = $this->get_choices();
        $selected = $state->contents;

        $selected = array_flip($state->contents);
        $radiobuttons = array();
        $classes = array();

        foreach ($values as $key => $ansid) {
            $inputattributes = array(
                'type' => 'radio',
                'name' => $fieldname,
                'value' => $key,
                'id' => $fieldname . '_' . $key
            );
            $threejsattributes = array(
                'inputid' => $inputattributes['id'],
                'exp' => $ansid,
                'options' => $this->extraoptions,
            );
            if (array_key_exists($key, $selected)) {
                $inputattributes['checked'] = 'checked';
            }
            if ($readonly) {
                $inputattributes['disabled'] = 'disabled';
            }
            $radiobuttons[] = html_writer::empty_tag('input', $inputattributes);
            $PAGE->requires->js_call_amd('qtype_stack/threedradio', 'threedradio', $threejsattributes);
        }

        $result = '';

        $result .= html_writer::start_tag('div', array('class' => 'answer'));
        foreach ($radiobuttons as $key => $radio) {
            $result .= html_writer::tag('div', stack_maths::process_lang_string($radio), array('class' => 'option'));
        }
        $result .= html_writer::end_tag('div');

        return $result;

    }

    /**
     * This allows each input type to adapt to the values of parameters.  For example, the dropdown and units
     * use this to sort out options.
     */
    protected function internal_contruct() {
        $options = $this->get_parameter('options');
        if (trim($options) != '') {
            $options = explode(',', $options);
            foreach ($options as $option) {
                $option = strtolower(trim($option));
                list($option, $arg) = stack_utils::parse_option($option);
                // Only accept those options specified in the array for this input type.
                if (array_key_exists($option, $this->extraoptions)) {
                    if ($arg === '') {
                        // Extra options with no argument set a Boolean flag.
                        $this->extraoptions[$option] = true;
                    } else {
                        $this->extraoptions[$option] = $arg;
                    }
                } else {
                    $this->errors[] = stack_string('inputoptionunknown', $option);
                }
            }
        }
        $this->validate_extra_options();
    }

    /**
     * Return the default values for the parameters.
     * Parameters are options a teacher might set.
     * @return array parameters` => default value.
     */
    public static function get_parameters_defaults() {
        return array(
            'mustVerify' => false,
            'showValidation' => 0,
            'boxWidth' => 15,
            'insertStars' => 0,
            'syntaxHint' => '',
            'syntaxAttribute' => 0,
            'forbidWords' => '',
            'allowWords' => '',
            'forbidFloats' => false,
            'lowestTerms' => true,
            'sameType' => true,
            'options' => ''
        );
    }

    /**
     * Validate the individual extra options.
     *
     * Currently only taking into account the threedradio-specific extra options
     */
    public function validate_extra_options() {
        foreach ($this->extraoptions as $option => $arg) {

            switch ($option) {
                case 'backgroundcolor':
                    if (!(in_array($arg, ['white', 'black', 'blue', 'green', 'red', 'yellow', null], false))) {
                        $this->errors[] = $arg . ' is not a supported color!';
                    }
                    break;

                case 'graphcolorstyle':
                    if (!(in_array($arg, ['grayscale', 'rainbow', 'lightblue', 'solidblue', null], false))) {
                        $this->errors[] = $arg . ' is not a supported graph color style!';
                    }
                    break;

                case 'disableaxes':
                    if(!(is_bool($arg) || $arg == 'false' || $arg == 'true')) {
                        $this->errors[] = $arg . ' is not a valid arguement for disableaxes, should be true or false';
                    }
                    break;

                case 'ranges':
                    $pattern = '/^-?\d+(;-?\d+){3}$/i';
                    if (!(preg_match($pattern, $arg))) {
                        $this->errors[] = $arg . ' is not a valid arguement, ranges input format is \'xmin;xmax;ymin;ymax\'';
                    }
                    break;

                case 'graphopacity':
                    $pattern = '/^\d(\.\d)?$/i';
                    if (!(($arg <= 1 && $arg >= 0 && (preg_match($pattern, $arg))) || $arg == null)) {
                        $this->errors[] = $arg . ' must be a number between 0.0 and 1.0 with one decimal';
                    }
                    break;

                case ('height' || 'width'):
                    $pattern = '/^((auto)|(\d+(px|em|rem|vw|vh|ch))|(((\d{1,2})|(100))\%))$/i';
                    if (!(preg_match($pattern, $arg) || $arg == null)) {
                        $this->errors[] = $arg . ' is not a valid ' . $option . ' value.';
                    }
                    break;

                default:
            }
        }
    }
}