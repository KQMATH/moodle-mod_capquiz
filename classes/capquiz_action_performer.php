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
class capquiz_action_performer {

    public static function perform(string $action, capquiz $capquiz) {
        switch ($action) {
            case capquiz_actions::$redirect:
                self::redirect();
                break;
            case capquiz_actions::$set_question_list:
                self::assign_question_list($capquiz);
                break;
            case capquiz_actions::$add_question_to_list:
                self::add_question_to_list($capquiz);
                break;
            case capquiz_actions::$remove_question_from_list:
                self::remove_question_from_list($capquiz);
                break;
            case capquiz_actions::$publish_question_list:
                self::publish_capquiz($capquiz);
                break;
            case capquiz_actions::$set_question_rating:
                self::set_question_rating($capquiz);
                break;
            case capquiz_actions::$create_question_list_template:
                self::create_question_list_template($capquiz);
                break;
            default:
                break;
        }
    }

    public static function redirect() {
        $url = optional_param(capquiz_urls::$param_target_url, null, PARAM_TEXT);
        if ($url) {
            capquiz_urls::redirect_to_url(new \moodle_url($url));
        }
    }

    public static function assign_question_list(capquiz $capquiz) {
        $qlistid = optional_param(capquiz_urls::$param_question_list_id, 0, PARAM_INT);
        $qlist = capquiz_question_list::load_any($qlistid);
        if ($qlist) {
            $capquiz->validate_matchmaking_and_rating_systems();
            $qlist->create_instance_copy($capquiz->id());
        }
    }

    public static function add_question_to_list(capquiz $capquiz) {
        $qlist = $capquiz->question_list();
        $questionid = optional_param(capquiz_urls::$param_question_id, 0, PARAM_INT);
        if ($questionid) {
            self::create_capquiz_question($questionid, $qlist, $qlist->default_question_rating());
        }
        capquiz_urls::redirect_to_previous();
    }

    public static function remove_question_from_list(capquiz $capquiz) {
        $question_id = optional_param(capquiz_urls::$param_question_id, 0, PARAM_INT);
        if ($question_id && $capquiz->has_question_list()) {
            self::remove_capquiz_question($question_id, $capquiz->question_list()->id());
        }
        capquiz_urls::redirect_to_previous();
    }

    public static function publish_capquiz(capquiz $capquiz) {
        $capquiz->publish();
    }

    public static function set_question_rating(capquiz $capquiz) {
        $question_id = required_param(capquiz_urls::$param_question_id, PARAM_INT);
        $question = $capquiz->question_list()->question($question_id);
        if (!$question) {
            throw new \Exception('The specified question does not exist');
        }
        $rating = optional_param(capquiz_urls::$param_rating, null, PARAM_FLOAT);
        if ($rating !== null) {
            $question->set_rating($rating);
        }
        capquiz_urls::redirect_to_url(capquiz_urls::view_question_list_url());
    }

    public static function create_question_list_template(capquiz $capquiz) {
        $qlist = $capquiz->question_list();
        if (!$qlist) {
            throw new \Exception('Failed to find question list for this CAPQuiz.');
        } else if (!$qlist->can_create_template()) {
            throw new \Exception('Attempted to create template without questions.');
        }
        $qlistcopy = $qlist->create_template_copy();
        if ($qlistcopy === null) {
            throw new \Exception('Failed to create a template from this question list.');
        }
        return $qlistcopy;
    }

    private static function create_capquiz_question(int $question_id, capquiz_question_list $list, float $rating) {
        global $DB;
        $rated_question = new \stdClass();
        $rated_question->question_list_id = $list->id();
        $rated_question->question_id = $question_id;
        $rated_question->rating = $rating;
        $DB->insert_record(database_meta::$table_capquiz_question, $rated_question);
    }

    private static function remove_capquiz_question(int $question_id, int $question_list_id) {
        global $DB;
        $conditions = [
            database_meta::$field_id => $question_id,
            database_meta::$field_question_list_id => $question_list_id
        ];
        $DB->delete_records(database_meta::$table_capquiz_question, $conditions);
    }

}
