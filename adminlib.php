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
 * This file contains the classes for the admin settings of the capquiz module.
 *
 * @package     mod_capquiz
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/adminlib.php');

/**
 * Admin external page that displays a list of the installed capquiz plugins.
 *
 * @package     mod_capquiz
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_admin_page_manage_capquiz_plugins extends admin_externalpage {

    /** @var string the name of plugin subtype */
    private string $subtype;

    /**
     * Constructor.
     *
     * @param string $subtype
     */
    public function __construct(string $subtype) {
        $this->subtype = $subtype;
        $url = new moodle_url('/mod/capquiz/adminmanageplugins.php', ['subtype' => $subtype]);
        parent::__construct('manage' . $subtype . 'plugins', get_string('manage' . $subtype . 'plugins', 'capquiz'), $url);
    }

    /**
     * Search plugins for the specified string
     *
     * @param string $query The string to search for
     */
    public function search($query): array {
        $result = parent::search($query);
        if ($result) {
            return $result;
        }
        $found = false;
        foreach (core_component::get_plugin_list($this->subtype) as $name => $notused) {
            $pluginname = get_string('pluginname', $this->subtype . '_' . $name);
            if (str_contains(core_text::strtolower($pluginname), $query)) {
                $found = true;
                break;
            }
        }
        if ($found) {
            $result = new stdClass();
            $result->page = $this;
            $result->settings = [];
            return [$this->name => $result];
        } else {
            return [];
        }
    }
}


/**
 * Class that handles the display and configuration of the list of capquiz plugins.
 *
 * @package     mod_capquiz
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_plugin_manager {

    /** @var moodle_url the url of the manage capquiz plugin page */
    private moodle_url $pageurl;

    /** @var string report */
    private string $subtype;

    /** @var string any error from the current action */
    private string $error = '';

    /**
     * Constructor.
     *
     * @param string $subtype
     */
    public function __construct(string $subtype) {
        $this->pageurl = new moodle_url('/mod/capquiz/adminmanageplugins.php', ['subtype' => $subtype]);
        $this->subtype = $subtype;
    }

    /**
     * This is the entry point for this controller class.
     *
     * @param string $action Action to perform
     * @param ?string $plugin Optional name of a plugin type to perform the action on
     */
    public function execute(string $action = 'view', ?string $plugin = null): void {
        $this->check_permissions();
        if ($plugin !== null) {
            if ($action === 'hide') {
                $action = $this->hide_plugin($plugin);
            } else if ($action === 'show') {
                $action = $this->show_plugin($plugin);
            } else if ($action === 'moveup') {
                $action = $this->move_plugin($plugin, 'up');
            } else if ($action === 'movedown') {
                $action = $this->move_plugin($plugin, 'down');
            }
        }
        if ($action === 'view') {
            $this->view_plugins_table();
        }
    }

    /**
     * Check this user has permission to edit the list of installed plugins
     */
    private function check_permissions(): void {
        require_login();
        $systemcontext = context_system::instance();
        require_capability('moodle/site:config', $systemcontext);
    }

    /**
     * Hide this plugin.
     *
     * @param string $plugin - The plugin to hide
     * @return string The next page to display
     */
    public function hide_plugin(string $plugin): string {
        set_config('disabled', 1, $this->subtype . '_' . $plugin);
        core_plugin_manager::reset_caches();
        return 'view';
    }

    /**
     * Show this plugin.
     *
     * @param string $plugin - The plugin to show
     * @return string The next page to display
     */
    public function show_plugin(string $plugin): string {
        set_config('disabled', 0, $this->subtype . '_' . $plugin);
        core_plugin_manager::reset_caches();
        return 'view';
    }

    /**
     * Change the order of this plugin.
     *
     * @param string $plugintomove - The plugin to move
     * @param string $dir - up or down
     * @return string The next page to display
     */
    public function move_plugin(string $plugintomove, string $dir): string {
        // Get a list of the current plugins.
        $plugins = $this->get_sorted_plugins_list();

        $currentindex = 0;

        // Throw away the keys.
        $plugins = array_values($plugins);

        // Find this plugin in the list.
        foreach ($plugins as $key => $plugin) {
            if ($plugin == $plugintomove) {
                $currentindex = $key;
                break;
            }
        }

        // Make the switch.
        if ($dir == 'up') {
            if ($currentindex > 0) {
                $tempplugin = $plugins[$currentindex - 1];
                $plugins[$currentindex - 1] = $plugins[$currentindex];
                $plugins[$currentindex] = $tempplugin;
            }
        } else if ($dir == 'down') {
            if ($currentindex < (count($plugins) - 1)) {
                $tempplugin = $plugins[$currentindex + 1];
                $plugins[$currentindex + 1] = $plugins[$currentindex];
                $plugins[$currentindex] = $tempplugin;
            }
        }

        // Save the new normal order.
        foreach ($plugins as $key => $plugin) {
            set_config('sortorder', $key, $this->subtype . '_' . $plugin);
        }
        return 'view';
    }

    /**
     * Return a list of plugins sorted by the order defined in the admin interface
     *
     * @return array The list of plugins
     */
    public function get_sorted_plugins_list(): array {
        $names = core_component::get_plugin_list($this->subtype);
        $result = [];
        foreach ($names as $name => $path) {
            $idx = get_config($this->subtype . '_' . $name, 'sortorder');
            if (!$idx) {
                $idx = 0;
            }
            while (array_key_exists($idx, $result)) {
                $idx += 1;
            }
            $result[$idx] = $name;
        }
        ksort($result);
        return $result;
    }

    /**
     * Write the HTML for the capquiz plugins table.
     */
    private function view_plugins_table(): void {
        global $OUTPUT, $CFG;
        require_once($CFG->libdir . '/tablelib.php');

        // Set up the table.
        $this->view_header();
        $table = new flexible_table($this->subtype . 'pluginsadminttable');
        $table->define_baseurl($this->pageurl);
        $table->define_columns(['pluginname', 'version', 'hideshow', 'order', 'settings', 'uninstall']);
        $table->define_headers([get_string($this->subtype . 'type', 'capquiz'),
            get_string('version'), get_string('hideshow', 'capquiz'),
            get_string('order'), get_string('settings'), get_string('uninstallplugin', 'core_admin')]);
        $table->set_attribute('id', $this->subtype . 'plugins');
        $table->set_attribute('class', 'admintable generaltable');
        $table->setup();

        $plugins = $this->get_sorted_plugins_list();
        $shortsubtype = substr($this->subtype, strlen('capquiz'));

        foreach ($plugins as $idx => $plugin) {
            $row = [];
            $class = '';

            $row[] = get_string('pluginname', $this->subtype . '_' . $plugin);
            $row[] = get_config($this->subtype . '_' . $plugin, 'version');

            $visible = !get_config($this->subtype . '_' . $plugin, 'disabled');

            if ($visible) {
                $row[] = $this->format_icon_link('hide', $plugin, 't/hide', get_string('disable'));
            } else {
                $row[] = $this->format_icon_link('show', $plugin, 't/show', get_string('enable'));
                $class = 'dimmed_text';
            }

            $movelinks = '';
            if (!$idx == 0) {
                $movelinks .= $this->format_icon_link('moveup', $plugin, 't/up', get_string('up'));
            } else {
                $movelinks .= $OUTPUT->spacer(['width' => 16]);
            }
            if ($idx != count($plugins) - 1) {
                $movelinks .= $this->format_icon_link('movedown', $plugin, 't/down', get_string('down'));
            }
            $row[] = $movelinks;

            $exists = file_exists($CFG->dirroot . '/mod/capquiz/' . $shortsubtype . '/' . $plugin . '/settings.php');
            if ($row[1] !== '' && $exists) {
                $url = new moodle_url('/admin/settings.php', ['section' => $this->subtype . '_' . $plugin]);
                $row[] = html_writer::link($url, get_string('settings'));
            } else {
                $row[] = '&nbsp;';
            }

            $row[] = $this->format_icon_link('delete', $plugin, 't/delete', get_string('uninstallplugin', 'core_admin'));

            $table->add_data($row, $class);
        }

        $table->finish_output();
        $this->view_footer();
    }

    /**
     * Write the page header
     */
    private function view_header(): void {
        global $OUTPUT;
        admin_externalpage_setup('manage' . $this->subtype . 'plugins');
        // Print the page heading.
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('manage' . $this->subtype . 'plugins', 'capquiz'));
    }

    /**
     * Util function for writing an action icon link
     *
     * @param string $action URL parameter to include in the link
     * @param string $plugin URL parameter to include in the link
     * @param string $icon The key to the icon to use (e.g. 't/up')
     * @param string $alt The string description of the link used as the title and alt text
     * @return string The icon/link
     */
    private function format_icon_link(string $action, string $plugin, string $icon, string $alt): string {
        global $OUTPUT;
        if ($action === 'delete') {
            $url = core_plugin_manager::instance()->get_uninstall_url($this->subtype . '_' . $plugin, 'manage');
            if (!$url) {
                return '&nbsp;';
            }
            return html_writer::link($url, get_string('uninstallplugin', 'core_admin'));
        }
        $url = new moodle_url($this->pageurl, ['action' => $action, 'plugin' => $plugin, 'sesskey' => sesskey()]);
        $icon = new pix_icon($icon, $alt, 'moodle', ['title' => $alt]);
        return $OUTPUT->action_icon($url, $icon, null, ['title' => $alt]) . ' ';
    }

    /**
     * Write the page footer
     */
    private function view_footer(): void {
        global $OUTPUT;
        echo $OUTPUT->footer();
    }
}
