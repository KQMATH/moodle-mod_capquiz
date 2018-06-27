<?php

namespace mod_capquiz;

class database_meta
{
    public static $table_capquiz = "capquiz";
    public static $table_capquiz_user = "capquiz_user";
    public static $table_capquiz_attempt = "capquiz_attempt";
    public static $table_capquiz_question = "capquiz_question";
    public static $table_capquiz_question_list = "capquiz_question_list";

    public static $field_id = "id";
    public static $field_user_id = "user_id";
    public static $field_course_id = "course_id";
    public static $field_attempt_id = "attempt_id";
    public static $field_capquiz_id = "capquiz_id";
    public static $field_question_id = "question_id";
    public static $field_question_list_id = "question_list_id";

    public static $field_title = "title";
    public static $field_rating = "rating";
    public static $field_answered = "answered";
    public static $field_reviewed = "reviewed";
    public static $field_description = "description";

    public static $moodletable_question = 'question';
}