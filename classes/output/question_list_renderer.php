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
 * This file defines a class used to render question list
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @author      Sebastian S. Gundersen <sebastian@sgundersen.com>
 * @copyright   2019 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_capquiz\output;

use mod_capquiz\capquiz;
use mod_capquiz\capquiz_urls;
use mod_capquiz\capquiz_question_list;

defined('MOODLE_INTERNAL') || die();

/**
 * Class question_list_renderer
 *
 * @package     mod_capquiz
 * @author      Aleksander Skrede <aleksander.l.skrede@ntnu.no>
 * @author      Sebastian S. Gundersen <sebastian@sgundersen.com>
 * @copyright   2019 NTNU
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_list_renderer {

    /** @var capquiz $capquiz */
    private $capquiz;

    /** @var renderer $renderer */
    private $renderer;

    /**
     * question_list_renderer constructor.
     * @param capquiz $capquiz
     * @param renderer $renderer
     */
    public function __construct(capquiz $capquiz, renderer $renderer) {
        $this->capquiz = $capquiz;
        $this->renderer = $renderer;
    }

    /**
     * Renders question list
     *
     * @return bool|string
     * @throws \coding_exception
     */
    public function render() {
        global $PAGE;
        $cmid = $this->capquiz->course_module()->id;
        $PAGE->requires->js_call_amd('mod_capquiz/edit_questions', 'initialize', [$cmid]);
        $qlist = $this->capquiz->question_list();
        if ($qlist && $qlist->has_questions()) {
            return $this->render_questions($qlist);
        }
        $title = get_string('question_list', 'capquiz');
        $noquestions = get_string('question_list_no_questions', 'capquiz');
        return "<h3>$title</h3><p>$noquestions</p>";
    }

    /**
     * Renders all the individual questions
     *
     * @param capquiz_question_list $qlist
     * @return bool|string
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    private function render_questions(capquiz_question_list $qlist) {
        global $CFG;
        $rows = [];
        $questions = $qlist->questions();
        for ($i = 0; $i < $qlist->question_count(); $i++) {
            $question = $questions[$i];
            $courseid = $question->course_id();
            $editurl = new \moodle_url($CFG->wwwroot . '/question/question.php', [
                'courseid' => $courseid,
                'id' => $question->question_id()
            ]);
            $previewurl = new \moodle_url($CFG->wwwroot . '/question/preview.php', [
                'courseid' => $courseid,
                'id' => $question->question_id()
            ]);
            $targetblank = ['name' => 'target', 'value' => '_blank'];
            $edit = $courseid === 0 ? false : [
                'url' => $editurl->out(false),
                'label' => get_string('edit'),
                'classes' => 'fa fa-edit',
                'attributes' => [$targetblank]
            ];
            $preview = $courseid === 0 ? false : [
                'url' => $previewurl->out(false),
                'label' => get_string('preview'),
                'classes' => 'fa fa-search-plus',
                'attributes' => [$targetblank]
            ];
            $rows[] = [
                'index' => $i + 1,
                'name' => $question->name(),
                'rating' => round($question->rating(), 3),
                'question_id' => $question->id(),
                'rating_url' => capquiz_urls::set_question_rating_url($question->id())->out(false),
                'delete' => [
                    'url' => capquiz_urls::remove_question_from_list_url($question->id())->out(false),
                    'label' => get_string('remove', 'capquiz'),
                    'classes' => 'fa fa-trash'
                ],
                'edit' => $edit,
                'preview' => $preview
            ];
        }
        $message = null;
        if ($qlist->has_questions()) {
            $message = get_string('update_rating_explanation', 'capquiz');
        }
        return $this->renderer->render_from_template('capquiz/question_list', [
            'default_rating' => $qlist->default_question_rating(),
            'questions' => $rows,
            'message' => $message ? $message : false
        ]);
    }

}
