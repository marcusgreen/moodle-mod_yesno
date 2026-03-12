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
 * Character counter for the question textarea in mod_yesno.
 *
 * @module     mod_yesno/charcounter
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    "use strict";

    /**
     * Character counter for the yesno module.
     */
    class CharCounter {
        /**
         * Create a character counter instance.
         *
         * @param {string} textareaSelector - The selector for the textarea element.
         * @param {string} counterSelector - The selector for the counter element.
         */
        constructor(textareaSelector, counterSelector) {
            this.textarea = document.querySelector(textareaSelector);
            this.counter = document.querySelector(counterSelector);
            this.maxLength = parseInt(this.textarea.getAttribute('maxlength'));

            // Initialize the counter.
            this.init();
        }

        /**
         * Initialize the character counter.
         */
        init() {
            if (this.textarea && this.counter) {
                // Set initial counter value.
                this.updateCounter();

                // Add input event listener.
                this.textarea.addEventListener('input', () => this.updateCounter());

                // Submit form on Enter (without Shift) if text has been entered.
                this.textarea.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && !e.shiftKey && this.textarea.value.trim().length > 0) {
                        e.preventDefault();
                        this.textarea.closest('form').submit();
                    }
                });
            }
        }

        /**
         * Update the character counter display.
         */
        updateCounter() {
            const remaining = this.maxLength - this.textarea.value.length;
            this.counter.textContent = `Characters remaining: ${remaining}/${this.maxLength}`;

            // Remove all classes first.
            this.counter.classList.remove('warning', 'danger');

            // Add warning class when approaching limit (< 20%).
            if (remaining < this.maxLength * 0.2 && remaining > 0) {
                this.counter.classList.add('warning');
            }

            // Add danger class when at or over limit.
            if (remaining <= 0) {
                this.counter.classList.add('danger');
            }
        }
    }

    return {
        /**
         * Initialize the character counter module.
         */
        init: () => {
            new CharCounter('#student_question', '#char-counter');
        }
    };
});