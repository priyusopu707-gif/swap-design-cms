/**
 * Swap Design - Magnetic Buttons
 *
 * Elements with [data-magnetic] attract toward the cursor.
 * Uses GSAP quickTo when available for spring-like snap, else CSS transform.
 * Respects prefers-reduced-motion.
 *
 * @package SwapDesign
 */
(function () {
    'use strict';

    var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReduced) return;

    var elements = document.querySelectorAll('[data-magnetic]');
    if (!elements.length) return;

    var hasGSAP = window.SwapGSAP && window.SwapGSAP.ready();

    elements.forEach(function (el) {
        var strength = parseFloat(el.getAttribute('data-magnetic')) || 0.25;

        if (hasGSAP) {
            var gsap = window.SwapGSAP.gsap;
            var xTo = gsap.quickTo(el, 'x', { duration: 0.3, ease: 'power3.out' });
            var yTo = gsap.quickTo(el, 'y', { duration: 0.3, ease: 'power3.out' });

            el.addEventListener('mousemove', function (e) {
                var rect = el.getBoundingClientRect();
                var x = (e.clientX - rect.left - rect.width / 2) * strength;
                var y = (e.clientY - rect.top - rect.height / 2) * strength;
                xTo(x);
                yTo(y);
            });
            el.addEventListener('mouseleave', function () {
                xTo(0);
                yTo(0);
            });
        } else {
            el.addEventListener('mousemove', function (e) {
                var rect = el.getBoundingClientRect();
                var x = (e.clientX - rect.left - rect.width / 2) * strength;
                var y = (e.clientY - rect.top - rect.height / 2) * strength;
                el.style.transform = 'translate(' + x + 'px, ' + y + 'px)';
            });
            el.addEventListener('mouseleave', function () {
                el.style.transform = 'translate(0, 0)';
            });
        }
    });
})();
