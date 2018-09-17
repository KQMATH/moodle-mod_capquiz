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

define(['jquery'], $ => {

    let parameters = {
        capquizId: 0,
    };

    function sendQuestionRating(questionId, rating, onSuccess, onError) {
        $.ajax({
            type: 'post',
            url: 'action.php',
            data: {
                'action': 'set-question-rating',
                'id': parameters.capquizId,
                'question-id': questionId,
                'rating': rating,
            },
            success: onSuccess,
            error: onError
        });
    }

    function submitQuestionRating($input) {
        $input.data('saving', true);
        $input.data('dirty', false);
        let $indicator = $input.next();
        $indicator.css('color', 'blue');
        sendQuestionRating($input.data('question-id'), $input.val(), () => {
            if ($input.data('dirty') === true) {
                submitQuestionRating($input);
            } else {
                $indicator.css('color', 'green');
                $input.data('dirty', false);
                $input.data('saving', false);
            }
        }, () => {
            $indicator.css('color', 'red');
        });
    }

    function registerQuestionRatingListeners() {
        $(document).on('input', '.capquiz-question-rating input', event => {
            let $input = $(event.target);
            const isBeingSaved = $input.data('saving');
            if (isBeingSaved === true) {
                $input.data('dirty', true);
                return;
            }
            submitQuestionRating($input);
        });
    }

    function fixTabIndicesForQuestionRatingInputs() {
        $('.capquiz-question-rating-submit-wrapper button').each((index, object) => {
            $(object).attr('tabindex', -1);
        });
    }

    return {
        initialize: (capquizId) => {
            parameters.capquizId = capquizId;
            registerQuestionRatingListeners();
            fixTabIndicesForQuestionRatingInputs();
        }
    };

});
