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
 * @author     Sebastian Gundersen <sebastian@sgundersen.com>
 * @copyright  2024 Norwegian University of Science and Technology (NTNU)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

"use strict";

import Ajax from 'core/ajax';

export const SELECTORS = {
    questionRating: '.capquiz-question-rating',
    questionRatingInput: '.capquiz-question-rating input',
    questionRatingSaveButton: '.capquiz-question-rating-save',
    questionRatingResetButton: '.capquiz-question-rating-reset',
};

/**
 * Set question rating.
 *
 * @param {number} courseModuleId
 * @param {number} slotId
 * @param {number} rating
 * @returns {Promise<void>}
 */
export const setQuestionRating = (courseModuleId, slotId, rating) => {
    return Ajax.call([{
        methodname: 'mod_capquiz_set_question_rating',
        args: {cmid: courseModuleId, slotid: slotId, rating: rating},
    }])[0];
};

/**
 * Initialize.
 *
 * @param {number} courseModuleId
 */
export const init = courseModuleId => {
    window.addEventListener('beforeunload', () => {
        for (const input of document.querySelectorAll(SELECTORS.questionRatingInput)) {
            if (input.dataset.dirty === 'yes') {
                event.preventDefault();
            }
        }
    });
    document.addEventListener('input', event => {
        const questionRating = event.target.closest(SELECTORS.questionRating);
        if (questionRating) {
            const input = questionRating.querySelector('input');
            const dirty = input.dataset.initialValue !== input.value;
            const saveButton = questionRating.querySelector(SELECTORS.questionRatingSaveButton);
            const resetButton = questionRating.querySelector(SELECTORS.questionRatingResetButton);
            saveButton.classList.toggle('d-none', !dirty);
            resetButton.classList.toggle('d-none', !dirty);
            if (dirty) {
                input.dataset.dirty = 'yes';
            } else {
                input.removeAttribute('data-dirty');
            }
        }
    });
    document.addEventListener('click', async event => {
        const saveButton = event.target.closest(SELECTORS.questionRatingSaveButton);
        if (saveButton) {
            const questionRating = event.target.closest(SELECTORS.questionRating);
            const input = questionRating.querySelector(SELECTORS.questionRatingInput);
            const resetButton = questionRating.querySelector(SELECTORS.questionRatingResetButton);
            saveButton.disabled = true;
            resetButton.disabled = true;
            const newValue = parseFloat(input.value);
            await setQuestionRating(courseModuleId, parseInt(input.dataset.slotId), newValue);
            input.dataset.initialValue = input.value;
            saveButton.classList.add('d-none');
            resetButton.classList.add('d-none');
            saveButton.disabled = false;
            resetButton.disabled = false;
            input.removeAttribute('data-dirty');
        }
        const resetButton = event.target.closest(SELECTORS.questionRatingResetButton);
        if (resetButton) {
            const questionRating = event.target.closest(SELECTORS.questionRating);
            const input = questionRating.querySelector(SELECTORS.questionRatingInput);
            input.value = input.dataset.initialValue;
            input.removeAttribute('data-dirty');
            questionRating.querySelector(SELECTORS.questionRatingSaveButton).classList.add('d-none');
            resetButton.classList.add('d-none');
        }
    });
};
