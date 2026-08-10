/**
 * Swap Design - Toast Notification System
 *
 * Lightweight, auto-dismiss toast stack. Respects prefers-reduced-motion.
 *
 * Usage:
 *   window.SwapToast.show({ type: 'success', title: 'Saved', message: 'Changes saved.' });
 *   window.SwapToast.show({ type: 'error', title: 'Error', message: 'Something went wrong.', duration: 5000 });
 *
 * @package SwapDesign
 */
(function () {
    'use strict';

    var ICONS = {
        success: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        error:   '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
        warning: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        info:    '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
    };

    var ICON_COLORS = { success: 'var(--ds-success)', error: 'var(--ds-error)', warning: 'var(--ds-warning)', info: 'var(--ds-info)' };

    function show(opts) {
        var type = opts.type || 'info';
        var title = opts.title || '';
        var message = opts.message || '';
        var duration = opts.duration || 3500;

        var container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        var toast = document.createElement('div');
        toast.className = 'toast toast--' + type;
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        toast.innerHTML =
            '<div class="toast__icon" style="color:' + ICON_COLORS[type] + '">' + (ICONS[type] || ICONS.info) + '</div>' +
            '<div class="toast__body">' +
                (title ? '<p class="toast__title">' + title + '</p>' : '') +
                (message ? '<p class="toast__message">' + message + '</p>' : '') +
            '</div>' +
            '<button class="toast__close" aria-label="Dismiss notification">&times;</button>';

        container.appendChild(toast);

        // Trigger enter animation
        requestAnimationFrame(function () {
            requestAnimationFrame(function () { toast.classList.add('is-visible'); });
        });

        // Close on click
        toast.querySelector('.toast__close').addEventListener('click', function () { dismiss(toast); });

        // Auto-dismiss
        var timer = setTimeout(function () { dismiss(toast); }, duration);

        function dismiss(t) {
            clearTimeout(timer);
            t.classList.remove('is-visible');
            t.classList.add('is-exiting');
            setTimeout(function () { t.remove(); }, 300);
        }
    }

    window.SwapToast = { show: show };
})();
