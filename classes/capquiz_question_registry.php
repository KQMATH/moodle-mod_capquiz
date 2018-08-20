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
class capquiz_question_registry {

    private $capquiz;

    public function __construct(capquiz $capquiz) {
        $this->capquiz = $capquiz;
    }

    public function capquiz_instance() {
        return $this->capquiz;
    }

    public function question_ids(int $question_list_id) {
        $questions = $this->question_list($question_list_id)->questions();
        $ret = [];
        foreach ($questions as $question) {
            $ret[] = $question->id();
        }
        return $ret;
    }

    public function question_list(int $list_id) {
        global $DB;
        if ($entry = $DB->get_record(database_meta::$table_capquiz_question_list, [database_meta::$field_id => $list_id])) {
            return new capquiz_question_list($entry, $this->capquiz);
        }
        return null;
    }

    /**
     * @param bool|null $istemplate
     * @return capquiz_question_list[]
     */
    public function question_lists(bool $istemplate = null) {
        global $DB;
        $lists = [];
        $conditions = null;
        if ($istemplate !== null) {
            $conditions = ['is_template' => (int)$istemplate];
        }
        $records = $DB->get_records(database_meta::$table_capquiz_question_list, $conditions);
        foreach ($records as $record) {
            $lists[] = new capquiz_question_list($record, $this->capquiz);
        }
        return $lists;
    }

    public function has_question_lists() {
        return count($this->question_lists()) > 0;
    }

    public function create_question_list(string $title, string $description, array $ratings) {
        global $DB;
        if (count($ratings) < 5) {
            return false;
        }
        $list = new \stdClass();
        $list->capquiz_id = $this->capquiz->course_module_id();
        $list->title = $title;
        $list->description = $description;
        $list->level_1_rating = $ratings[0];
        $list->level_2_rating = $ratings[1];
        $list->level_3_rating = $ratings[2];
        $list->level_4_rating = $ratings[3];
        $list->level_5_rating = $ratings[4];
        $list->is_template = 0;
        $list->time_created = time();
        $list->time_modified = time();
        try {
            if ($id = $DB->insert_record(database_meta::$table_capquiz_question_list, $list)) {
                $this->assign_to_capquiz($id);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function assign_to_capquiz(int $id) {
        $this->capquiz->assign_question_list($id);
    }

}
