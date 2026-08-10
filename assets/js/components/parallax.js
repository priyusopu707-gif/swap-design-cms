/**
 * Swap Design - Parallax Depth
 *
 * Light parallax on [data-parallax] elements. Moves them at a different
 * rate than the page scroll for depth illusion. Falls back gracefully.
 * Respects prefers-reduced-motion.
 *
 * @package SwapDesign
 */
(function () {
    'use strict';

    var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReduced) return;

    var elements = document.querySelectorAll('[data-parallax]');
    if (!elements.length) return;

    function update() {
        var scrollTop = window.scrollY;
        elements.forEach(function (el) {
            var speed = parseFloat(el.getAttribute('data-parallax')) || 0.3;
            var rect = el.getBoundingClientRect();
            var offset = (rect.top + scrollTop - window.innerHeight / 2) * speed;
            el.style.transform = 'translateY(' + (-offset * 0.1) + 'px)';
        });
    }

    window.addEventListener('scroll', update, { passive: true });
    update();
})();
