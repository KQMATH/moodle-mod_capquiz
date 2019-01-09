#!/usr/bin/env bash

EXIT=0

moodle-plugin-ci phplint || EXIT=$?
moodle-plugin-ci phpcpd || EXIT=$?
moodle-plugin-ci phpmd || EXIT=$?
moodle-plugin-ci codechecker || EXIT=$?
moodle-plugin-ci validate || EXIT=$?
moodle-plugin-ci savepoints || EXIT=$?
moodle-plugin-ci mustache || EXIT=$?
moodle-plugin-ci grunt -t eslint:amd || EXIT=$?
moodle-plugin-ci phpunit --coverage-clover || EXIT=$?
moodle-plugin-ci behat || EXIT=$?

exit ${EXIT}