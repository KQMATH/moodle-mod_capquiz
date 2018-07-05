<?php

namespace mod_capquiz;

require_once($CFG->dirroot . '/mod/capquiz/classes/widget/tree_widget.php');

defined('MOODLE_INTERNAL') || die();

class enrolled_users_model extends tree_model {

    private $users;
    private $capquiz;

    public function __construct(capquiz $capquiz) {
        $this->users = capquiz_user::list_users($capquiz);
        $this->capquiz = $capquiz;
    }

    public function header_cell(int $column) {
        return ['#', 'User ID', 'Rating'][$column];
    }

    public function column_count() {
        return 3;
    }

    public function row_count() {
        return count($this->users);
    }

    public function cell(int $row, int $column) {
        $user = $this->users[$row];
        return [$row + 1, $user->id(), $user->rating()][$column];
    }

}

class enrolled_users_widget {

    private $capquiz;

    public function __construct(capquiz $capquiz) {
        $this->capquiz = $capquiz;
    }

    public function show() {
        $list_widget = new tree_widget(new enrolled_users_model($this->capquiz, $this->question_list));
        $list_widget->show();
    }

}