/**
 * Swap Design - Drawer
 *
 * Slide-in panel from the right.
 * Open via data-drawer-target="drawer-id" on any trigger.
 * Close via [data-drawer-close] or clicking the overlay.
 *
 * @package SwapDesign
 */
(function () {
    'use strict';

    var lastFocused = null;

    function openDrawer(id) {
        var overlay = document.querySelector('[data-drawer-overlay="' + id + '"]');
        var drawer = document.getElementById(id);
        if (!drawer) return;

        lastFocused = document.activeElement;
        if (overlay) overlay.classList.add('is-open');
        drawer.classList.add('is-open');
        document.body.style.overflow = 'hidden';

        if (overlay) {
            overlay.addEventListener('click', function closeOnOverlay(e) {
                if (e.target === overlay) closeDrawer(id);
                overlay.removeEventListener('click', closeOnOverlay);
            });
        }
    }

    function closeDrawer(id) {
        var overlay = document.querySelector('[data-drawer-overlay="' + id + '"]');
        var drawer = document.getElementById(id);
        if (!drawer) return;

        if (overlay) overlay.classList.remove('is-open');
        drawer.classList.remove('is-open');
        document.body.style.overflow = '';
        if (lastFocused) lastFocused.focus();
    }

    document.addEventListener('click', function (e) {
        var openTrigger = e.target.closest('[data-drawer-target]');
        if (openTrigger) {
            openDrawer(openTrigger.getAttribute('data-drawer-target'));
            return;
        }
        var closeTrigger = e.target.closest('[data-drawer-close]');
        if (closeTrigger) {
            var drawer = closeTrigger.closest('.drawer');
            if (drawer) closeDrawer(drawer.id);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            var openDrawer_ = document.querySelector('.drawer.is-open');
            if (openDrawer_) closeDrawer(openDrawer_.id);
        }
    });
})();
