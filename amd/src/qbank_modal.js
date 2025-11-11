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

export class QuestionBankModal extends Modal {
    /**
     * @param {Object} modalConfig
     */
    configure(modalConfig) {
        modalConfig.large = true;
        modalConfig.show = true;
        modalConfig.removeOnClose = true;
        this.contextId = modalConfig.contextId;
        this.capquizCmId = modalConfig.capquizCmId;
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
            capquizcmid: this.capquizCmId,
            bankcmid: this.bankCmId,
        }));
    }

    /**
     * Register event listeners.
     */
    registerEventListeners() {
        super.registerEventListeners(this);

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
            event.preventDefault();
            this.reloadBodyContent(target.search);
        });

        this.getRoot().on(ModalEvents.bodyRendered, () => {
            FormChangeChecker.disableAllChecks();
        });
    }
}

/**
 * Initialize.
 *
 * @param {number} contextId
 * @param {number} capquizCmId
 * @param {number} bankCmId
 */
export const init = (contextId, capquizCmId, bankCmId) => {
    QuestionBankModal.registerModalType();
    document.addEventListener('click', async event => {
        const target = event.target.closest('.menu [data-action="questionbank"]');
        if (target instanceof HTMLElement) {
            event.preventDefault();
            await QuestionBankModal.create({
                contextId: contextId,
                capquizCmId: capquizCmId,
                bankCmId: bankCmId,
                title: target.dataset.header,
                addOnPage: target.dataset.addonpage,
                templateContext: {
                    hidden: true,
                },
                large: true,
            });
        }
    });
};
