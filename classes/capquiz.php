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

namespace mod_capquiz;

require_once($CFG->libdir . '/questionlib.php');

require_once($CFG->dirroot . '/mod/capquiz/classes/capquiz_rating_system_loader.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/capquiz_matchmaking_strategy_loader.php');

defined('MOODLE_INTERNAL') || die();

/**
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz {

    /** @var \context_module $context */
    private $context;

    /** @var \stdClass $course_module */
    private $course_module;

    /** @var \stdClass $course_db_entry */
    private $course_db_entry;

    /** @var \stdClass $capquiz_db_entry */
    private $capquiz_db_entry;

    /** @var \renderer_base|output\renderer $capquiz_renderer */
    private $capquiz_renderer;

    public function __construct(int $course_module_id) {
        global $DB, $PAGE;

        $this->course_module = get_coursemodule_from_id('capquiz', $course_module_id, 0, false, MUST_EXIST);

        require_login($this->course_module->course, false, $this->course_module);

        $this->context = \context_module::instance($course_module_id);
        $PAGE->set_context($this->context);
        $this->capquiz_renderer = $PAGE->get_renderer('mod_capquiz');

        $this->course_db_entry = $DB->get_record(database_meta::$table_moodle_course, [database_meta::$field_id => $this->course_module->course], '*', MUST_EXIST);
        $this->capquiz_db_entry = $DB->get_record(database_meta::$table_capquiz, [database_meta::$field_id => $this->course_module->instance], '*', MUST_EXIST);
    }

    /**
     * @throws \coding_exception
     */
    public static function create() : capquiz {
        return self::create_from_id(capquiz_urls::require_course_module_id_param()); // throws if no id/cmid param
    }

    public static function create_from_id(int $id) : capquiz {
        return new capquiz($id);
    }

    public function id() : int {
        return $this->capquiz_db_entry->id;
    }

    public function name() : string {
        return $this->capquiz_db_entry->name;
    }

    public function description() : string {
        return $this->capquiz_db_entry->description;
    }

    public function is_published() : bool {
        return $this->capquiz_db_entry->published;
    }

    public function can_publish() : bool {
        if (!$this->has_question_list()) {
            return false;
        }
        if ($this->is_published()) {
            return false;
        }
        return $this->question_list()->has_questions();
    }

    public function is_student() : bool {
        return $this->has_capability('mod/capquiz:student');
    }

    public function is_instructor() : bool {
        return $this->has_capability('mod/capquiz:instructor');
    }

    public function question_list_id() : int {
        return $this->capquiz_db_entry->question_list_id;
    }

    public function has_question_list() : bool {
        return $this->capquiz_db_entry->question_list_id != null;
    }

    public function assign_question_list(int $question_list_id) : bool {
        global $DB;
        $this->validate_matchmaking_and_rating_systems();
        $question_list = capquiz_question_list::load_any($this, $question_list_id);
        if (!$question_list) {
            return false;
        }
        if ($question_list->is_template()) {
            $question_list_id = capquiz_question_list::copy($question_list, false);
        }
        $capquiz_entry = $this->capquiz_db_entry;
        $capquiz_entry->question_list_id = $question_list_id;
        if ($DB->update_record(database_meta::$table_capquiz, $capquiz_entry)) {
            $this->capquiz_db_entry = $capquiz_entry;
            return true;
        }
        return false;
    }

    public function publish() : bool {
        global $DB;
        if (!$this->can_publish()) {
            return false;
        }
        $capquiz_entry = $this->capquiz_db_entry;
        $capquiz_entry->published = true;
        $capquiz_entry->question_usage_id = $this->create_question_usage();
        try {
            $DB->update_record(database_meta::$table_capquiz, $capquiz_entry);
            $this->capquiz_db_entry = $capquiz_entry;
        } catch (\dml_exception $e) {
            return false;
        }
        return $this->is_published();
    }

    public function renderer() : \renderer_base {
        return $this->capquiz_renderer;
    }

    public function output() : \renderer_base {
        return $this->capquiz_renderer->output_renderer();
    }

    public function selection_strategy_loader() : capquiz_matchmaking_strategy_loader {
        return new capquiz_matchmaking_strategy_loader($this);
    }

    public function selection_strategy_registry() : capquiz_matchmaking_strategy_registry {
        return new capquiz_matchmaking_strategy_registry($this);
    }

    public function rating_system_loader() : capquiz_rating_system_loader {
        return new capquiz_rating_system_loader($this);
    }

    public function rating_system_registry() : capquiz_rating_system_registry {
        return new capquiz_rating_system_registry($this);
    }

    public function question_engine() : ?capquiz_question_engine {
        if ($question_usage = $this->question_usage()) {
            return new capquiz_question_engine($this, $question_usage, $this->selection_strategy_loader(), $this->rating_system_loader());
        }
        return null;
    }

    public function question_registry() : capquiz_question_registry {
        return new capquiz_question_registry($this);
    }

    public function question_list() : ?capquiz_question_list {
        if ($this->has_question_list()) {
            return capquiz_question_list::load_question_list($this, $this->question_list_id());
        }
        return null;
    }

    public function question_usage() : ?\question_usage_by_activity {
        if (!$this->has_question_list()) {
            return null;
        }
        if (!$this->is_published()) {
            return null;
        }
        return \question_engine::load_questions_usage_by_activity($this->capquiz_db_entry->question_usage_id);
    }

    public function user() : capquiz_user {
        global $USER;
        return capquiz_user::load_user($this, $USER->id);
    }

    public function default_user_rating() : float {
        return $this->capquiz_db_entry->default_user_rating;
    }

    public function context() : \context_module {
        return $this->context;
    }

    public function course_module() : \stdClass {
        return $this->course_module;
    }

    public function course_module_id() : int {
        return $this->course_module->id;
    }

    public function course() : \stdClass {
        return $this->course_db_entry;
    }

    public function require_student_capability() : void {
        require_capability('mod/capquiz:student', $this->context);
    }

    public function require_instructor_capability() : void {
        require_capability('mod/capquiz:instructor', $this->context);
    }

    public function require_capability(string $capability) : void {
        require_capability($capability, $this->context);
    }

    public function has_capability(string $capability) : bool {
        return has_capability($capability, $this->context);
    }

    public function user_has_capability(string $capability, int $user_id) : bool {
        return has_capability($capability, $this->context, $user_id);
    }

    public function configure(\stdClass $configuration) : void {
        global $DB;
        $db_entry = $this->capquiz_db_entry;
        if ($name = $configuration->name) {
            $db_entry->name = $name;
        }
        if ($default_user_rating = $configuration->default_user_rating) {
            $db_entry->default_user_rating = $default_user_rating;
        }
        try {
            $DB->update_record(database_meta::$table_capquiz, $db_entry);
            $this->capquiz_db_entry = $db_entry;
        } catch (\dml_exception $e) {
        }
    }

    private function create_question_usage() : int {
        $question_usage = \question_engine::make_questions_usage_by_activity('mod_capquiz', $this->context());
        $question_usage->set_preferred_behaviour('immediatefeedback');
        // TODO: Don't suppress the error if it becomes possible to save QUBAs without slots.
        @\question_engine::save_questions_usage_by_activity($question_usage);
        return $question_usage->get_id();
    }

    private function validate_matchmaking_and_rating_systems() : void {
        if (!$this->rating_system_loader()->has_rating_system()) {
            $this->rating_system_loader()->set_default_rating_system();
        }
        if (!$this->selection_strategy_loader()->has_strategy()) {
            $this->selection_strategy_loader()->set_default_strategy();
        }
    }
}
