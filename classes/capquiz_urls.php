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

    /** @var string The URL to the entrypoint view for the capquiz */
    public static string $urlview = '/mod/capquiz/view.php';

    /** @var string The URL to update the user attempts and return to the dashboard */
    public static string $urlasync = '/mod/capquiz/async.php';

    /** @var string The URL to the error page */
    public static string $urlerror = '/mod/capquiz/error.php';

    /** @var string The URL to the action page */
    public static string $urlaction = '/mod/capquiz/action.php';

    /** @var string The URL to the classlist view */
    public static string $urlviewclasslist = '/mod/capquiz/view_classlist.php';

    /** @var string The URL to the grading view */
    public static string $urlviewgrading = '/mod/capquiz/view_grading.php';

    /** @var string The URL to the import view */
    public static string $urlviewimport = '/mod/capquiz/view_import.php';

    /** @var string The URL to the report view */
    public static string $urlviewreport = '/mod/capquiz/view_report.php';

    /** @var string The URL for the capquiz editor */
    public static string $urledit = '/mod/capquiz/edit.php';

    /** @var string The URL to the create question list view */
    public static string $urlviewcreateqlist = '/mod/capquiz/view_create_question_list.php';

    /** @var string The URL to the rating system view */
    public static string $urlviewratingsystemconfig = '/mod/capquiz/view_rating_system.php';

    /**
     * Returns a redirect url
     *
     * @param moodle_url $target
     */
    public static function redirect(moodle_url $target): moodle_url {
        $url = self::create_view_url(self::$urlaction);
        $url->param('action', 'redirect');
        $url->param('target-url', $target->out_as_local_url());
        return $url;
    }

    /**
     * Generates a url based on a relative url
     *
     * @param string $relativeurl
     */
    public static function create_view_url(string $relativeurl): moodle_url {
        global $CFG;
        $url = new moodle_url($CFG->wwwroot . $relativeurl);
        $url->param('id', self::require_course_module_id_param());
        return $url;
    }

    /**
     * Returns the course module id
     */
    public static function require_course_module_id_param(): int {
        $id = optional_param('id', 0, PARAM_INT);
        if ($id !== 0) {
            return $id;
        }
        return required_param('cmid', PARAM_INT);
    }

    /**
     * Redirects to the front page
     */
    public static function redirect_to_front_page(): void {
        global $CFG;
        redirect(new moodle_url($CFG->wwwroot));
    }

    /**
     * Redirects to the dashboard
     */
    public static function redirect_to_dashboard(): void {
        self::redirect_to_url(self::create_view_url(self::$urlview));
    }

    /**
     * Redirects to specified url
     *
     * @param moodle_url $url
     */
    public static function redirect_to_url(moodle_url $url): void {
        redirect($url);
    }

    /**
     * Redirects to the previous page
     */
    public static function redirect_to_previous(): void {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    /**
     * Sets teh current page url
     *
     * @param capquiz $capquiz
     * @param string $url
     */
    public static function set_page_url(capquiz $capquiz, string $url): void {
        global $PAGE;
        $PAGE->set_context($capquiz->context());
        $PAGE->set_cm($capquiz->course_module());
        $PAGE->set_pagelayout('incourse');
        $PAGE->set_url(self::create_view_url($url));
    }

    /**
     * Returns url to the front page of the capquiz dashboard
     */
    public static function view_url(): moodle_url {
        return self::create_view_url(self::$urlview);
    }

    /**
     * Returns the url to the question list view
     *
     * @param int $questionpage
     */
    public static function view_question_list_url(int $questionpage = 0): moodle_url {
        $url = self::create_view_url(self::$urledit);
        $url->param('qpage', $questionpage);
        return $url;
    }

    /**
     * Returns the url to the rating system view
     */
    public static function view_rating_system_url(): moodle_url {
        return self::create_view_url(self::$urlviewratingsystemconfig);
    }

    /**
     * Returns the url to the grading view
     */
    public static function view_grading_url(): moodle_url {
        return self::create_view_url(self::$urlviewgrading);
    }

    /**
     * Returns the url to the classlist/leaderboard view
     */
    public static function view_classlist_url(): moodle_url {
        return self::create_view_url(self::$urlviewclasslist);
    }

    /**
     * Returns url to the "create question list" view
     */
    public static function view_create_question_list_url(): moodle_url {
        return self::create_view_url(self::$urlviewcreateqlist);
    }

    /**
     * Returns url to the import view
     */
    public static function view_import_url(): moodle_url {
        return self::create_view_url(self::$urlviewimport);
    }

    /**
     * Returns url to the report view
     *
     * @param string $mode
     */
    public static function view_report_url(string $mode = ''): moodle_url {
        return self::report_url(self::$urlviewreport, $mode);
    }

    /**
     * Generates and returns url to the report view
     *
     * @param string $relativeurl
     * @param string $mode
     */
    public static function report_url(string $relativeurl, string $mode): moodle_url {
        $url = self::create_view_url($relativeurl);
        if ($mode !== '') {
            $url->param('mode', $mode);
        }
        return $url;
    }

    /**
     * Generates and returns url to add a qyestion to the list with
     * the parameters to add question to the list
     *
     * @param int $questionid
     */
    public static function add_question_to_list_url(int $questionid): moodle_url {
        $url = self::create_view_url(self::$urlaction);
        $url->param('action', 'add-question');
        $url->param('question-id', $questionid);
        return $url;
    }

    /**
     * Generates and returns url to remove a question from a list with
     * the parameters to remove question from the list
     *
     * @param int $questionid
     */
    public static function remove_question_from_list_url(int $questionid): moodle_url {
        $url = self::create_view_url(self::$urlaction);
        $url->param('action', 'remove-question');
        $url->param('question-id', $questionid);
        return $url;
    }

    /**
     * Generates and returns url to publish a question list with
     * the parameters to publish the question list
     *
     * @param capquiz_question_list $qlist
     */
    public static function question_list_publish_url(capquiz_question_list $qlist): moodle_url {
        $url = self::create_view_url(self::$urlaction);
        $url->param('action', 'publish-question-list');
        $url->param('question-list-id', $qlist->id());
        return $url;
    }

    /**
     * Generates and returns url to create a question list template with
     * the parameters to create the template
     *
     * @param capquiz_question_list $qlist
     */
    public static function question_list_create_template_url(capquiz_question_list $qlist): moodle_url {
        $url = self::create_view_url(self::$urlaction);
        $url->param('action', 'create-question-list-template');
        $url->param('question-list-id', $qlist->id());
        return $url;
    }

    /**
     * Generates and returns url to select a question list with
     * the parameters to set the question list
     *
     * @param capquiz_question_list $qlist
     */
    public static function question_list_select_url(capquiz_question_list $qlist): moodle_url {
        $url = self::create_view_url(self::$urlaction);
        $url->param('action', 'set-question-list');
        $url->param('question-list-id', $qlist->id());
        return $url;
    }

    /**
     * Generates and returns url to set a question rating with
     * the parameters to set the question rating
     *
     * @param int $questionid
     */
    public static function set_question_rating_url(int $questionid): moodle_url {
        $url = self::create_view_url(self::$urlaction);
        $url->param('action', 'set-question-rating');
        $url->param('question-id', $questionid);
        return $url;
    }

    /**
     * Generates and returns url to regrade all
     */
    public static function regrade_all_url(): moodle_url {
        $url = self::create_view_url(self::$urlaction);
        $url->param('action', 'regrade-all');
        return $url;
    }

    /**
     * Generates and returns url to merge qlist with the parameters to merge the qlist
     *
     * @param int $qlistid
     */
    public static function merge_qlist(int $qlistid): moodle_url {
        $url = self::create_view_url(self::$urlaction);
        $url->param('action', 'merge_qlist');
        $url->param('qlistid', $qlistid);
        return $url;
    }

    /**
     * Generates and returns url to delete a question list with the parameters to delete the list
     *
     * @param int $qlistid
     */
    public static function delete_qlist(int $qlistid): moodle_url {
        $url = self::create_view_url(self::$urlaction);
        $url->param('action', 'delete_qlist');
        $url->param('qlistid', $qlistid);
        return $url;
    }

    /**
     * Generates and returns url to submit an attempt
     *
     * @param capquiz_question_attempt $attempt
     */
    public static function response_submit_url(capquiz_question_attempt $attempt): moodle_url {
        $url = self::create_view_url(self::$urlasync);
        $url->param('action', 'answered');
        $url->param('attempt', $attempt->id());
        return $url;
    }

    /**
     * Generates and returns url to mark an attempt as reviewed
     *
     * @param capquiz_question_attempt $attempt
     */
    public static function response_reviewed_url(capquiz_question_attempt $attempt): moodle_url {
        $url = self::create_view_url(self::$urlasync);
        $url->param('action', 'reviewed');
        $url->param('attempt', $attempt->id());
        return $url;
    }
}
