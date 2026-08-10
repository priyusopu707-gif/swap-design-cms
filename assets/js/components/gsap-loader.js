/**
 * Swap Design - GSAP + ScrollTrigger Loader
 *
 * Lazy-loads GSAP and ScrollTrigger from CDN (deferred), registers the
 * plugin once, and exposes a global `window.SwapGSAP` facade. All other
 * animation modules check `window.SwapGSAP?.loaded` and degrade gracefully
 * to IntersectionObserver when GSAP is unavailable (offline / blocked).
 *
 * Performance: scripts only download when the browser supports them; we
 * gate heavy animation on `prefers-reduced-motion` at the module level.
 *
 * @package SwapDesign
 */
(function () {
    'use strict';

    var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    window.SwapGSAP = {
        loaded: false,
        gsap: null,
        ScrollTrigger: null,
        ready: function () {
            return this.loaded;
        },
    };

    // Respect reduced-motion: never load the animation library at all.
    if (prefersReduced) {
        return;
    }

    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = src;
            s.async = true;
            s.onload = resolve;
            s.onerror = reject;
            document.head.appendChild(s);
        });
    }

    function init() {
        if (!window.gsap) return;

        var ScrollTrigger = window.ScrollTrigger || null;
        if (ScrollTrigger) {
            gsap.registerPlugin(ScrollTrigger);
        }

        window.SwapGSAP.loaded = true;
        window.SwapGSAP.gsap = window.gsap;
        window.SwapGSAP.ScrollTrigger = ScrollTrigger;

        document.dispatchEvent(new CustomEvent('swap:gsap-ready'));
    }

    // Load GSAP core, then ScrollTrigger (both from the official CDN).
    loadScript('https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js')
        .then(function () {
            return loadScript('https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js');
        })
        .then(init)
        .catch(function () {
            // GSAP unavailable — animation modules fall back to IO reveal.
        });
})();
