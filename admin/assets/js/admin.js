/**
 * Swap Design - Admin Panel JavaScript
 *
 * Handles:
 *   - Sidebar mobile toggle (open/close with overlay)
 *   - Sidebar collapse/expand (desktop toggle)
 *   - User dropdown menu
 *   - Flash message auto-dismiss
 *   - Confirm dialogs for destructive actions
 *   - CSRF token injection for AJAX requests
 */
document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initUserDropdown();
    initFlashMessages();
    initConfirmDialogs();
    initTableRowLinks();
});

/* ---- Sidebar Toggle (Mobile + Desktop Collapse) ---- */
function initSidebar() {
    const toggle  = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('admin-sidebar');
    if (!toggle || !sidebar) return;

    let overlay = null;

    function createOverlay() {
        overlay = document.createElement('div');
        overlay.className = 'admin-sidebar-overlay';
        overlay.style.cssText = `
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            z-index: 399;
            opacity: 0;
            transition: opacity 250ms ease;
        `;
        overlay.addEventListener('click', closeSidebar);
        document.body.appendChild(overlay);
        requestAnimationFrame(() => { overlay.style.opacity = '1'; });
    }

    function removeOverlay() {
        if (overlay) {
            overlay.removeEventListener('click', closeSidebar);
            overlay.style.opacity = '0';
            overlay.addEventListener('transitionend', () => overlay.remove());
        }
    }

    function openSidebar() {
        const isMobile = window.matchMedia('(max-width: 767px)').matches;
        if (isMobile) {
            sidebar.classList.add('admin-sidebar--open');
            toggle.setAttribute('aria-expanded', 'true');
            createOverlay();
        } else {
            sidebar.classList.toggle('admin-sidebar--collapsed');
        }
    }

    function closeSidebar() {
        sidebar.classList.remove('admin-sidebar--open');
        toggle.setAttribute('aria-expanded', 'false');
        removeOverlay();
    }

    toggle.addEventListener('click', () => {
        const isMobile = window.matchMedia('(max-width: 767px)').matches;
        if (isMobile) {
            if (sidebar.classList.contains('admin-sidebar--open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        } else {
            openSidebar();
        }
    });

    /* Close mobile sidebar on Escape */
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar.classList.contains('admin-sidebar--open')) {
            closeSidebar();
        }
    });
}

/* ---- User Dropdown ---- */
function initUserDropdown() {
    const dropdown = document.getElementById('user-dropdown');
    if (!dropdown) return;

    const toggle = dropdown.querySelector('.admin-topbar__user-toggle');
    const menu = document.getElementById('user-menu');
    if (!toggle || !menu) return;

    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        menu.classList.toggle('admin-topbar__user-menu--open');
        const isOpen = menu.classList.contains('admin-topbar__user-menu--open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    /* Close on outside click */
    document.addEventListener('click', (e) => {
        if (!dropdown.contains(e.target)) {
            menu.classList.remove('admin-topbar__user-menu--open');
            toggle.setAttribute('aria-expanded', 'false');
        }
    });

    /* Close on Escape */
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && menu.classList.contains('admin-topbar__user-menu--open')) {
            menu.classList.remove('admin-topbar__user-menu--open');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.focus();
        }
    });
}

/* ---- Flash Message Auto-Dismiss ---- */
function initFlashMessages() {
    const flashes = document.querySelectorAll('.admin-flash');
    flashes.forEach((flash) => {
        /* Auto-dismiss after 5 seconds */
        setTimeout(() => {
            flash.style.opacity = '0';
            flash.style.transform = 'translateY(-4px)';
            flash.style.transition = 'all 300ms ease';
            setTimeout(() => flash.remove(), 300);
        }, 5000);

        /* Close button */
        const closeBtn = flash.querySelector('.admin-flash__close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                flash.style.opacity = '0';
                flash.style.transform = 'translateY(-4px)';
                flash.style.transition = 'all 200ms ease';
                setTimeout(() => flash.remove(), 200);
            });
        }
    });
}

/* ---- Confirm Dialogs ---- */
function initConfirmDialogs() {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-confirm]');
        if (!btn) return;

        const message = btn.getAttribute('data-confirm') || 'Are you sure?';
        if (!confirm(message)) {
            e.preventDefault();
            e.stopPropagation();
        }
    });
}

/* ---- Table Row Click Navigation ---- */
function initTableRowLinks() {
    document.addEventListener('click', (e) => {
        const row = e.target.closest('tr[data-href]');
        if (!row) return;

        /* Don't navigate if clicking a link or button inside the row */
        if (e.target.closest('a, button, input, select, textarea')) return;

        window.location.href = row.getAttribute('data-href');
    });
}

/* ---- CSRF Token for AJAX ---- */
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

/**
 * Send an AJAX POST request with CSRF protection.
 */
async function adminFetch(url, options = {}) {
    const defaults = {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': window.csrfToken || getCsrfToken(),
        },
    };

    const merged = { ...defaults, ...options };
    if (merged.headers) {
        merged.headers = { ...defaults.headers, ...options.headers };
    }

    const response = await fetch(url, merged);

    if (!response.ok) {
        const error = await response.json().catch(() => ({ error: 'Request failed' }));
        throw new Error(error.error || 'Request failed');
    }

    return response.json();
}

/**
 * adminModalTrap — Reusable keyboard focus-trap for admin modals.
 *
 * Usage:
 *   const trap = adminModalTrap(modalEl, triggerEl);
 *   trap.activate();   // call on modal open
 *   trap.deactivate(); // call on modal close
 *
 * Parameters:
 *   modalEl   — the modal container element (must have tabindex="-1")
 *   triggerEl — the element that opened the modal (focus restored here on close)
 */
function adminModalTrap(modalEl, triggerEl) {
    /* Selectors for focusable interactive elements inside the modal */
    var FOCUSABLE_SELECTORS = [
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled]):not([type="hidden"])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
        '[contenteditable]'
    ].join(', ');

    var _trapListener = null;
    var _trigger = triggerEl || null;

    function getFocusableElements() {
        return Array.prototype.slice.call(
            modalEl.querySelectorAll(FOCUSABLE_SELECTORS)
        );
    }

    function handleKeydown(e) {
        if (e.key !== 'Tab') return;

        var focusable = getFocusableElements();
        if (focusable.length === 0) {
            /* No focusable children — trap focus on the modal itself */
            e.preventDefault();
            modalEl.focus();
            return;
        }

        var first = focusable[0];
        var last  = focusable[focusable.length - 1];

        if (e.shiftKey) {
            /* Shift+Tab: wrap from first → last */
            if (document.activeElement === first || document.activeElement === modalEl) {
                e.preventDefault();
                last.focus();
            }
        } else {
            /* Tab: wrap from last → first */
            if (document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        }
    }

    return {
        /**
         * Activate the trap — call when the modal opens.
         * Moves focus to the first focusable element (or the modal itself).
         */
        activate: function() {
            /* Remove any previous listener to avoid duplicates */
            this.deactivate();

            _trapListener = handleKeydown;
            document.addEventListener('keydown', _trapListener);

            /* Move focus into the modal */
            var focusable = getFocusableElements();
            if (focusable.length > 0) {
                /* Small delay so the browser has painted the modal */
                requestAnimationFrame(function() {
                    focusable[0].focus();
                });
            } else {
                requestAnimationFrame(function() {
                    modalEl.focus();
                });
            }
        },

        /**
         * Deactivate the trap — call when the modal closes.
         * Restores focus to the trigger element.
         */
        deactivate: function() {
            if (_trapListener) {
                document.removeEventListener('keydown', _trapListener);
                _trapListener = null;
            }

            /* Restore focus to the element that opened the modal */
            if (_trigger && typeof _trigger.focus === 'function') {
                _trigger.focus();
            }
        }
    };
}
