<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = [    // Can start a quiz and move on to the next question.
    // NB: must have 'attempt' as well to be able to see the questions.
    'mod/capquiz:instructor' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy'       => [
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        ]
    ],

    // Can try to answer the quiz.
    'mod/capquiz:student' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy'       => [
            'student'        => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        ]
    ],

    // Can try to answer the quiz.
    'mod/capquiz:attempt' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy'       => [
            'student'        => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        ]
    ],

    // Can see who gave what answer.
    'mod/capquiz:seeresponses' => [
        'captype'      => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy'       => [
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        ]
    ],

    // Can add / delete / update questions.
    'mod/capquiz:editquestions' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy'       => [
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        ]
    ],

    // Can add an instance of this module to a course.
    'mod/capquiz:addinstance' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'legacy'       => [
            'editingteacher' => CAP_ALLOW,
            'coursecreator'  => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        ]
    ],
];