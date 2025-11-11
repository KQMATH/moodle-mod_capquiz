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

namespace mod_capquiz\output;

use core\output\action_menu;
use core\output\html_writer;
use core\output\pix_icon;
use mod_capquiz\capquiz;
use mod_capquiz\capquiz_slot;

/**
 * Edit question list.
 *
 * @package     mod_capquiz
 * @author      Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright   2024 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_questions implements \renderable, \templatable {
    /**
     * Constructor.
     *
     * @param capquiz $capquiz
     */
    public function __construct(
        /** @var capquiz CAPQuiz */
        private readonly capquiz $capquiz
    ) {
    }

    /**
     * Render question list.
     *
     * @param \core\output\renderer_base $output
     * @return bool|string
     */
    public function render(\core\output\renderer_base $output): bool|string {
        return $output->render_from_template('capquiz/edit_questions', $this->export_for_template($output));
    }

    /**
     * Export parameters for template.
     *
     * @param \core\output\renderer_base $output
     * @return array
     */
    public function export_for_template(\core\output\renderer_base $output): array {
        $cm = $this->capquiz->get_cm();
        $cmid = (int)$cm->id;
        $context = $this->capquiz->get_context();
        $output->get_page()->requires->js_call_amd('mod_capquiz/edit_slots', 'init', [$cmid]);
        $output->get_page()->requires->js_call_amd('mod_capquiz/qbank_modal', 'init', [
            $context->id,
            $cmid,
            $cmid,
        ]);
        $rows = [];
        $editstr = get_string('edit');
        $previewstr = get_string('preview');
        $deletestr = get_string('remove');
        foreach (capquiz_slot::get_records(['capquizid' => $this->capquiz->get('id')], 'rating') as $slot) {
            $editaction = false;
            $previewaction = false;
            $deleteaction = false;
            $relatedcourse = $slot->find_related_course();
            $question = $slot->find_question();
            if ($question && $relatedcourse) {
                $editaction = [
                    'url' => (new \core\url('/question/bank/editquestion/question.php', [
                        'cmid' => $cmid,
                        'id' => $question->id,
                    ]))->out(false),
                    'label' => $editstr,
                    'icon' => [
                        'key' => 'i/edit',
                        'title' => $editstr,
                    ],
                    'attributes' => [['name' => 'target', 'value' => '_blank']],
                ];
                $previewaction = [
                    'url' => \qbank_previewquestion\helper::question_preview_url($question->id)->out(false),
                    'label' => $previewstr,
                    'icon' => [
                        'key' => 'e/find_replace',
                        'title' => $previewstr,
                    ],
                    'attributes' => [['name' => 'target', 'value' => '_blank']],
                ];
                $deleteaction = [
                    'url' => (new \core\url('/mod/capquiz/edit.php', [
                        'id' => $cmid,
                        'action' => 'deleteslot',
                        'slotid' => $slot->get('id'),
                    ]))->out(false),
                    'label' => $deletestr,
                    'icon' => [
                        'key' => 'e/delete',
                        'title' => $deletestr,
                    ],
                ];
            }
            $questionversion = $slot->find_question_version();
            $rows[] = [
                'name' => $question?->name ?? get_string('cannotloadquestion', 'question'),
                'rating' => round($slot->get('rating'), 2),
                'slotid' => $slot->get('id'),
                'version' => $questionversion->version,
                'latestversion' => $questionversion->referencedversion === null,
                'deleteaction' => $deleteaction,
                'editaction' => $editaction,
                'previewaction' => $previewaction,
            ];
        }
        $menu = new action_menu();
        $trigger = html_writer::tag('span', get_string('add'));
        $menu->set_menu_trigger($trigger);

        $fromqbankstring = get_string('fromquestionbank', 'capquiz');
        $icon = new pix_icon('t/add', $fromqbankstring, 'moodle', ['class' => 'iconsmall', 'title' => '']);
        $qbankaction = new \action_menu_link_secondary($output->get_page()->url, $icon, $fromqbankstring, [
            'class' => 'questionbank',
            'data-header' => $fromqbankstring,
            'data-action' => 'questionbank',
        ]);

        $menu->add($qbankaction);
        return [
            'slots' => $rows,
            'actionmenu' => $output->render($menu),
        ];
    }
}
