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
            }
        }

        /**
         * Update the character counter display.
         */
        updateCounter() {
            const remaining = this.maxLength - this.textarea.value.length;
            this.counter.textContent = `Characters remaining: ${remaining}/${this.maxLength}`;

            // Change color when approaching limit.
            if (remaining < this.maxLength * 0.2) {
                this.counter.style.color = 'orange';
            } else {
                this.counter.style.color = '';
            }

            // Turn red when at or over limit.
            if (remaining <= 0) {
                this.counter.style.color = 'red';
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