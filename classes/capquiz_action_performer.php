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
class capquiz_action_performer {

    public static function perform(string $action, capquiz $capquiz) {
        switch ($action) {
            case capquiz_actions::$redirect:
                self::redirect();
                break;
            case capquiz_actions::$setquestionlist:
                self::assign_question_list($capquiz);
                break;
            case capquiz_actions::$addquestion:
                self::add_question_to_list($capquiz);
                break;
            case capquiz_actions::$removequestion:
                self::remove_question_from_list($capquiz);
                break;
            case capquiz_actions::$publishquestionlist:
                self::publish_capquiz($capquiz);
                break;
            case capquiz_actions::$setquestionrating:
                self::set_question_rating($capquiz);
                break;
            case capquiz_actions::$setdefaultqrating:
                self::set_default_question_rating($capquiz);
                break;
            case capquiz_actions::$createqlisttemplate:
                self::create_question_list_template($capquiz);
                break;
            case 'merge_qlist':
                self::merge_question_list($capquiz);
                break;
            case 'delete_qlist':
                self::delete_question_list();
                break;
            default:
                break;
        }
    }

    public static function redirect() {
        $url = optional_param(capquiz_urls::$paramtargeturl, null, PARAM_TEXT);
        if ($url) {
            capquiz_urls::redirect_to_url(new \moodle_url($url));
        }
    }

    public static function assign_question_list(capquiz $capquiz) {
        $qlistid = optional_param(capquiz_urls::$paramqlistid, 0, PARAM_INT);
        $qlist = capquiz_question_list::load_any($qlistid);
        if ($qlist) {
            $capquiz->validate_matchmaking_and_rating_systems();
            $qlist->create_instance_copy($capquiz);
        }
    }

    public static function add_question_to_list(capquiz $capquiz) {
        $qlist = $capquiz->question_list();
        $questionid = optional_param(capquiz_urls::$paramquestionid, 0, PARAM_INT);
        if ($questionid) {
            self::create_capquiz_question($questionid, $qlist, $qlist->default_question_rating());
        }
        capquiz_urls::redirect_to_previous();
    }

    public static function remove_question_from_list(capquiz $capquiz) {
        $questionid = optional_param(capquiz_urls::$paramquestionid, 0, PARAM_INT);
        if ($questionid && $capquiz->has_question_list()) {
            self::remove_capquiz_question($questionid, $capquiz->question_list()->id());
        }
        capquiz_urls::redirect_to_previous();
    }

    public static function publish_capquiz(capquiz $capquiz) {
        $capquiz->publish();
    }

    public static function set_question_rating(capquiz $capquiz) {
        $questionid = required_param(capquiz_urls::$paramquestionid, PARAM_INT);
        $question = $capquiz->question_list()->question($questionid);
        if (!$question) {
            throw new \Exception('The specified question does not exist');
        }
        $rating = optional_param(capquiz_urls::$paramrating, null, PARAM_FLOAT);
        if ($rating !== null) {
            $question->set_rating($rating);
        }
        capquiz_urls::redirect_to_url(capquiz_urls::view_question_list_url());
    }

    public static function set_default_question_rating(capquiz $capquiz) {
        $rating = optional_param(capquiz_urls::$paramrating, null, PARAM_FLOAT);
        if ($rating !== null) {
            $capquiz->question_list()->set_default_question_rating($rating);
        }
        capquiz_urls::redirect_to_url(capquiz_urls::view_question_list_url());
    }

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

    private static function create_capquiz_question(int $questionid, capquiz_question_list $list, float $rating) {
        global $DB;
        $ratedquestion = new \stdClass();
        $ratedquestion->question_list_id = $list->id();
        $ratedquestion->question_id = $questionid;
        $ratedquestion->rating = $rating;
        $DB->insert_record('capquiz_question', $ratedquestion);
    }

    private static function remove_capquiz_question(int $questionid, int $qlistid) {
        global $DB;
        $DB->delete_records('capquiz_question', ['id' => $questionid, 'question_list_id' => $qlistid]);
    }

    private static function merge_question_list(capquiz $capquiz) {
        global $DB;
        $srcqlistid = required_param('qlistid', PARAM_INT);
        $srcqlistrecord = $DB->get_record('capquiz_question_list', ['id' => $srcqlistid]);
        if ($srcqlistrecord) {
            $capquiz->question_list()->merge(new capquiz_question_list($srcqlistrecord));
        }
        capquiz_urls::redirect_to_url(capquiz_urls::view_question_list_url());
    }

    private static function delete_question_list() {
        global $DB;
        $srcqlistid = required_param('qlistid', PARAM_INT);
        $DB->delete_records('capquiz_question', ['question_list_id' => $srcqlistid]);
        $DB->delete_records('capquiz_question_list', ['id' => $srcqlistid]);
        capquiz_urls::redirect_to_url(capquiz_urls::view_import_url());
    }

}
