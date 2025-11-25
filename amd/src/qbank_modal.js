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

import Modal from 'core/modal';
import * as ModalEvents from 'core/modal_events';
import * as FormChangeChecker from 'core_form/changechecker';
import * as Fragment from 'core/fragment';
import {getStrings} from 'core/str';
import AutoComplete from 'core/form-autocomplete';

export class QuestionBankModal extends Modal {
    /**
     * @param {Object} modalConfig
     */
    configure(modalConfig) {
        modalConfig.large = true;
        modalConfig.show = true;
        modalConfig.removeOnClose = true;
        this.contextId = modalConfig.contextId;
        this.quizCmId = modalConfig.quizCmId;
        this.bankCmId = modalConfig.bankCmId;
        this.originalTitle = modalConfig.title;
        super.configure(modalConfig);
    }

    /**
     * Display this modal.
     *
     * @returns {Promise}
     */
    show() {
        this.reloadBodyContent(window.location.search);
        return super.show();
    }

    /**
     * Reloady body content.
     */
    reloadBodyContent() {
        this.hideFooter();
        this.setTitle(this.originalTitle);
        this.setBody(Fragment.loadFragment('mod_capquiz', 'capquiz_qbank', this.contextId, {
            querystring: window.location.search,
            quizcmid: this.quizCmId,
            bankcmid: this.bankCmId,
        }));
    }

    /**
     * Register event listeners.
     */
    registerEventListeners() {
        super.registerEventListeners(this);

        this.getModal().on('click', 'button[data-action="switch-question-bank"]', async() => {
            await this.handleSwitchBankContentReload('#searchbanks');
            const searchBanks = document.getElementById('searchbanks');
            if (searchBanks instanceof HTMLElement) {
                searchBanks.addEventListener('change', event => {
                    const bankCmId = event.currentTarget.value;
                    if (bankCmId > 0) {
                        this.bankCmId = bankCmId;
                        this.reloadBodyContent(window.location.search);
                    }
                });
            }
            const goBackButton = document.querySelector('button[data-action="go-back"]');
            if (goBackButton instanceof HTMLButtonElement) {
                goBackButton.addEventListener('click', event => {
                    this.bankCmId = event.currentTarget.value;
                    this.reloadBodyContent(window.location.search);
                });
            }
        });

        this.getModal().on('click', 'a', event => {
            const target = event.currentTarget;
            if (!(target instanceof HTMLAnchorElement)) {
                return;
            }
            if (target.closest('td.capquiz_add_question')) {
                return;
            }
            if (target.closest('td.capquiz_preview_question')) {
                return;
            }
            if (target.closest('.sorters')) {
                return;
            }
            if (target.closest('a[data-newmodid]')) {
                this.bankCmId = target.dataset.newmodid;

                // We need to clear the filter as we are about to reload the content.
                const url = new URL(location.href);
                url.searchParams.delete('filter');
                history.pushState({}, '', url);
            }
            event.preventDefault();
            this.reloadBodyContent(target.search);
        });

        this.getModal().on('submit', 'form#questionsubmit', event => {
            const form = event.currentTarget;
            const cmIdInput = document.querySelector('form#questionsubmit input[name="cmid"]');
            cmIdInput.setAttribute('value', this.quizCmId);
            const actionUrl = new URL(form.getAttribute('action'));
            actionUrl.searchParams.set('cmid', this.quizCmId);
            form.setAttribute('action', actionUrl.toString());
        });

        this.getRoot().on(ModalEvents.bodyRendered, () => {
            FormChangeChecker.disableAllChecks();
        });
    }

    /**
     * Update the modal with a list of banks to switch to and enhance the standard selects to Autocomplete fields.
     *
     * Please note that this is copied from quiz/add_question_modal.js
     *
     * @param {String} selector for the original select element.
     */
    async handleSwitchBankContentReload(selector) {
        const [selectQuestionBank, placeholderString, goBackString] = await getStrings([
            {key: 'selectquestionbank', component: 'mod_quiz'},
            {key: 'searchbyname', component: 'mod_quiz'},
            {key: 'gobacktoquiz', component: 'mod_quiz'},
        ]);

        this.setTitle(selectQuestionBank);

        // Create a 'Go back' button and set it in the footer.
        const goBackButton = document.createElement('button');
        goBackButton.classList.add('btn', 'btn-primary');
        goBackButton.textContent = goBackString;
        goBackButton.dataset.action = 'go-back';
        goBackButton.value = this.bankCmId;
        this.setFooter(goBackButton);

        const bodyFragment = Fragment.loadFragment('mod_capquiz', 'switch_question_bank', this.contextId, {
            quizcmid: this.quizCmId,
            bankcmid: this.bankCmId,
        });
        this.setBody(bodyFragment);

        await this.getBodyPromise();
        await AutoComplete.enhance(
            selector,
            false,
            'core_question/question_banks_datasource',
            placeholderString,
            false,
            true,
            '',
            true
        );

        // Hide the selection element as we don't need it.
        const selection = document.querySelector('.search-banks .form-autocomplete-selection');
        if (selection instanceof HTMLElement) {
            selection.classList.add('d-none');
        }
    }
}

/**
 * Initialize.
 *
 * @param {number} contextId
 * @param {number} quizCmId
 * @param {number} bankCmId
 */
export const init = (contextId, quizCmId, bankCmId) => {
    QuestionBankModal.registerModalType();
    document.addEventListener('click', async event => {
        const target = event.target.closest('.menu [data-action="questionbank"]');
        if (target instanceof HTMLElement) {
            event.preventDefault();
            await QuestionBankModal.create({
                contextId: contextId,
                quizCmId: quizCmId,
                bankCmId: bankCmId,
                title: target.dataset.header,
                templateContext: {
                    hidden: true,
                },
            });
        }
    });
};
