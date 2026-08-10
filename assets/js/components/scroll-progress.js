/**
 * Swap Design - Scroll Progress Bar
 *
 * Tracks page scroll and updates a CSS variable on a .scroll-progress element.
 * Pure CSS width driven by --scroll-progress.
 *
 * @package SwapDesign
 */
(function () {
    'use strict';

    var bar = document.querySelector('.scroll-progress');
    if (!bar) return;

    function update() {
        var scrollTop = window.scrollY || document.documentElement.scrollTop;
        var docHeight = document.documentElement.scrollHeight - window.innerHeight;
        var pct = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
        bar.style.width = pct + '%';
    }

    window.addEventListener('scroll', update, { passive: true });
    update();
})();
