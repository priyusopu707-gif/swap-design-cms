/**
 * Swap Design - 3D Tilt Cards
 *
 * Mouse-move perspective tilt on elements with [data-tilt].
 * Uses CSS transform (GPU-composited). No GSAP needed.
 * Respects prefers-reduced-motion.
 *
 * @package SwapDesign
 */
(function () {
    'use strict';

    var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReduced) return;

    var cards = document.querySelectorAll('[data-tilt]');
    if (!cards.length) return;

    cards.forEach(function (card) {
        var maxTilt = parseFloat(card.getAttribute('data-tilt')) || 10;

        card.addEventListener('mousemove', function (e) {
            var rect = card.getBoundingClientRect();
            var x = (e.clientX - rect.left) / rect.width - 0.5;
            var y = (e.clientY - rect.top) / rect.height - 0.5;
            card.style.transform =
                'perspective(1000px) rotateX(' + (-y * maxTilt) + 'deg) rotateY(' + (x * maxTilt) + 'deg) scale(1.02)';
        });

        card.addEventListener('mouseleave', function () {
            card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale(1)';
        });
    });
})();
