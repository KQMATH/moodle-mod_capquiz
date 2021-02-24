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
 * This file defines a class that represents a capquiz url
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @author      Sebastian S. Gundersen <sebastian@sgundersen.com>
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz;

use coding_exception;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/capquiz/report/reportlib.php');

/**
 * Class capquiz_urls
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @author      Sebastian S. Gundersen <sebastian@sgundersen.com>
 * @author      André Storhaug <andr3.storhaug@gmail.com>
 * @copyright   2019 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capquiz_urls {

    public static $urlview = '/mod/capquiz/view.php';
    public static $urlasync = '/mod/capquiz/async.php';
    public static $urlerror = '/mod/capquiz/error.php';
    public static $urlaction = '/mod/capquiz/action.php';
    public static $urlviewclasslist = '/mod/capquiz/view_classlist.php';
    public static $urlviewgrading = '/mod/capquiz/view_grading.php';
    public static $urlviewimport = '/mod/capquiz/view_import.php';
    public static $urlviewreport = '/mod/capquiz/view_report.php';
    public static $urledit = '/mod/capquiz/edit.php';
    public static $urlviewcreateqlist = '/mod/capquiz/view_create_question_list.php';
    public static $urlviewratingsystemconfig = '/mod/capquiz/view_rating_system.php';

    /**
     * Returns a redirect url
     *
     * @param moodle_url $target
     * @return moodle_url
     * @throws coding_exception
     */
    public static function redirect(moodle_url $target): moodle_url {
        $url = self::create_view_url(self::$urlaction);
        $url->param('action', 'redirect');
        $url->param('target-url', $target->out_as_local_url());
        return $url;
    }

    /**
     * @param string $relativeurl
     * @return moodle_url
     * @throws \moodle_exception
     * @throws coding_exception
     */
    public static function create_view_url(string $relativeurl): moodle_url {
        global $CFG;
        $url = new moodle_url($CFG->wwwroot . $relativeurl);
        $url->param('id', self::require_course_module_id_param());
        return $url;
    }

    /**
     * @return int
     * @throws coding_exception
     */
    public static function require_course_module_id_param(): int {
        $id = optional_param('id', 0, PARAM_INT);
        if ($id !== 0) {
            return $id;
        }
        return required_param('cmid', PARAM_INT);
    }

    /**

     * @throws \moodle_exception
     */
    public static function redirect_to_front_page() {
        global $CFG;
        redirect(new moodle_url($CFG->wwwroot));
    }

    public static function redirect_to_dashboard() {
        self::redirect_to_url(self::create_view_url(self::$urlview));
    }

    public static function redirect_to_url(moodle_url $url) {
        redirect($url);
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

    public static function view_url(): moodle_url {
        return self::create_view_url(self::$urlview);
    }

    public static function view_question_list_url(int $questionpage = 0): moodle_url {
        $url = self::create_view_url(self::$urledit);
        $url->param('qpage', $questionpage);
        return $url;
    }

    public static function view_rating_system_url(): moodle_url {
        return self::create_view_url(self::$urlviewratingsystemconfig);
    }

    public static function view_grading_url(): moodle_url {
        return self::create_view_url(self::$urlviewgrading);
    }

    public static function view_classlist_url(): moodle_url {
        return self::create_view_url(self::$urlviewclasslist);
    }

    public static function view_create_question_list_url(): moodle_url {
        return self::create_view_url(self::$urlviewcreateqlist);
    }

    public static function view_import_url(): moodle_url {
        return self::create_view_url(self::$urlviewimport);
    }

    public static function view_report_url($mode = ''): moodle_url {
        return self::report_url(self::$urlviewreport, $mode);
    }

    public static function report_url(string $relativeurl, $mode): moodle_url {
        $url = self::create_view_url($relativeurl);
        if ($mode !== '') {
            $url->param('mode', $mode);
        }
        return $url;
    }

    public static function add_question_to_list_url(int $questionid): moodle_url {
        $url = self::create_view_url(self::$urlaction);
        $url->param('action', 'add-question');
        $url->param('question-id', $questionid);
        return $url;
    }

    public static function remove_question_from_list_url(int $questionid): moodle_url {
        $url = self::create_view_url(self::$urlaction);
        $url->param('action', 'remove-question');
        $url->param('question-id', $questionid);
        return $url;
    }

    public static function question_list_publish_url(capquiz_question_list $qlist): moodle_url {
        $url = self::create_view_url(self::$urlaction);
        $url->param('action', 'publish-question-list');
        $url->param('question-list-id', $qlist->id());
        return $url;
    }

    public static function question_list_create_template_url(capquiz_question_list $qlist): moodle_url {
        $url = self::create_view_url(self::$urlaction);
        $url->param('action', 'create-question-list-template');
        $url->param('question-list-id', $qlist->id());
        return $url;
    }

    public static function question_list_select_url(capquiz_question_list $qlist): moodle_url {
        $url = self::create_view_url(self::$urlaction);
        $url->param('action', 'set-question-list');
        $url->param('question-list-id', $qlist->id());
        return $url;
    }

    public static function set_question_rating_url(int $questionid): moodle_url {
        $url = self::create_view_url(self::$urlaction);
        $url->param('action', 'set-question-rating');
        $url->param('question-id', $questionid);
        return $url;
    }

    public static function regrade_all_url(): moodle_url {
        $url = self::create_view_url(self::$urlaction);
        $url->param('action', 'regrade-all');
        return $url;
    }

    public static function merge_qlist(int $qlistid): moodle_url {
        $url = self::create_view_url(self::$urlaction);
        $url->param('action', 'merge_qlist');
        $url->param('qlistid', $qlistid);
        return $url;
    }

    public static function delete_qlist(int $qlistid): moodle_url {
        $url = self::create_view_url(self::$urlaction);
        $url->param('action', 'delete_qlist');
        $url->param('qlistid', $qlistid);
        return $url;
    }

    public static function response_submit_url(capquiz_question_attempt $attempt): moodle_url {
        $url = self::create_view_url(self::$urlasync);
        $url->param('action', 'answered');
        $url->param('attempt', $attempt->id());
        return $url;
    }

    public static function response_reviewed_url(capquiz_question_attempt $attempt): moodle_url {
        $url = self::create_view_url(self::$urlasync);
        $url->param('action', 'reviewed');
        $url->param('attempt', $attempt->id());
        return $url;
    }
}
