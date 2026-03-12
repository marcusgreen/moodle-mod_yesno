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
 * AMD module for AI-driven secret/clue pair generation in the yesno editing form.
 *
 * Flow when more repeat fields are needed than currently exist:
 *  1. Pairs are stored in sessionStorage.
 *  2. The hidden `secret_repeats` input is set to (pairs.length - 1) so that
 *     Moodle's +1 increment from the add-button produces exactly pairs.length fields.
 *  3. The "Add another secret" button is clicked — Moodle reloads the form.
 *  4. On the reloaded page this module detects the stored pairs, waits for
 *     TinyMCE editors to finish initialising, then populates all fields.
 *
 * @module     mod_yesno/generate_secrets
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/notification', 'mod_yesno/secrets_io', 'core/str'],
    function(Ajax, Notification, SecretsIO, Str) {
    'use strict';

    const SESSION_KEY = 'yesno_generated_pairs';
    const EDITOR_POLL_MS = 200;
    const EDITOR_POLL_MAX = 30; // 6 seconds total

    /**
     * Initialise the module.
     *
     * @param {number} contextid Moodle context ID passed from PHP.
     */
    function init(contextid) {
        // On every page load check for pairs stored by a previous generate cycle.
        applyStoredPairs();

        const btn = document.getElementById('id_generate_secrets_btn');
        if (!btn) {
            return;
        }

        btn.addEventListener('click', function(e) {
            e.preventDefault();

            const promptEl = document.getElementById('id_secretsprompt');
            const prompt = promptEl ? promptEl.value.trim() : '';
            if (!prompt) {
                return;
            }

            setButtonBusy(btn, true);

            Ajax.call([{
                methodname: 'mod_yesno_generate_secrets',
                args: {prompt: prompt, contextid: contextid},
                done: function(response) {
                    setButtonBusy(btn, false);
                    try {
                        const pairs = JSON.parse(response);
                        if (Array.isArray(pairs) && pairs.length > 0) {
                            insertPairs(pairs);
                        }
                    } catch(err) {
                        Notification.alert('Error', err.message);
                    }
                },
                fail: function(err) {
                    setButtonBusy(btn, false);
                    Notification.exception(err);
                },
            }]);
        });
    }

    /**
     * Insert generated pairs into the form.
     *
     * If the form already has enough fields, populate immediately (after editors
     * are ready).  Otherwise set secret_repeats so that one add-button click
     * produces exactly the right number of fields, store the pairs in
     * sessionStorage, and submit.
     *
     * @param {Array} pairs Array of {secret, clue} objects from the AI.
     */
    function insertPairs(pairs) {
        const fieldCount = SecretsIO.getSecretFieldCount();

        if (pairs.length <= fieldCount) {
            waitForEditorsAndLoad(pairs);
            return;
        }

        // Need more fields. Set secret_repeats so that Moodle's fixed +1
        // increment lands on exactly pairs.length after the reload.
        const repeatsInput = document.querySelector('[name="secret_repeats"]');
        if (repeatsInput) {
            repeatsInput.value = pairs.length - 1;
        }

        sessionStorage.setItem(SESSION_KEY, JSON.stringify(pairs));

        const addBtn = document.querySelector('[name="secret_add_fields"]');
        if (addBtn) {
            addBtn.click();
        } else {
            sessionStorage.removeItem(SESSION_KEY);
            Notification.alert(
                'Error',
                'Could not find the "Add another secret" button to expand the form.'
            );
        }
    }

    /**
     * Check sessionStorage for pairs saved by a previous generate-and-expand
     * cycle and populate the form once enough fields exist.
     */
    function applyStoredPairs() {
        const stored = sessionStorage.getItem(SESSION_KEY);
        if (!stored) {
            return;
        }
        try {
            const pairs = JSON.parse(stored);
            if (!Array.isArray(pairs) || pairs.length === 0) {
                sessionStorage.removeItem(SESSION_KEY);
                return;
            }
            const fieldCount = SecretsIO.getSecretFieldCount();
            if (pairs.length <= fieldCount) {
                sessionStorage.removeItem(SESSION_KEY);
                waitForEditorsAndLoad(pairs);
            }
            // If still not enough fields, leave in sessionStorage so the next
            // generate click can try again.
        } catch(e) {
            sessionStorage.removeItem(SESSION_KEY);
        }
    }

    /**
     * Poll until all TinyMCE clue editors are initialised, then load pairs.
     *
     * Falls back immediately if TinyMCE is not present on the page.
     *
     * @param {Array} pairs Array of {secret, clue} objects.
     */
    function waitForEditorsAndLoad(pairs) {
        if (!window.tinymce) {
            // No TinyMCE — populate straight away.
            SecretsIO.loadSecrets(pairs);
            return;
        }

        let attempts = 0;
        const timer = setInterval(function() {
            attempts++;
            let allReady = true;
            for (let i = 0; i < pairs.length; i++) {
                if (!window.tinymce.get('id_clue_' + i)) {
                    allReady = false;
                    break;
                }
            }
            if (allReady || attempts >= EDITOR_POLL_MAX) {
                clearInterval(timer);
                SecretsIO.loadSecrets(pairs);
            }
        }, EDITOR_POLL_MS);
    }

    /**
     * Toggle the generate button between normal and busy states.
     *
     * @param {HTMLElement} btn  The button element.
     * @param {boolean}     busy True to show busy state.
     */
    function setButtonBusy(btn, busy) {
        btn.disabled = busy;
        const key = busy ? 'generating' : 'generatesecrets';
        Str.get_string(key, 'yesno').then(function(label) {
            btn.textContent = label;
        }).catch(function() {
            btn.textContent = busy ? '...' : 'Generate Secrets';
        });
    }

    return {init: init};
});
