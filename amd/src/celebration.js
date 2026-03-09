define([], function() {
    "use strict";

    var CONFETTI_COLORS = [
        '#ffd700', '#ff6347', '#00ced1', '#ff69b4',
        '#7fff00', '#ff8c00', '#da70d6', '#00bfff'
    ];
    var PIECE_COUNT = 80;
    var DURATION_MS = 4000;

    /**
     * Celebration effects for the yesno win state.
     */
    var Celebration = {

        /**
         * Initialize: launch confetti if the win panel is present.
         */
        init: function() {
            if (document.getElementById('yesno-win-celebration')) {
                Celebration.launchConfetti();
            }
        },

        /**
         * Create and animate confetti particles.
         */
        launchConfetti: function() {
            var container = document.createElement('div');
            container.className = 'yesno-confetti-container';
            document.body.appendChild(container);

            for (var i = 0; i < PIECE_COUNT; i++) {
                Celebration.createPiece(container, i);
            }

            // Remove container after animation completes.
            setTimeout(function() {
                if (container.parentNode) {
                    container.parentNode.removeChild(container);
                }
            }, DURATION_MS + 500);
        },

        /**
         * Create a single confetti piece.
         *
         * @param {HTMLElement} container Parent container element.
         * @param {number} index Piece index used to stagger delays.
         */
        createPiece: function(container, index) {
            var piece = document.createElement('div');
            piece.className = 'yesno-confetti-piece';

            var color = CONFETTI_COLORS[index % CONFETTI_COLORS.length];
            var left = Math.random() * 100;
            var delay = Math.random() * 1.5;
            var duration = 2.5 + Math.random() * 1.5;
            var size = 7 + Math.floor(Math.random() * 8);

            piece.style.left = left + '%';
            piece.style.backgroundColor = color;
            piece.style.width = size + 'px';
            piece.style.height = size + 'px';
            piece.style.animationDuration = duration + 's';
            piece.style.animationDelay = delay + 's';

            // Alternate circles and rectangles for variety.
            if (index % 3 === 0) {
                piece.style.borderRadius = '50%';
            }

            container.appendChild(piece);
        }
    };

    return {
        /**
         * Initialize the celebration module.
         */
        init: Celebration.init
    };
});
