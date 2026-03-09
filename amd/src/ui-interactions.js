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
