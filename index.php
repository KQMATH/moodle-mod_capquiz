<?php

namespace mod_capquiz;

require('../../config.php');

global $PAGE;
$PAGE->set_url(new \moodle_url(capquiz_urls::$url_view));
