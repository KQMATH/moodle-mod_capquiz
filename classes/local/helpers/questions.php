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

declare(strict_types=1);

namespace mod_capquiz\local\helpers;

use mod_capquiz\capquiz;

/**
 * Helper functions for questions.
 *
 * @package     mod_capquiz
 * @author      Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright   2025 Norwegian University of Science and Technology (NTNU)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class questions {
    /**
     * Get all question records that are found by question references via the given context and question area.
     *
     * @param int $contextid
     * @param string $questionarea
     * @return \stdClass[]
     */
    public static function get_all_questions_by_references(int $contextid, string $questionarea): array {
        global $DB;
        return $DB->get_records_sql("
            SELECT q.*
              FROM {question_references} qr
              JOIN {question_bank_entries} qbe
                ON qbe.id = qr.questionbankentryid
              JOIN {question_versions} qv
                ON qv.questionbankentryid = qbe.id
              JOIN {question} q
                ON q.id = qv.questionid
             WHERE qr.component = 'mod_capquiz'
               AND qr.usingcontextid = :contextid
               AND qr.questionarea = :questionarea", [
            'contextid' => $contextid,
            'questionarea' => $questionarea,
        ]);
    }

    /**
     * Get question display options for reviewing question attempts.
     *
     * @param capquiz $capquiz
     * @return \question_display_options
     */
    public static function get_question_display_options(capquiz $capquiz): \question_display_options {
        $options = new \question_display_options();
        if ($capquiz->get('id')) {
            $options->context = $capquiz->get_context();
        }
        foreach (json_decode($capquiz->get('questiondisplayoptions'), true) ?: [] as $key => $value) {
            if (property_exists($options, $key)) {
                $options->$key = $value;
            }
        }
        return $options;
    }

    /**
     * CAPQuiz doesn't support every question behaviour due to the nature of how the quiz
     * behaves, so we need to filter which can be used.
     *
     * 'Adaptive mode' is discouraged in favor of 'Interactive with multiple tries'.
     *
     * 'Adaptive mode (no penalty)' is disabled because it would defeat the purpose of user and question ratings.
     * 'Deferred feedback' and 'Deferred feedback with CBM' are disabled because CAPQuiz relies on immediate feedback.
     *
     * @return string[]
     */
    public static function get_unsupported_question_behaviours(): array {
        return ['adaptivenopenalty', 'deferredfeedback', 'deferredcbm'];
    }
}
