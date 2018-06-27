<?php

namespace mod_capquiz;

require_once($CFG->dirroot . '/mod/capquiz/classes/widget/tree_widget.php');

defined('MOODLE_INTERNAL') || die();

class question_tree_model extends tree_model
{
    private $capquiz;
    private $question_list;

    public function header_cell(int $column)
    {
        return array(
            "#",
            "Title",
            "Rating",
            "Action"
        )[$column];
    }

    public function __construct(capquiz $capquiz)
    {
        $this->capquiz = $capquiz;
        $this->question_list = $capquiz->question_list();
    }

    public function column_count()
    {
        return 4;
    }

    public function row_count()
    {
        return $this->question_list->question_count();
    }

    public function cell(int $row, int $column)
    {
        $question = $this->question_list->questions()[$row];
        return array(
            $row + 1,
            $question->name(),
            $this->change_rating_element($question),
            "Remove"
        )[$column];
    }

    private function change_rating_element(capquiz_question $question)
    {
        if ($this->capquiz->is_published())
            return $question->rating();
        $html = '<form name="form" action="' . capquiz_urls::create_set_question_rating_url($this->capquiz, $question->id()) . '" method="post">';
        $html .= '<input type="number" name="rating" id="rating" value="' . $question->rating() . '">';
        $html .= '</form>';
        return $html;
    }
}

class question_list_widget
{
    private $capquiz;
    private $question_list;

    public function __construct(capquiz $capquiz)
    {
        $this->capquiz = $capquiz;
        $this->question_list = $capquiz->question_list();
    }

    public function show()
    {
        if ($this->question_list == null) {
            echo "No question list assigned";
        } else if (count($this->question_list->questions()) == 0) {
            echo "No questions added to the list";
        } else {
            $this->print_questions();
        }
    }

    private function print_questions()
    {
        $list_widget = new tree_widget(new question_tree_model($this->capquiz, $this->question_list));
        $list_widget->show();
    }
}