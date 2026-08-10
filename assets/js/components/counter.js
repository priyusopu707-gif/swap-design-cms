/**
 * Swap Design - Number Counter
 *
 * Animate [data-counter] numbers when scrolled into view.
 * Supports decimals via data-counter-decimals, and a suffix/prefix
 * via data-counter-suffix / data-counter-prefix.
 * Uses requestAnimationFrame (no GSAP dependency).
 *
 * @package SwapDesign
 */
(function () {
    'use strict';

    var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var counters = document.querySelectorAll('[data-counter]');
    if (!counters.length) return;

    function format(n, decimals, locale) {
        return n.toLocaleString(locale, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        });
    }

    function run(el) {
        var target = parseFloat(el.getAttribute('data-counter'));
        if (isNaN(target)) return;
        var decimals = parseInt(el.getAttribute('data-counter-decimals'), 10) || 0;
        var prefix = el.getAttribute('data-counter-prefix') || '';
        var suffix = el.getAttribute('data-counter-suffix') || '';
        var duration = 2000;
        var start = null;

        function step(ts) {
            if (!start) start = ts;
            var progress = Math.min((ts - start) / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 3); // easeOutCubic
            var value = target * eased;
            el.textContent = prefix + format(value, decimals) + suffix;
            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                el.textContent = prefix + format(target, decimals) + suffix;
            }
        }

        requestAnimationFrame(step);
    }

    // Reduced motion: set final value immediately
    if (prefersReduced) {
        counters.forEach(function (el) {
            var target = parseFloat(el.getAttribute('data-counter'));
            var decimals = parseInt(el.getAttribute('data-counter-decimals'), 10) || 0;
            var prefix = el.getAttribute('data-counter-prefix') || '';
            var suffix = el.getAttribute('data-counter-suffix') || '';
            if (!isNaN(target)) {
                el.textContent = prefix + format(target, decimals) + suffix;
            }
        });
        return;
    }

    var observer = new IntersectionObserver(
        function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    run(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.5 }
    );

    counters.forEach(function (el) { observer.observe(el); });
})();
