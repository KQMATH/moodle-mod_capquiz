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
 * @module     mod_capquiz
 * @author     Sebastian S. Gundersen <sebastian@sgundersen.com>
 * @copyright  2019 NTNU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/str'], function($, mString) {

    /**
     * Show star tooltip.
     * @param {Object} $element
     * @param {string} text
     */
    function showTooltip($element, text) {
        var $tooltip = $('.capquiz-star-tooltip');
        $tooltip.html(text);
        $tooltip.css('display', 'block');
        var x = $element.offset().left - $tooltip.width() / 2;
        var y = $element.offset().top + 32;
        $tooltip.css('left', x + 'px');
        $tooltip.css('top', y + 'px');
    }

    /**
     * Hide star tooltip.
     */
    function hideTooltip() {
        $('.capquiz-star-tooltip').css('display', 'none');
    }

    /**
     * Register event listeners for showing tooltips on the stars.
     */
    function enableTooltips() {
        $(document).on('mouseover', '.capquiz-quiz-stars span', function() {
            var $self = $(this);
            if ($self.hasClass('capquiz-star')) {
                $.when(mString.get_string('tooltip_achieved_star', 'capquiz')).done(function(text) {
                    showTooltip($self, text);
                });
            } else if ($self.hasClass('capquiz-lost-star')) {
                $.when(mString.get_string('tooltip_lost_star', 'capquiz')).done(function(text) {
                    showTooltip($self, text);
                });
            } else if ($self.hasClass('capquiz-no-star')) {
                $.when(mString.get_string('tooltip_no_star', 'capquiz')).done(function(text) {
                    showTooltip($self, text);
                });
            } else if ($self.hasClass('capquiz-help-stars')) {
                $.when(mString.get_string('tooltip_help_star', 'capquiz')).done(function(text) {
                    showTooltip($self, text);
                });
            }
        });
        $(document).on('mouseleave', '.capquiz-quiz-stars span', function() {
            hideTooltip();
        });
    }

    return {
        initialize: function() {
            enableTooltips();
        }
    };

});
