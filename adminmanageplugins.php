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
 * Allows the admin to manage capquiz plugins
 *
 * @package     mod_capquiz
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");

require_login();

require_once($CFG->dirroot . '/mod/capquiz/adminlib.php');

$subtype = required_param('subtype', PARAM_PLUGIN);
$action = optional_param('action', 'view', PARAM_PLUGIN);
$plugin = optional_param('plugin', null, PARAM_PLUGIN);

if (!empty($plugin)) {
    require_sesskey();
}

$PAGE->set_context(context_system::instance());

$pluginmanager = new capquiz_plugin_manager($subtype);
$pluginmanager->execute($action, $plugin);
