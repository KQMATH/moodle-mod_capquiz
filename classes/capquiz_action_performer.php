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
 * This file defines a class used to perform different actions on the capquiz
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @author      Sebastian S. Gundersen <sebastian@sgundersen.com>
 * @copyright   2019 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/capquiz_rating_system_loader.php');
require_once($CFG->dirroot . '/mod/capquiz/classes/capquiz_matchmaking_strategy_loader.php');

/**
 * Class capquiz_action_performer
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @author      Sebastian S. Gundersen <sebastian@sgundersen.com>
 * @copyright   2019 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_action_performer {

    /**
     * Performs an action on a capquiz
     *
     * @param string $action The action to perform
     * @param capquiz $capquiz The capquiz on which the action will be performed
     * @throws \Exception
     */
    public static function perform(string $action, capquiz $capquiz) {
        switch ($action) {
            case 'redirect':
                self::redirect();
                break;
            case 'set-question-list':
                self::assign_question_list($capquiz);
                break;
            case 'add-question':
                self::add_question_to_list($capquiz);
                break;
            case 'remove-question':
                self::remove_question_from_list($capquiz);
                break;
            case 'publish-question-list':
                self::publish_capquiz($capquiz);
                break;
            case 'set-question-rating':
                self::set_question_rating($capquiz);
                break;
            case 'set-default-question-rating':
                self::set_default_question_rating($capquiz);
                break;
            case 'create-question-list-template':
                self::create_question_list_template($capquiz);
                break;
            case 'merge_qlist':
                self::merge_question_list($capquiz);
                break;
            case 'delete_qlist':
                self::delete_question_list();
                break;
            case 'regrade-all':
                $capquiz->update_grades(true);
                capquiz_urls::redirect_to_url(capquiz_urls::view_classlist_url());
                break;
            default:
                break;
        }
    }

    /**
     * Redirects the user to another page
     *
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public static function redirect() {
        $url = optional_param('target-url', null, PARAM_TEXT);
        if ($url) {
            capquiz_urls::redirect_to_url(new \moodle_url($url));
        }
    }

    /**
     * Assigns a question list to the capquiz
     *
     * @param capquiz $capquiz
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function assign_question_list(capquiz $capquiz) {
        $qlistid = optional_param('question-list-id', 0, PARAM_INT);
        $qlist = capquiz_question_list::load_any($qlistid, $capquiz->context());
        if ($qlist) {
            $capquiz->validate_matchmaking_and_rating_systems();
            $qlist->create_instance_copy($capquiz);
        }
    }

    /**
     * Adds questions from the capquiz' question to the capquiz
     *
     * @param capquiz $capquiz
     * @throws \coding_exception
     */
    public static function add_question_to_list(capquiz $capquiz) {
        $qlist = $capquiz->question_list();
        $questionids = required_param('question-id', PARAM_TEXT);
        $questionids = explode(',', $questionids);
        foreach ($questionids as $questionid) {
            self::create_capquiz_question((int)$questionid, $qlist, $qlist->default_question_rating());
        }
        capquiz_urls::redirect_to_previous();
    }

    /**
     * Removes a question
     *
     * @param capquiz $capquiz
     * @throws \coding_exception
     */
    public static function remove_question_from_list(capquiz $capquiz) {
        $questionid = optional_param('question-id', 0, PARAM_INT);
        if ($questionid && $capquiz->has_question_list()) {
            self::remove_capquiz_question($questionid, $capquiz->question_list()->id());
        }
        capquiz_urls::redirect_to_previous();
    }

    /**
     * Publishes the capquiz
     *
     * @param capquiz $capquiz
     * @throws \dml_exception
     */
    public static function publish_capquiz(capquiz $capquiz) {
        $capquiz->publish();
    }

    /**
     * Sets a new question rating on a question
     *
     * @param capquiz $capquiz
     * @throws \coding_exception
     */
    public static function set_question_rating(capquiz $capquiz) {
        $questionid = required_param('question-id', PARAM_INT);
        $question = $capquiz->question_list()->question($questionid);
        if (!$question) {
            throw new \Exception('The specified question does not exist');
        }
        $rating = optional_param('rating', null, PARAM_FLOAT);
        if ($rating !== null) {
            $question->set_rating($rating, true);
        }
        capquiz_urls::redirect_to_url(capquiz_urls::view_question_list_url());
    }

    /**
     * Sets a new default question rating
     *
     * @param capquiz $capquiz
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function set_default_question_rating(capquiz $capquiz) {
        $rating = optional_param('rating', null, PARAM_FLOAT);
        if ($rating !== null) {
            $capquiz->question_list()->set_default_question_rating($rating);
        }
        capquiz_urls::redirect_to_url(capquiz_urls::view_question_list_url());
    }

    /**
     * Creates a template from the capquiz' current question list
     *
     * @param capquiz $capquiz
     * @return capquiz_question_list
     * @throws \Exception
     */
    public static function create_question_list_template(capquiz $capquiz) {
        $qlist = $capquiz->question_list();
        if (!$qlist) {
            throw new \Exception('Failed to find question list for this CAPQuiz.');
        } else if (!$qlist->has_questions()) {
            throw new \Exception('Attempted to create template without questions.');
        }
        $qlistcopy = $qlist->create_template_copy($capquiz);
        if ($qlistcopy === null) {
            throw new \Exception('Failed to create a template from this question list.');
        }
        return $qlistcopy;
    }

    /**
     * Creates a new capquiz question and adds it to the database
     *
     * @param int $questionid
     * @param capquiz_question_list $list
     * @param float $rating
     * @throws \dml_exception
     */
    private static function create_capquiz_question(int $questionid, capquiz_question_list $list, float $rating) {
        global $DB;
        if ($questionid === 0) {
            return;
        }
        $ratedquestion = new \stdClass();
        $ratedquestion->question_list_id = $list->id();
        $ratedquestion->question_id = $questionid;
        $ratedquestion->rating = $rating;
        $capquizquestionid = $DB->insert_record('capquiz_question', $ratedquestion, true);
        capquiz_question_rating::insert_question_rating_entry($capquizquestionid, $rating);
    }

    /**
     * Removes matching questions from the database
     *
     * @param int $questionid
     * @param int $qlistid
     * @throws \dml_exception
     */
    private static function remove_capquiz_question(int $questionid, int $qlistid) {
        global $DB;
        $DB->delete_records('capquiz_question', ['id' => $questionid, 'question_list_id' => $qlistid]);
    }

    /**
     * Merges the capquiz' question list to its current context
     *
     * @param capquiz $capquiz
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private static function merge_question_list(capquiz $capquiz) {
        global $DB;
        $srcqlistid = required_param('qlistid', PARAM_INT);
        $srcqlistrecord = $DB->get_record('capquiz_question_list', ['id' => $srcqlistid]);
        if ($srcqlistrecord) {
            $capquiz->question_list()->merge(new capquiz_question_list($srcqlistrecord, $capquiz->context()));
        }
        capquiz_urls::redirect_to_url(capquiz_urls::view_question_list_url());
    }

    /**
     * Deletes the question list and its questions from the database
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private static function delete_question_list() {
        global $DB;
        $srcqlistid = required_param('qlistid', PARAM_INT);
        $DB->delete_records('capquiz_question', ['question_list_id' => $srcqlistid]);
        $DB->delete_records('capquiz_question_list', ['id' => $srcqlistid]);
        capquiz_urls::redirect_to_url(capquiz_urls::view_import_url());
    }

}
