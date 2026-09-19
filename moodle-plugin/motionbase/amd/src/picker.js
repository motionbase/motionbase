// This file is part of the MotionBase plugin for Moodle.

/**
 * "Add from MotionBase": count what is ticked, keep the button off until
 * something is, and narrow the list while typing.
 *
 * @module     filter_motionbase/picker
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString} from 'core/str';

export const init = () => {
    const form = document.getElementById('mbpicker-picker');
    if (!form) {
        return;
    }

    const count = form.querySelector('[data-count]');
    const submit = form.querySelector('[data-submit]');
    const search = form.querySelector('[data-search-input]');
    const none = form.querySelector('[data-nomatch]');
    const ticked = () => form.querySelectorAll('input[name="items[]"]:checked').length;

    // Strings arrive in their own time; only the answer to the latest tick counts.
    let latest = 0;
    const update = () => {
        const n = ticked();
        const mine = ++latest;
        if (submit) {
            submit.disabled = n === 0;
        }
        const key = n === 0 ? 'selectednone' : (n === 1 ? 'selectedone' : 'selected');
        getString(key, 'filter_motionbase', n).then(text => {
            if (mine === latest) {
                count.textContent = text;
            }
            return text;
        }).catch(() => null);
    };

    form.addEventListener('change', update);

    form.addEventListener('submit', e => {
        if (ticked() === 0) {
            e.preventDefault();
            return;
        }
        // Creating assignments takes a moment; a second click would add them twice.
        submit.disabled = true;
        getString('adding', 'filter_motionbase').then(text => {
            submit.textContent = text;
            return text;
        }).catch(() => null);
    });

    // The browser may bring ticks back on "back".
    update();

    if (!search) {
        return;
    }

    search.addEventListener('input', () => {
        const term = search.value.trim().toLowerCase();
        let anyTopic = false;

        form.querySelectorAll('[data-topic]').forEach(topic => {
            let anyOption = false;

            topic.querySelectorAll('[data-option]').forEach(option => {
                const match = !term || option.dataset.search.includes(term);
                option.hidden = !match;
                anyOption = anyOption || match;
            });

            // A matching lesson opens its list; its chapter stays in view for context.
            topic.querySelectorAll('[data-chapter]').forEach(chapter => {
                const list = chapter.querySelector('details');
                if (!list) {
                    return;
                }
                const hit = !!term && [...list.querySelectorAll('[data-option]')].some(o => !o.hidden);
                list.open = hit || !!list.querySelector('input:checked');
                list.hidden = !!term && !hit;
                if (hit) {
                    chapter.querySelector('[data-option]').hidden = false;
                }
            });

            topic.querySelectorAll('[data-group]').forEach(group => {
                group.hidden = ![...group.querySelectorAll('[data-option]')].some(o => !o.hidden);
            });

            topic.hidden = !anyOption;
            anyTopic = anyTopic || anyOption;
        });

        none.hidden = anyTopic;
    });
};
