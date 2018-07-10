<?php

defined('MOODLE_INTERNAL') || die();

function capquiz($capquiz) {
    global $DB;
    $capquiz->id = $DB->insert_record('capquiz', $capquiz);
    return $capquiz->id;
}

function capquiz_add_instance(stdClass $capquiz_mod_form_data) {
    global $DB;
    $capquiz_mod_form_data->time_modified = time();
    $capquiz_mod_form_data->time_created = time();
    $capquiz_mod_form_data->published = false;
    $capquiz_mod_form_data->question_list_id = null;
    $capquiz_mod_form_data->question_usage_id = null;
    return $DB->insert_record('capquiz', $capquiz_mod_form_data);
}

function capquiz_update_instance(mod_capquiz\capquiz $capquiz) {
    global $DB;
    $capquiz->id = $capquiz->instance;
    $DB->update_record('capquiz', $capquiz);
    return true;
}

function capquiz_delete_instance(int $id) {
    global $DB;
    try {
        $capquiz = $DB->get_record('capquiz', ['id' => $id], '*', MUST_EXIST);
        $DB->delete_records('capquiz_questions', ['capquizid' => $capquiz->id]);
        $DB->delete_records('capquiz', ['id' => $capquiz->id]);
    } catch (Exception $e) {
        return false;
    }
    return true;
}

function capquiz_cron() {
    return true;
}

function capquiz_supports($feature) {
    return false;
}
