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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/capquiz_rating_system_loader.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/capquiz_matchmaking_strategy_loader.php');

/**
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz {

    /** @var \context_module $context */
    private $context;

    /** @var \stdClass $cm */
    private $cm;

    /** @var \stdClass $courserecord */
    private $courserecord;

    /** @var \stdClass $record */
    private $record;

    /** @var \renderer_base|output\renderer $renderer */
    private $renderer;

    /** @var capquiz_question_list $qlist */
    private $qlist;

    public function __construct(int $cmid) {
        global $DB, $PAGE;
        $this->cm = get_coursemodule_from_id('capquiz', $cmid, 0, false, MUST_EXIST);
        $this->context = \context_module::instance($cmid);
        $PAGE->set_context($this->context);
        $this->renderer = $PAGE->get_renderer('mod_capquiz');
        $this->courserecord = $DB->get_record('course', ['id' => $this->cm->course], '*', MUST_EXIST);
        $this->record = $DB->get_record('capquiz', ['id' => $this->cm->instance], '*', MUST_EXIST);
        $this->qlist = capquiz_question_list::load_question_list($this);
    }

    /**
     * @throws \coding_exception if no id/cmid param
     */
    public static function create() : capquiz {
        return self::create_from_id(capquiz_urls::require_course_module_id_param());
    }

    public function update_grades() {
        if (!$this->is_grading_completed()) {
            capquiz_update_grades($this->record);
        }
    }

    public static function create_from_id(int $id) : capquiz {
        return new capquiz($id);
    }

    public function id() : int {
        return $this->record->id;
    }

    public function is_published() : bool {
        return $this->record->published;
    }

    public function stars_to_pass() : int {
        return $this->record->stars_to_pass;
    }

    public function set_stars_to_pass(int $stars) {
        global $DB;
        $this->record->stars_to_pass = $stars;
        $DB->update_record('capquiz', $this->record);
    }

    public function time_due() : int {
        return $this->record->timedue;
    }

    public function set_time_due(int $time) {
        global $DB;
        $this->record->timedue = $time;
        $DB->update_record('capquiz', $this->record);
    }

    public function is_grading_completed() : bool {
        return $this->record->timedue < time() && $this->record->timedue > 0;
    }

    public function can_publish() : bool {
        if (!$this->has_question_list() || $this->is_published()) {
            return false;
        }
        return $this->question_list()->has_questions();
    }

    public function publish() : bool {
        global $DB;
        if (!$this->can_publish()) {
            return false;
        }
        $this->question_list()->create_question_usage($this->context());
        $this->record->published = true;
        $DB->update_record('capquiz', $this->record);
        return $this->is_published();
    }

    public function renderer() : \renderer_base {
        return $this->renderer;
    }

    public function output() : \renderer_base {
        return $this->renderer->output_renderer();
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
        return new capquiz_rating_system_registry();
    }

    public function question_engine() {
        $quba = $this->question_usage();
        if (!$quba) {
            return null;
        }
        return new capquiz_question_engine($this, $quba, $this->selection_strategy_loader(), $this->rating_system_loader());
    }

    public function has_question_list() : bool {
        return $this->qlist !== null;
    }

    public function question_list() {
        return $this->qlist;
    }

    public function question_usage() {
        if ($this->has_question_list() && $this->is_published()) {
            return $this->question_list()->question_usage();
        }
        return null;
    }

    public function user() : capquiz_user {
        global $USER;
        return capquiz_user::load_user($this, $USER->id);
    }

    public function default_user_rating() : float {
        return $this->record->default_user_rating;
    }

    public function context() : \context_module {
        return $this->context;
    }

    public function course_module() : \stdClass {
        return $this->cm;
    }

    public function course_module_id() : int {
        return $this->cm->id;
    }

    public function course() : \stdClass {
        return $this->courserecord;
    }

    public function set_default_user_rating(float $rating) {
        global $DB;
        $this->record->default_user_rating = $rating;
        $DB->update_record('capquiz', $this->record);
    }

    public function validate_matchmaking_and_rating_systems() {
        if (!$this->rating_system_loader()->has_rating_system()) {
            $this->rating_system_loader()->set_default_rating_system();
        }
        if (!$this->selection_strategy_loader()->has_strategy()) {
            $this->selection_strategy_loader()->set_default_strategy();
        }
    }

}
