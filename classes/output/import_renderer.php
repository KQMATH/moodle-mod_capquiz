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
 * This file defines a class used to render the question list imports
 *
 * @package     mod_capquiz
 * @author      Sebastian S. Gundersen <sebastian@sgundersen.com>
 * @copyright   2019 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz\output;

use mod_capquiz\capquiz;
use mod_capquiz\capquiz_urls;

defined('MOODLE_INTERNAL') || die();

/**
 * Class import_renderer
 *
 * @package     mod_capquiz
 * @author      Sebastian S. Gundersen <sebastian@sgundersen.com>
 * @copyright   2019 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_renderer {

    /** @var capquiz $capquiz */
    private $capquiz;

    /** @var renderer $renderer */
    private $renderer;

    /**
     * import_renderer constructor.
     * @param capquiz $capquiz The current capquiz
     * @param renderer $renderer The renderer to be used by this instance
     */
    public function __construct(capquiz $capquiz, renderer $renderer) {
        $this->capquiz = $capquiz;
        $this->renderer = $renderer;
    }

    /**
     * Fetches questions in the list from the database
     *
     * @param int $qlistid the id of the list with the question
     * @return array Array of all questions in list $qlistid
     * @throws \dml_exception
     */
    private function get_questions_in_list(int $qlistid) : array {
        global $DB;
        $sql = 'SELECT cq.id     AS id,
                       cq.rating AS rating,
                       q.name    AS name
                  FROM {capquiz_question} cq
                  JOIN {question} q
                    ON q.id = cq.question_id
                 WHERE cq.question_list_id = :qlistid
              ORDER BY cq.rating';
        return $DB->get_records_sql($sql, ['qlistid' => $qlistid]);
    }

    /**
     * Get list of all questions
     *
     * @return array
     * @throws \dml_exception
     */
    private function get_question_lists() : array {
        global $DB;
        $path = \context_course::instance($this->capquiz->course()->id)->path;
        $sql = 'SELECT DISTINCT cql.*
                  FROM {capquiz_question_list} cql
                  JOIN {context} ctx
                    ON (ctx.id = cql.context_id AND ctx.path LIKE :pathpart)
                    OR cql.context_id IS NULL
                 WHERE cql.is_template = 1
              ORDER BY cql.time_created DESC';
        return $DB->get_records_sql($sql, ['pathpart' => $path . '%']);
    }

    /**
     * Render
     *
     * @return bool|string
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function render() {
        $srcqlists = $this->get_question_lists();
        $qlists = [];
        foreach ($srcqlists as $srcqlist) {
            $questions = [];
            foreach ($this->get_questions_in_list($srcqlist->id) as $question) {
                $questions[] = $question;
            }
            $qlists[] = [
                'title' => $srcqlist->title,
                'time_created' => $srcqlist->time_created,
                'description' => $srcqlist->description,
                'questions' => $questions,
                'merge' => [
                    'primary' => true,
                    'method' => 'post',
                    'url' => capquiz_urls::merge_qlist($srcqlist->id)->out(false),
                    'label' => get_string('merge', 'capquiz')
                ],
                'delete' => [
                    'primary' => false,
                    'method' => 'post',
                    'url' => capquiz_urls::delete_qlist($srcqlist->id)->out(false),
                    'label' => get_string('delete')
                ]
            ];
        }
        return $this->renderer->render_from_template('capquiz/merge_with_question_list', ['lists' => $qlists]);
    }

}

