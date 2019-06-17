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

/**
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @copyright   2018 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_urls {

    public static $paramid = 'id';
    public static $paramcmid = 'cmid';
    public static $paramrating = 'rating';
    public static $paramattempt = 'attempt';
    public static $paramtargeturl = 'target-url';
    public static $paramquestionid = 'question-id';
    public static $paramquestionpage = 'qpage';
    public static $paramdeleteselected = 'deleteselected';
    public static $paramqlistid = 'question-list-id';

    public static $urlview = '/mod/capquiz/view.php';
    public static $urlasync = '/mod/capquiz/async.php';
    public static $urlerror = '/mod/capquiz/error.php';
    public static $urlaction = '/mod/capquiz/action.php';
    public static $urlviewclasslist = '/mod/capquiz/view_classlist.php';
    public static $urlviewconfig = '/mod/capquiz/view_configuration.php';
    public static $urlviewcomments = '/mod/capquiz/view_comments.php';
    public static $urlviewimport = '/mod/capquiz/view_import.php';
    public static $urledit = '/mod/capquiz/edit.php';
    public static $urlviewbadgeconfig = '/mod/capquiz/view_badge_configuration.php';
    public static $urlviewcreateqlist = '/mod/capquiz/view_create_question_list.php';
    public static $urlviewmatchmakingconfig = '/mod/capquiz/view_matchmaking_configuration.php';
    public static $urlviewratingsystemconfig = '/mod/capquiz/view_rating_system_configuration.php';

    public static function redirect(\moodle_url $target) {
        $url = self::create_view_url(self::$urlaction);
        $url->param(capquiz_actions::$parameter, capquiz_actions::$redirect);
        $url->param(self::$paramtargeturl, $target->out_as_local_url());
        return $url;
    }

    public static function redirect_to_front_page() {
        global $CFG;
        redirect(new \moodle_url($CFG->wwwroot));
    }

    public static function redirect_to_url(\moodle_url $url) {
        redirect($url);
    }

    public static function redirect_to_dashboard() {
        self::redirect_to_url(self::create_view_url(self::$urlview));
    }

    public static function redirect_to_previous() {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    public static function set_page_url(capquiz $capquiz, string $url) {
        global $PAGE;
        $PAGE->set_context($capquiz->context());
        $PAGE->set_cm($capquiz->course_module());
        $PAGE->set_pagelayout('incourse');
        $PAGE->set_url(self::create_view_url($url));
    }

    /**
     * @throws \coding_exception
     */
    public static function require_course_module_id_param() {
        $id = optional_param(self::$paramid, 0, PARAM_INT);
        if ($id !== 0) {
            return $id;
        }
        return required_param(self::$paramcmid, PARAM_INT);
    }

    public static function view_url() {
        return self::create_view_url(self::$urlview);
    }

    public static function view_question_list_url(int $questionpage = 0) {
        $url = self::create_view_url(self::$urledit);
        $url->param(self::$paramquestionpage, $questionpage);
        return $url;
    }

    public static function view_matchmaking_configuration_url() {
        $url = self::create_view_url(self::$urlviewmatchmakingconfig);
        return $url;
    }

    public static function view_rating_system_configuration_url() {
        $url = self::create_view_url(self::$urlviewratingsystemconfig);
        return $url;
    }

    public static function view_badge_configuration_url() {
        $url = self::create_view_url(self::$urlviewbadgeconfig);
        return $url;
    }

    public static function view_classlist_url() {
        return self::create_view_url(self::$urlviewclasslist);
    }

    public static function view_configuration_url() {
        return self::create_view_url(self::$urlviewconfig);
    }

    public static function view_create_question_list_url() {
        return self::create_view_url(self::$urlviewcreateqlist);
    }

    public static function view_comments_url() {
        return self::create_view_url(self::$urlviewcomments);
    }

    public static function view_import_url() {
        return self::create_view_url(self::$urlviewimport);
    }

    public static function add_question_to_list_url(int $questionid) {
        $url = self::create_view_url(self::$urlaction);
        $url->param(capquiz_actions::$parameter, capquiz_actions::$addquestion);
        $url->param(self::$paramquestionid, $questionid);
        return $url;
    }

    public static function remove_question_from_list_url(int $questionid) {
        $url = self::create_view_url(self::$urlaction);
        $url->param(capquiz_actions::$parameter, capquiz_actions::$removequestion);
        $url->param(self::$paramquestionid, $questionid);
        return $url;
    }

    public static function question_list_publish_url(capquiz_question_list $qlist) {
        $url = self::create_view_url(self::$urlaction);
        $url->param(self::$paramqlistid, $qlist->id());
        $url->param(capquiz_actions::$parameter, capquiz_actions::$publishquestionlist);
        return $url;
    }

    public static function question_list_create_template_url(capquiz_question_list $qlist) {
        $url = self::create_view_url(self::$urlaction);
        $url->param(self::$paramqlistid, $qlist->id());
        $url->param(capquiz_actions::$parameter, capquiz_actions::$createqlisttemplate);
        return $url;
    }

    public static function question_list_select_url(capquiz_question_list $qlist) {
        $url = self::create_view_url(self::$urlaction);
        $url->param(capquiz_actions::$parameter, capquiz_actions::$setquestionlist);
        $url->param(self::$paramqlistid, $qlist->id());
        return $url;
    }

    public static function set_question_rating_url(int $questionid) {
        $url = self::create_view_url(self::$urlaction);
        $url->param(self::$paramquestionid, $questionid);
        $url->param(capquiz_actions::$parameter, capquiz_actions::$setquestionrating);
        return $url;
    }

    public static function merge_qlist(int $qlistid) {
        $url = self::create_view_url(self::$urlaction);
        $url->param('qlistid', $qlistid);
        $url->param(capquiz_actions::$parameter, 'merge_qlist');
        return $url;
    }

    public static function response_submit_url(capquiz_question_attempt $attempt) {
        $url = self::create_view_url(self::$urlasync);
        $url->param(self::$paramattempt, $attempt->id());
        $url->param(capquiz_actions::$parameter, capquiz_actions::$attemptanswered);
        return $url;
    }

    public static function response_reviewed_url(capquiz_question_attempt $attempt) {
        $url = self::create_view_url(self::$urlasync);
        $url->param(self::$paramattempt, $attempt->id());
        $url->param(capquiz_actions::$parameter, capquiz_actions::$attemptreviewed);
        return $url;
    }

    public static function create_view_url(string $relativeurl) {
        global $CFG;
        $url = new \moodle_url($CFG->wwwroot . $relativeurl);
        $url->param(self::$paramid, self::require_course_module_id_param());
        return $url;
    }

}
