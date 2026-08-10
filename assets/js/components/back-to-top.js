/**
 * Swap Design - Back to Top Button
 *
 * Shows button after scrolling past 400px. Smooth scroll to top.
 *
 * @package SwapDesign
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var btn = document.querySelector('.back-to-top');
        if (!btn) return;

        window.addEventListener('scroll', function () {
            if (window.scrollY > 400) {
                btn.classList.add('is-visible');
            } else {
                btn.classList.remove('is-visible');
            }
        }, { passive: true });

        btn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });
})();
