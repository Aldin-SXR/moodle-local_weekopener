/**
 * Keeps an autocomplete's suggestions where they were after a pick.
 *
 * Core re-renders the list after every selection and scrolls back to its first
 * item, which loses the place when skipping several sections down a long list.
 * Relies on core_form/form-autocomplete's markup: a ul.form-autocomplete-suggestions
 * that is replaced on each update, and aria-selected marking the active option.
 *
 * @module local_weekopener/keep_scroll
 */

import $ from 'jquery';

const OPTION = '[role="option"]';
const LIST = 'ul.form-autocomplete-suggestions';

/**
 * @param {string} fieldId Id of the autocomplete's original select.
 */
export const init = (fieldId) => {
    const field = document.getElementById(`fitem_${fieldId}`);
    if (!field) {
        return;
    }

    let pending = null;

    const remember = (option) => {
        const list = option.closest(LIST);
        const visible = [...list.querySelectorAll(`${OPTION}:not([aria-hidden])`)];
        pending = {list, scrollTop: list.scrollTop, index: visible.indexOf(option)};
    };

    // Capture phase, so the position is read before core handles the pick.
    field.addEventListener('click', (e) => {
        const option = e.target.closest(`${LIST} ${OPTION}`);
        if (option) {
            remember(option);
        }
    }, true);
    field.addEventListener('keydown', (e) => {
        const option = field.querySelector(`${LIST} ${OPTION}[aria-selected="true"]`);
        if (e.key === 'Enter' && option) {
            remember(option);
        }
    }, true);

    new MutationObserver(() => {
        const list = field.querySelector(LIST);
        if (!pending || !list || list === pending.list) {
            return;
        }
        const {scrollTop, index} = pending;
        pending = null;

        // Finish core's scroll to the first item, then undo it.
        $(list).stop(true, true);
        list.scrollTop = scrollTop;

        // Make the option now in the picked one's place the active one, so the
        // keyboard carries on from here too.
        const all = [...list.querySelectorAll(OPTION)];
        const visible = all.filter((option) => !option.hasAttribute('aria-hidden'));
        const active = visible[Math.min(index, visible.length - 1)];
        if (!active) {
            return;
        }
        all.forEach((option) => {
            option.setAttribute('aria-selected', 'false');
            option.id = '';
        });
        active.setAttribute('aria-selected', 'true');
        active.id = `${list.id}-${all.indexOf(active)}`;
        field.querySelector('input[data-fieldtype="autocomplete"]')?.setAttribute('aria-activedescendant', active.id);
    }).observe(field, {childList: true, subtree: true});
};
