<?php
// This file is part of Stack - https://stack.maths.ed.ac.uk
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

// JSXGraph block essentially repeats the functionality of the JSXGraph filter
// in Moodle but limits the use to authors thus negating the primary security
// issue. More importantly it also provides STACK specific extensions in
// the form of references to question inputs.
//
// While this filter is simple and repeat existing logic it does have a purpose.
//
// @copyright  2021 Norwegian University of Science and Technology
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
// @authors    David Rise Knotten, Simen Nesse Wiik

require_once("block.interface.php");
require_once(__DIR__ . '/../../../../../../lib/pagelib.php');

class stack_cas_castext_mathbox extends stack_cas_castext_block {

    private static $boxcount = 1;
    // TODO make the mathbox instances more confined to allow multiple blocks on one page (Maybe by wrapping the javascript in a finctino)
    public function clear() {
        global $PAGE;
        $PAGE->requires->js_call_amd('qtype_stack/threedradio', 'initMethods');

        $rootid = 'mathbox-root-'.self::$boxcount.'';

        $code = 'var rootid = "'.$rootid.'"; var root = document.getElementById(rootid); '.
        'function threedwait() {if (typeof window?.threed == "undefined") {setTimeout(threedwait, 100);} else { ';

        // TODO regex out <br>
        $code .= strip_tags(rtrim(ltrim($this->get_node()->to_string(), '[[ mathbox ]]'), '[[/ mathbox ]]')) . ' }}; threedwait();';
        print_object($code); //notifytiny


        $scriptattributes = array(
            'id' => 'mathbox-script-'.self::$boxcount,
            'class' => 'mathbox-script',
            'type' => 'application/javascript',
        );

        $script = html_writer::tag('script',$code, $scriptattributes);

        $divattributes = array(
            'id' => $rootid,
            'class' => 'mathbox-root',
        );
        $this->get_node()->convert_to_text(html_writer::tag('div', $script, $divattributes));

        self::$boxcount++;

    }

    public function extract_attributes($tobeevaluatedcassession, $conditionstack = null) {
    }

    public function content_evaluation_context($conditionstack = array()) {
        return $conditionstack;
    }

    public function process_content($evaluatedcassession, $conditionstack = null) {
        return false;
    }

    public function validate(&$errors = array()) {
        $valid = true;
        // TODO make this work, most importantly, check that a valid exp has been set
        /*
        $options = array();
        foreach (preg_split('/,/', preg_replace('/ /', '//',strip_tags(rtrim(ltrim($this->get_node()->to_string(), '[[ mathbox ]]'), '[[/ mathbox ]]')))) as $key => $value) {
            if ($value != null) {
                $value = preg_split('/=/', $value);
                $options[$value[0]] = $value[1];
            }
        }
        if (!isset($options['exp'])) {
            $valid = false;
            $this->errors[] = 'An exp value must be set';
        }
        */

        if ($valid) {
            $valid = parent::validate($errors);
        }

        return $valid;
    }

    public function validate_extract_attributes() {
        return array();
    }

}
