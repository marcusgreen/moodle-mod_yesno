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
 * Import/export and delete helpers for the secrets editor in mod_yesno.
 *
 * @module     mod_yesno/secrets_io
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/notification', 'core/str'], function(Notification, Str) {
    "use strict";

    /**
     * Secrets import/export AMD module for mod_yesno.
     *
     * Handles the delete buttons for secret/clue pairs and the client-side
     * file reading for the import-secrets feature on the mod_form editing page.
     */
    class SecretsIO {
        /**
         * Initialise delete buttons and the import file-input listener.
         */
        static init() {
            // Fetch the button label then inject a delete button into every group.
            Str.get_string('deletesecret', 'yesno').then(function(label) {
                SecretsIO.initDeleteButtons(label);
            }).catch(function() {
                SecretsIO.initDeleteButtons('Delete');
            });

            const fileInput = document.getElementById('id_importsecrets_file');
            if (!fileInput) {
                return;
            }
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (!file) {
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(event) {
                    try {
                        const data = JSON.parse(event.target.result);
                        SecretsIO.loadSecrets(data);
                    } catch (err) {
                        Notification.alert('Import error', err.message);
                    }
                    // Reset so the same file can be re-selected.
                    fileInput.value = '';
                };
                reader.readAsText(file);
            });
        }

        /**
         * Inject a delete button into the secret field row for every group.
         *
         * @param {string} label Button label text.
         */
        static initDeleteButtons(label) {
            let index = 0;
            while (document.querySelector('[name="secret[' + index + ']"]')) {
                SecretsIO.addDeleteButton(index, label);
                index++;
            }
        }

        /**
         * Add a delete button to the fitem containing secret[index].
         *
         * @param {number} index Repeat-element index.
         * @param {string} label Button label.
         */
        static addDeleteButton(index, label) {
            const secretInput = document.querySelector('[name="secret[' + index + ']"]');
            if (!secretInput) {
                return;
            }
            const fitem = secretInput.closest('.fitem');
            if (!fitem) {
                return;
            }

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = label;
            btn.className = 'btn btn-outline-danger btn-sm mt-2 bg-danger-subtle';
            btn.addEventListener('click', function() {
                SecretsIO.deleteSecretRow(index);
            });

            // Place the button inline with the input using a flex row.
            const felement = fitem.querySelector('.felement') || fitem;
            felement.style.display = 'flex';
            felement.style.alignItems = 'center';
            felement.appendChild(btn);
        }

        /**
         * Disable and hide both rows (secret + clue) for the given index.
         *
         * Disabling the secret input removes it from the POST data so the
         * server-side save logic naturally skips it.
         *
         * @param {number} index
         */
        static deleteSecretRow(index) {
            // Secret input row.
            const secretInput = document.querySelector('[name="secret[' + index + ']"]');
            if (secretInput) {
                secretInput.disabled = true;
                const fitem = secretInput.closest('.fitem');
                if (fitem) {
                    fitem.style.display = 'none';
                }
            }

            // Clue editor row.
            const clueTextarea = document.getElementById('id_clue_' + index);
            if (clueTextarea) {
                const fitem = clueTextarea.closest('.fitem');
                if (fitem) {
                    fitem.style.display = 'none';
                }
            }
        }

        /**
         * Validate and populate form fields from a parsed JSON array.
         *
         * @param {Array} data Array of {secret, clue} objects.
         */
        static loadSecrets(data) {
            if (!Array.isArray(data) || data.length === 0) {
                Notification.alert('Import error', 'The JSON file must contain a non-empty array.');
                return;
            }

            const fieldCount = SecretsIO.getSecretFieldCount();
            if (data.length > fieldCount) {
                const extra = data.length - fieldCount;
                Notification.alert(
                    'More fields required',
                    'Your file has ' + data.length + ' secrets but the form only has ' + fieldCount +
                    ' field(s). Click "Add another secret" ' + extra + ' more time(s), then import again.'
                );
                return;
            }

            data.forEach(function(item, i) {
                SecretsIO.setSecretField(i, item.secret || '');
                SecretsIO.setClueField(i, item.clue || '');
            });
        }

        /**
         * Return the number of secret[N] inputs currently rendered.
         *
         * @return {number}
         */
        static getSecretFieldCount() {
            let count = 0;
            while (document.querySelector('[name="secret[' + count + ']"]')) {
                count++;
            }
            return count;
        }

        /**
         * Set a secret text input value.
         *
         * @param {number} index
         * @param {string} value
         */
        static setSecretField(index, value) {
            const el = document.querySelector('[name="secret[' + index + ']"]');
            if (el) {
                el.value = value;
            }
        }

        /**
         * Set a clue editor value, supporting both TinyMCE and plain textarea.
         *
         * @param {number} index
         * @param {string} value
         */
        static setClueField(index, value) {
            const editorId = 'id_clue_' + index;

            // TinyMCE 6 (Moodle 5.x tiny editor).
            if (window.tinymce) {
                const editor = window.tinymce.get(editorId);
                if (editor) {
                    editor.setContent(value);
                    return;
                }
            }

            // Fallback: set the underlying textarea directly.
            const textarea = document.getElementById(editorId);
            if (textarea) {
                textarea.value = value;
            }
        }
    }

    return {
        /**
         * @param {object} params unused, reserved for future options
         */
        init: SecretsIO.init
    };
});
