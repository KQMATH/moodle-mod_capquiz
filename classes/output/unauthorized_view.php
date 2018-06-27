<?php

namespace mod_capquiz;

defined('MOODLE_INTERNAL') || die();

class unauthorized_view
{
    private $renderer;

    public function __construct(capquiz $capquiz, \core_renderer $renderer)
    {
        $this->renderer = $renderer;
    }

    public function show()
    {
        echo $this->renderer->header();
        echo '<h3>You need to log in</h3>';
        echo $this->renderer->footer();
    }

}
