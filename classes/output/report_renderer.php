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
 * This file defines a class used to render a report
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz\output;

use capquiz_exception;
use mod_capquiz\capquiz;
use mod_capquiz\capquiz_urls;
use mod_capquiz\report\capquiz_report_factory;
use tabobject;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../report/reportfactory.php');

/**
 * Class report_renderer
 *
 * @package     mod_capquiz
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_renderer {

    /** @var capquiz $capquiz */
    private $capquiz;

    /** @var renderer $renderer */
    private $renderer;

    /**
     * report_renderer constructor.
     * @param capquiz $capquiz
     * @param renderer $renderer
     */
    public function __construct(capquiz $capquiz, renderer $renderer) {
        $this->capquiz = $capquiz;
        $this->renderer = $renderer;
    }

    /**
     * Renders report
     *
     * @return \lang_string|string
     * @throws \coding_exception
     * @throws capquiz_exception
     */
    public function render() {
        global $CFG;
        $html = '';
        $download = optional_param('download', '', PARAM_RAW);
        $mode = optional_param('mode', '', PARAM_ALPHA);

        $reportlist = capquiz_report_list($this->capquiz->context());
        if (empty($reportlist)) {
            return get_string('noreports', 'capquiz');
        }
        if ($mode == '') {
            // Default to first accessible report and redirect.
            capquiz_urls::redirect_to_url(capquiz_urls::view_report_url(reset($reportlist)));
        }
        if (!in_array($mode, $reportlist)) {
            throw new capquiz_exception('erroraccessingreport', 'capquiz',
                $CFG->wwwroot.'/mod/capquiz/view.php?id=' . $this->capquiz->course()->id);
        }
        $report = capquiz_report_factory::make($mode);
        $this->setup_report();

        $row = array();
        foreach ($reportlist as $rep) {
            $row[] = new tabobject('capquiz_' . $rep, capquiz_urls::view_report_url($rep),
                get_string('pluginname', 'capquizreport_' . $rep));
        }
        $tabs[] = $row;

        $html .= print_tabs($tabs, 'capquiz_' . $mode, null, null, true);

        ob_start();
        $report->display($this->capquiz, $this->capquiz->course_module(), $this->capquiz->course(), $download);
        $html .= ob_get_clean();

        return $html;

    }

    /**
     * Sets pagelayout to "report"
     */
    private function setup_report() {
        $PAGE = $this->capquiz->renderer()->PAGE;
        $PAGE->set_pagelayout('report');
    }
}

