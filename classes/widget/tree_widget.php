<?php

namespace mod_capquiz;

defined('MOODLE_INTERNAL') || die();

class tree_model {

    public function header_cell(int $column) {
        return "Column " . $column;
    }

    public function row_count() {
        return 0;
    }

    public function column_count() {
        return 0;
    }

    public function cell(int $row, int $column) {
        return '';
    }

}

class tree_widget {

    private $model;
    private $row_css_class;
    private $cell_css_class;
    private $table_css_class;
    private $header_row_css_class;
    private $header_cell_css_class;

    public function __construct(tree_model $model) {
        $this->model = $model;
        $this->row_css_class = 'capquiz-tree-widget-row';
        $this->cell_css_class = 'capquiz-tree-widget-cell';
        $this->table_css_class = 'capquiz-tree-widget';
        $this->header_row_css_class = 'capquiz-tree-header';
        $this->header_cell_css_class = 'capquiz-tree-header-cell';
    }

    public function set_table_css_class(string $table_css_class) {
        $this->table_css_class = $table_css_class;
    }

    public function set_row_css_class(string $row_css_class) {
        $this->row_css_class = $row_css_class;
    }

    public function set_cell_css_class(string $cell_css_class) {
        $this->cell_css_class = $cell_css_class;
    }

    public function set_header_css_class(string $header_css_class) {
        $this->header_row_css_class = $header_css_class;
    }

    public function set_header_cell_css_class(string $header_cell_css_class) {
        $this->header_cell_css_class = $header_cell_css_class;
    }

    public function show() {
        $row_count = $this->model ? $this->model->row_count() : 0;
        $column_count = $this->model ? $this->model->column_count() : 0;
        if ($row_count === 0) {
            return;
        }
        $this->show_table($row_count, $column_count);
    }

    private function show_table(int $row_count, int $column_count) {
        echo "<table class='" . $this->table_css_class . "'>";
        $this->show_header($column_count);
        $this->show_rows($row_count, $column_count);
        echo "</table>";
    }

    private function show_header(int $column_count) {
        echo "<tr class='" . $this->header_row_css_class . "'>";
        for ($column = 0; $column < $column_count; $column++) {
            echo "<td class='" . $this->header_cell_css_class . "'>" . $this->model->header_cell($column) . "</td>";
        }
        echo "</tr>";
    }

    private function show_rows(int $row_count, int $column_count) {
        for ($row = 0; $row < $row_count; $row++) {
            echo "<tr class='" . $this->row_css_class . "'>";
            for ($column = 0; $column < $column_count; $column++) {
                echo "<td class='" . $this->cell_css_class . "'>" . $this->model->cell($row, $column) . "</td>";
            }
            echo "</tr>";
        }
    }

}
