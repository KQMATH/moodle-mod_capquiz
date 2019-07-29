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
 * @package     mod_capquiz
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/capquiz/adminlib.php');

$modcapquizfolder = new admin_category('modcapquizfolder', new lang_string('pluginname', 'capquiz'), $module->is_enabled() === false);
$ADMIN->add('modsettings', $modcapquizfolder);

$settings = new admin_settingpage($section, get_string('settings', 'capquiz'), 'moodle/site:config', !$module->is_enabled());
$ADMIN->add('modcapquizfolder', $settings);
// Tell core we already added the settings structure.
$settings = null;

// Folder 'capquiz report'.
$ADMIN->add('modcapquizfolder', new admin_category('capquizreportplugins',
    new lang_string('reportplugin', 'capquiz'), !$module->is_enabled()));
$ADMIN->add('capquizreportplugins', new capquiz_admin_page_manage_capquiz_plugins('capquizreport'));


foreach (core_plugin_manager::instance()->get_plugins_of_type('capquizreport') as $plugin) {
    $plugin->load_settings($ADMIN, 'capquizreportplugins', $hassiteconfig);
}