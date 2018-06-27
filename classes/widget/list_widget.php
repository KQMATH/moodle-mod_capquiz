<?php

namespace mod_capquiz;

defined('MOODLE_INTERNAL') || die();

class list_model
{
    public function row_count()
    {
        return 0;
    }

    public function row(int $index)
    {
        return '';
    }
}

class list_widget
{
    private $model;
    private $list_css_class;
    private $list_item_css_class;

    public function __construct(list_model $list_model)
    {
        $this->model = $list_model;
        $this->list_css_class = 'capquiz-list-widget';
        $this->list_item_css_class = 'capquiz-list-widget-item';
    }

    public function set_list_css_class(string $list_css_class)
    {
        $this->list_css_class = $list_css_class;
    }

    public function set_list_item_css_class(string $list_item_css_class)
    {
        $this->list_item_css_class = $list_item_css_class;
    }

    public function show()
    {
        $row_count = $this->model ? $this->model->row_count() : 0;
        if ($row_count == 0) {
            return;
        }
        echo '<ol class="' . $this->list_css_class . '">';
        for ($index = 0; $index < $row_count; $index++) {
            echo '<li class="' . $this->list_item_css_class . '">' . $this->model->row($index) . '</li>';
        }
        echo '</ol>';
    }
}

