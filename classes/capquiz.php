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
require_once($CFG->dirroot . '/mod/capquiz/classes/rating_system/default_elo_rating_system.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/question_selectors/adaptive_question_selector.php');

defined('MOODLE_INTERNAL') || die();

class capquiz {

    private $context;
    private $course_module;
    private $course_db_entry;
    private $capquiz_db_entry;

    /**
     * @var output\renderer
     */
    private $capquiz_renderer;

    public function __construct(int $course_module_id) {
        global $DB;
        global $PAGE;

        $this->course_module = get_coursemodule_from_id('capquiz', $course_module_id, 0, false, MUST_EXIST);

        require_login($this->course_module->course, false, $this->course_module);

        $this->context = \context_module::instance($course_module_id);
        $PAGE->set_context($this->context);
        $this->capquiz_renderer = $PAGE->get_renderer('mod_capquiz');

        $this->course_db_entry = $DB->get_record('course', ['id' => $this->course_module->course], '*', MUST_EXIST);
        $this->capquiz_db_entry = $DB->get_record('capquiz', ['id' => $this->course_module->instance], '*', MUST_EXIST);
    }

    public function id() {
        return $this->capquiz_db_entry->id;
    }

    public function name() {
        return $this->capquiz_db_entry->name;
    }

    public function is_published() {
        return $this->capquiz_db_entry->published;
    }

    public function is_student() {
        return $this->has_capability('mod/capquiz:student');
    }

    public function is_instructor() {
        return $this->has_capability('mod/capquiz:instructor');
    }

    public function question_list_id() {
        return $this->capquiz_db_entry->question_list_id;
    }

    public function has_question_list() {
        return $this->capquiz_db_entry->question_list_id != null;
    }

    public function assign_question_list(int $question_list_id) {
        global $DB;
        $capquiz_entry = $this->capquiz_db_entry;
        $capquiz_entry->question_list_id = $question_list_id;
        if ($DB->update_record(database_meta::$table_capquiz, $capquiz_entry)) {
            $this->capquiz_db_entry = $capquiz_entry;
        }
    }

    public function publish() {
        global $DB;
        $capquiz_entry = $this->capquiz_db_entry;
        $capquiz_entry->published = true;
        $capquiz_entry->question_usage_id = $this->create_question_usage();
        if ($DB->update_record(database_meta::$table_capquiz, $capquiz_entry)) {
            $this->capquiz_db_entry = $capquiz_entry;
        }
        return $this->is_published();
    }

    public function renderer() {
        return $this->capquiz_renderer;
    }

    public function output() {
        return $this->capquiz_renderer->output_renderer();
    }

    public function question_engine() {
        if ($question_usage = $this->question_usage()) {
            return new capquiz_question_engine($this, $question_usage, $this->question_selector(), $this->rating_system());
        }
        return null;
    }

    public function question_registry() {
        return new capquiz_question_registry($this);
    }

    public function question_list() {
        if ($this->has_question_list()) {
            return $this->question_registry()->question_list($this->question_list_id());
        }
        return null;
    }

    public function question_usage() {
        if (!$this->has_question_list()) {
            return null;
        }
        if (!$this->is_published()) {
            return null;
        }
        return \question_engine::load_questions_usage_by_activity($this->capquiz_db_entry->question_usage_id);
    }

    public function user() {
        global $USER;
        return capquiz_user::load_user($this, $USER->id);
    }

    public function default_user_k_factor() {
        return $this->capquiz_db_entry->default_user_k_factor;
    }

    public function default_question_k_factor() {
        return $this->capquiz_db_entry->default_question_k_factor;
    }

    public function default_user_rating() {
        return $this->capquiz_db_entry->default_user_rating;
    }

    public function default_question_rating() {
        return $this->capquiz_db_entry->default_question_rating;
    }

    public function context() {
        return $this->context;
    }

    public function course_module() {
        return $this->course_module;
    }

    public function course_module_id() {
        return $this->course_module->id;
    }

    public function require_instructor_capability() {
        require_capability('mod/capquiz:instructor', $this->context);
    }

    public function require_capability(string $capability) {
        require_capability($capability, $this->context);
    }

    public function has_capability(string $capability) {
        return has_capability($capability, $this->context);
    }

    public function user_has_capability(string $capability, int $user_id) {
        return has_capability($capability, $this->context, $user_id);
    }

    private function create_question_usage() {
        $question_usage = \question_engine::make_questions_usage_by_activity('mod_capquiz', $this->context());
        $question_usage->set_preferred_behaviour('immediatefeedback');
        \question_engine::save_questions_usage_by_activity($question_usage);
        return $question_usage->get_id();
    }

    private function question_selector() {
        return new adaptive_question_selector($this);
    }

    private function rating_system() {
        return new default_elo_rating_system($this->default_user_k_factor(), $this->default_question_k_factor());
    }
}
