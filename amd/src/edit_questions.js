// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    mod_capquiz
 * @author     Sebastian S. Gundersen <sebastsg@stud.ntnu.no>
 * @copyright  2018 NTNU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {

    var parameters = {
        capquizId: 0,
    };

    /**
     * Send an action to the server.
     * @param data
     * @param onSuccess
     * @param onError
     */
    function sendAction(data, onSuccess, onError) {
        $.ajax({
            type: 'post',
            url: 'action.php',
            data: data,
            success: onSuccess,
            error: onError
        });
    }

    /**
     * Send the new default rating for the question list to the server.
     * @param {object} data
     * @param {number} rating
     * @param {callback} onSuccess
     * @param {callback} onError
     */
    function sendDefaultQuestionRating(data, rating, onSuccess, onError) {
        sendAction({
            'action': 'set-default-question-rating',
            'id': parameters.capquizId,
            'rating': rating,
        }, onSuccess, onError);
    }

    /**
     * Send the new rating for the question to the server.
     * @param {object} data
     * @param {number} rating
     * @param {callback} onSuccess
     * @param {callback} onError
     */
    function sendQuestionRating(data, rating, onSuccess, onError) {
        sendAction({
            'action': 'set-question-rating',
            'id': parameters.capquizId,
            'question-id': data.questionId,
            'rating': rating,
        }, onSuccess, onError);
    }

    /**
     * Send the new value, and avoid race condition.
     * @param {object} $input
     * @param {callback} sendInput
     * @param {object} data
     */
    function submitInput($input, sendInput, data) {
        $input.data('saving', true);
        $input.data('dirty', false);
        var $indicator = $input.next();
        $indicator.css('color', 'blue');
        sendInput(data, $input.val(), function() {
            if ($input.data('dirty') === true) {
                submitInput($input, sendInput, data);
            } else {
                $indicator.css('color', 'green');
                $input.data('dirty', false);
                $input.data('saving', false);
            }
        }, function() {
            $indicator.css('color', 'red');
        });
    }

    /**
     * Send the new rating for the question, and avoid race condition.
     * @param $input
     */
    function submitQuestionRating($input) {
        submitInput($input, sendQuestionRating, {questionId: $input.data('question-id')});
    }

    /**
     * Send the new default rating for the question list, and avoid race condition.
     * @param $input
     */
    function submitDefaultQuestionRating($input) {
        submitInput($input, sendDefaultQuestionRating, null);
    }

    /**
     * Register an input event listener for submission.
     * @param {string} query
     * @param {callback} submit
     */
    function registerListener(query, submit) {
        $(document).on('input', query, function(event) {
            var $input = $(event.target);
            var isBeingSaved = $input.data('saving');
            if (isBeingSaved === true) {
                $input.data('dirty', true);
                return;
            }
            submit($input);
        });
    }

    /**
     * Sorts a table by the respective column based on $header.
     * It searches for an element of class "capquiz-sortable-item" inside the <td>, and if found,
     * the value attribute is used if it exists. Otherwise, the inner html is used to sort by.
     *
     * The <td> tag may not have the item class, as it has no effect on the sorting.
     * Their children elements are not required to have the class either. The inner html of <td> will be used then.
     *
     * The first column in the table must be an index of the row.
     *
     * @param $header The header column for which to sort the table by.
     */
    function sortTable($header) {
        var column = $header.index();
        var $table = $header.parent().parent();
        var $rows = $table.find('tr:gt(0)').toArray().sort(function (rowA, rowB) {
            var $colA = $(rowA).children('td').eq(0);
            var $colB = $(rowB).children('td').eq(0);
            return parseInt($colA.text()) - parseInt($colB.text());
        });
        $table.append($rows);
        $rows = $table.find('tr:gt(0)').toArray().sort(function (rowA, rowB) {
            var $colA = $(rowA).children('td').eq(column);
            var $colB = $(rowB).children('td').eq(column);
            var $itemA = $colA.find('.capquiz-sortable-item');
            var $itemB = $colB.find('.capquiz-sortable-item');
            var valA = ($itemA.length === 0 ? $colA.html() : ($itemA.val().length === 0 ? $itemA.html() : $itemA.val()));
            var valB = ($itemB.length === 0 ? $colB.html() : ($itemB.val().length === 0 ? $itemB.html() : $itemB.val()));
            if ($.isNumeric(valA) && $.isNumeric(valB)) {
                return valA - valB;
            } else {
                return valA.toString().localeCompare(valB);
            }
        });
        var ascending = ($table.data('asc') === 'true');
        $table.data('asc', ascending ? 'false' : 'true');
        var iconName = (ascending ? 'fa-arrow-up' : 'fa-arrow-down');
        $.each($table.find('.capquiz-sortable'), function () {
            $(this).find('.fa').remove();
        });
        $header.prepend('<i class="fa ' + iconName + '"></i>');
        if (!ascending) {
            $rows = $rows.reverse();
        }
        $table.append($rows);
        var i = 1;
        $table.find('tr:gt(0)').each(function () {
            $(this).find('td:first-child').html(i);
            i++;
        });
    }

    /**
     * Register click event listeners for the sortable table columns.
     */
    function registerSortListener() {
        $(document).on('click', '.capquiz-sortable', function() {
            sortTable($(this));
        });
    }

    /**
     * Set the tab indices for the question rating elements to be more user friendly.
     */
    function fixTabIndicesForQuestionRatingInputs() {
        $('.capquiz-question-rating-submit-wrapper button').each(function(index, object) {
            $(object).attr('tabindex', -1);
        });
    }

    return {
        initialize: function(capquizId) {
            parameters.capquizId = capquizId;
            registerListener('.capquiz-question-rating input', submitQuestionRating);
            registerListener('.capquiz-default-question-rating input', submitDefaultQuestionRating);
            fixTabIndicesForQuestionRatingInputs();
            registerSortListener();
        }
    };

});
