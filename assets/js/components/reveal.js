/**
 * Swap Design - Scroll Reveal
 *
 * Hybrid: GSAP ScrollTrigger when available, else IntersectionObserver.
 * Marks elements as `.is-visible` when they enter the viewport.
 * Add `data-reveal` to any element you want animated on scroll.
 *
 * @package SwapDesign
 */
(function () {
    'use strict';

    var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReduced) {
        // Make everything visible immediately
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-reveal], .reveal').forEach(function (el) {
                el.classList.add('is-visible');
                el.style.opacity = '1';
                el.style.transform = 'none';
            });
        });
        return;
    }

    function initIOReveal() {
        var elements = document.querySelectorAll('[data-reveal], .reveal');
        if (!elements.length) return;

        var observer = new IntersectionObserver(
            function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        // Stagger delay from data-reveal-delay (ms) or 0
                        var delay = parseInt(entry.target.getAttribute('data-reveal-delay'), 10) || 0;
                        setTimeout(function () {
                            entry.target.classList.add('is-visible');
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'none';
                        }, delay);
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.1, rootMargin: '0px 0px -50px 0px' }
        );

        elements.forEach(function (el) {
            observer.observe(el);
        });
    }

    function initGSAPReveal() {
        var gsap = window.SwapGSAP.gsap;
        var ST   = window.SwapGSAP.ScrollTrigger;
        if (!gsap || !ST) return;

        var elements = document.querySelectorAll('[data-reveal], .reveal');
        if (!elements.length) return;

        elements.forEach(function (el) {
            gsap.fromTo(el,
                { opacity: 0, y: 30 },
                {
                    opacity: 1,
                    y: 0,
                    duration: 0.8,
                    ease: 'power2.out',
                    scrollTrigger: { trigger: el, start: 'top 88%', toggleActions: 'play none none none' },
                }
            );
        });
    }

    /* Once-guard: init may be triggered by both DOMContentLoaded and
       the swap:gsap-ready event. Register animations exactly once to
       avoid double-animation on the same [data-reveal] elements. */
    var started = false;
    var usedGSAP = false;
    function init() {
        if (started) return;
        started = true;

        if (window.SwapGSAP && window.SwapGSAP.ready()) {
            usedGSAP = true;
            initGSAPReveal();
        } else {
            // GSAP not loaded yet (or unavailable) — use IO fallback
            initIOReveal();
        }

        // Let page-specific reveal handlers know this global system owns
        // [data-reveal], so they don't double-animate the same nodes.
        window.SwapRevealState = { loaded: true, usedGSAP: usedGSAP };
    }

    // Run when DOM ready; if GSAP is still loading, the custom event fires it
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            init();
            // Also fire if GSAP loads later
            document.addEventListener('swap:gsap-ready', init);
        });
    } else {
        init();
        document.addEventListener('swap:gsap-ready', init);
    }
})();
