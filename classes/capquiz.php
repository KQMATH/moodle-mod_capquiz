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
 * This file defines the class capquiz representing a capquiz
 *
 * @package     mod_capquiz
 * @author      Sebastian S. Gundersen <sebastian@sgundersen.com>
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2019 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz;

use context_module;
use moodle_page;
use renderer_base;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/capquiz_rating_system_loader.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/capquiz_matchmaking_strategy_loader.php');

/**
 * Class capquiz
 *
 * @package     mod_capquiz
 * @author      Sebastian S. Gundersen <sebastian@sgundersen.com>
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2019 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz {

    /** @var context_module $context */
    private $context;

    /** @var stdClass $cm */
    private $cm;

    /** @var stdClass $courserecord */
    private $courserecord;

    /** @var stdClass $record */
    private $record;

    /** @var renderer_base $renderer */
    private renderer_base $renderer;

    /** @var ?capquiz_question_list $qlist */
    private ?capquiz_question_list $qlist;

    /** @var moodle_page $page */
    private moodle_page $page;

    /**
     * Constructor.
     *
     * @param int $cmid
     */
    public function __construct(int $cmid) {
        global $DB, $PAGE;
        $this->cm = get_coursemodule_from_id('capquiz', $cmid, 0, false, MUST_EXIST);
        $this->context = context_module::instance($cmid);
        $PAGE->set_context($this->context);
        $this->renderer = $PAGE->get_renderer('mod_capquiz');
        $this->courserecord = $DB->get_record('course', ['id' => $this->cm->course], '*', MUST_EXIST);
        $this->record = $DB->get_record('capquiz', ['id' => $this->cm->instance], '*', MUST_EXIST);
        $this->qlist = capquiz_question_list::load_question_list($this);
        $this->page = $PAGE;
    }

    /**
     * Updates the grades if the grading is completed or if forced
     *
     * @param bool $force
     */
    public function update_grades(bool $force = false): void {
        if (!$this->is_grading_completed() || $force) {
            capquiz_update_grades($this->record);
        }
    }

    /**
     * Returns the page of the CapQuiz
     */
    public function get_page(): moodle_page {
        return $this->page;
    }

    /**
     * Returns the capquiz' id
     */
    public function id(): int {
        return $this->record->id;
    }

    /**
     * Returns the capquiz' name
     */
    public function name(): string {
        return $this->record->name;
    }

    /**
     * Returns true if the capquiz is published
     */
    public function is_published(): bool {
        return $this->record->published;
    }

    /**
     * Returns true if the capquiz is completely graded
     */
    public function is_grading_completed(): bool {
        return $this->record->timedue < time() && $this->record->timedue > 0;
    }

    /**
     * Returns the amount of stars needed to pass
     */
    public function stars_to_pass(): int {
        return $this->record->stars_to_pass;
    }

    /**
     * Returns the time when the quiz is due
     */
    public function time_due(): int {
        return $this->record->timedue;
    }

    /**
     * Sets a new value for stars to pass
     *
     * @param int $stars
     */
    public function set_stars_to_pass(int $stars): void {
        global $DB;
        $this->record->stars_to_pass = $stars;
        $DB->update_record('capquiz', $this->record);
    }

    /**
     * Sets a new due time
     *
     * @param int $time
     */
    public function set_time_due(int $time): void {
        global $DB;
        $this->record->timedue = $time;
        $DB->update_record('capquiz', $this->record);
    }

    /**
     * Sets a new default rating
     *
     * @param float $rating
     */
    public function set_default_user_rating(float $rating): void {
        global $DB;
        $this->record->default_user_rating = $rating;
        $DB->update_record('capquiz', $this->record);
    }

    /**
     * Publishes the capquiz if it can publish it
     */
    public function publish(): bool {
        global $DB;
        if (!$this->can_publish()) {
            return false;
        }
        $this->record->published = true;
        $DB->update_record('capquiz', $this->record);
        return $this->is_published();
    }

    /**
     * Returns true if the capquiz is publishable
     */
    public function can_publish(): bool {
        if (!$this->has_question_list() || $this->is_published()) {
            return false;
        }
        return $this->question_list()->has_questions();
    }

    /**
     * Returns a new question engine based on the user
     *
     * @param capquiz_user $user
     */
    public function question_engine(capquiz_user $user): ?capquiz_question_engine {
        $quba = $user->question_usage();
        if (!$quba) {
            return null;
        }
        $ratingsystemloader = new capquiz_rating_system_loader($this);
        $strategyloader = new capquiz_matchmaking_strategy_loader($this);
        return new capquiz_question_engine($this, $quba, $strategyloader, $ratingsystemloader);
    }

    /**
     * Returns the capquiz user
     */
    public function user(): ?capquiz_user {
        global $USER;
        return capquiz_user::load_user($this, $USER->id, $this->context());
    }

    /**
     * Returns the default rating
     */
    public function default_user_rating(): float {
        return $this->record->default_user_rating;
    }

    /**
     * Returns true if the capquiz has a question list
     */
    public function has_question_list(): bool {
        return $this->qlist !== null;
    }

    /**
     * Returns the quiz' question list
     */
    public function question_list(): ?capquiz_question_list {
        return $this->qlist;
    }

    /**
     * Returns the current context
     */
    public function context(): context_module {
        return $this->context;
    }

    /**
     * Returns teh current course module
     */
    public function course_module(): stdClass {
        return $this->cm;
    }

    /**
     * Returns the current course
     */
    public function course(): stdClass {
        return $this->courserecord;
    }

    /**
     * Returns the current renderer
     */
    public function renderer(): renderer_base {
        return $this->renderer;
    }

    /**
     * Validates the matchmaking and rating systems
     */
    public function validate_matchmaking_and_rating_systems(): void {
        $ratingsystemloader = new capquiz_rating_system_loader($this);
        if (!$ratingsystemloader->has_rating_system()) {
            $ratingsystemloader->set_default_rating_system();
        }
        $strategyloader = new capquiz_matchmaking_strategy_loader($this);
        if (!$strategyloader->has_strategy()) {
            $strategyloader->set_default_strategy();
        }
    }

}
