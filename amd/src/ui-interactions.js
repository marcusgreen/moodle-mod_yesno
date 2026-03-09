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
 * UI interaction handlers for mod_yesno (help section toggle).
 *
 * @module     mod_yesno/ui-interactions
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    "use strict";

    /**
     * UI Interactions for yesno module.
     */
    class UIInteractions {
        /**
         * Initialize UI interactions.
         */
        static init() {
            UIInteractions.initHelpToggle();
        }

        /**
         * Initialize help toggle functionality.
         */
        static initHelpToggle() {
            const helpToggle = document.getElementById('help-toggle');
            const helpContent = document.getElementById('help-content');

            if (helpToggle && helpContent) {
                helpToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const isHidden = helpContent.style.display === 'none';

                    if (isHidden) {
                        helpContent.style.display = 'block';
                        helpToggle.querySelector('.help-toggle-text').textContent = '- Hide Help';
                        helpToggle.classList.add('active');
                    } else {
                        helpContent.style.display = 'none';
                        helpToggle.querySelector('.help-toggle-text').textContent = '+ Show Help';
                        helpToggle.classList.remove('active');
                    }
                });
            }
        }
    }

    return {
        /**
         * Initialize the UI interactions module.
         */
        init: UIInteractions.init
    };
});
