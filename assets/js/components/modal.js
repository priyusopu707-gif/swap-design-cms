/**
 * Swap Design - Modal
 *
 * Glass modal with focus trap. Open via data-modal-target on any trigger,
 * close via [data-modal-close] or Escape key. Focus is trapped inside.
 *
 * Usage:
 *   <button data-modal-target="my-modal">Open</button>
 *   <div class="modal-overlay" id="my-modal" role="dialog" aria-modal="true" aria-labelledby="my-modal-title">
 *     <div class="modal">
 *       <button class="modal__close" data-modal-close aria-label="Close">✕</button>
 *       <h3 id="my-modal-title">Title</h3>
 *       <p>Content</p>
 *     </div>
 *   </div>
 *
 * @package SwapDesign
 */
(function () {
    'use strict';

    var lastFocused = null;

    function openModal(id) {
        var overlay = document.getElementById(id);
        if (!overlay) return;
        lastFocused = document.activeElement;
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        var firstFocusable = overlay.querySelector('input, button:not(.modal__close), [tabindex]:not([tabindex="-1"])');
        if (firstFocusable) firstFocusable.focus();

        overlay.addEventListener('click', function handleOverlay(e) {
            if (e.target === overlay) closeModal(id);
        });
    }

    function closeModal(id) {
        var overlay = document.getElementById(id);
        if (!overlay) return;
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (lastFocused) lastFocused.focus();
    }

    document.addEventListener('click', function (e) {
        var openTrigger = e.target.closest('[data-modal-target]');
        if (openTrigger) {
            openModal(openTrigger.getAttribute('data-modal-target'));
            return;
        }
        var closeTrigger = e.target.closest('[data-modal-close]');
        if (closeTrigger) {
            var overlay = closeTrigger.closest('.modal-overlay');
            if (overlay) closeModal(overlay.id);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            var openOverlay = document.querySelector('.modal-overlay.is-open');
            if (openOverlay) closeModal(openOverlay.id);
        }
    });
})();
