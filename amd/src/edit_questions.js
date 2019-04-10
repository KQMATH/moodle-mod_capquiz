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
        }
    };

});
