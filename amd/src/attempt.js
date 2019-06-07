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
 * @author     Sebastian S. Gundersen <sebastian@sgundersen.com>
 * @copyright  2019 NTNU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {

    /**
     *
     * @param $form
     */
    function moveCommentFieldToForm($form) {
        var $comment = $('.capquiz-student-comment');
        if ($comment.find('textarea').val().length) {
            $comment.prop('open', true);
        }
        $form.prepend($comment);
    }

    return {
        initialize: function() {
            var $nextButton = $('#capquiz_review_next');
            if ($nextButton.length) {
                moveCommentFieldToForm($nextButton.parent());
            }
        }
    };

});