/**
 * Swap Design - Sticky Header & Navigation
 *
 * Handles:
 *   Scroll shadow on header
 *   Services dropdown (hover on desktop, click on mobile)
 *   Mobile menu toggle
 *   Keyboard navigation
 *   ARIA state management
 */
(() => {
    const header     = document.getElementById('main-header');
    const toggle     = header?.querySelector('.main-header__toggle');
    const nav        = header?.querySelector('.main-nav');
    const menu       = document.getElementById('main-menu');
    const dropdowns  = menu?.querySelectorAll('.main-nav__item--has-dropdown');

    if (!header || !toggle || !nav || !menu) return;

    let isDesktop  = window.matchMedia('(min-width: 1024px)').matches;
    let isOpen     = false;
    let overlay    = null;

    /* ---- Create overlay for mobile ---- */
    function createOverlay() {
        overlay = document.createElement('div');
        overlay.className = 'main-nav__overlay';
        overlay.addEventListener('click', closeMenu);
        document.body.appendChild(overlay);
    }

    function removeOverlay() {
        if (overlay) {
            overlay.removeEventListener('click', closeMenu);
            overlay.remove();
            overlay = null;
        }
    }

    /* ---- Scroll shadow ---- */
    function onScroll() {
        const scrolled = window.scrollY > 10;
        header.classList.toggle('main-header--scrolled', scrolled);
    }

    /* ---- Focus trap: collect focusable elements inside the open mobile menu ---- */
    function getMenuFocusables() {
        if (!nav) return [];
        const selector = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
        return Array.from(nav.querySelectorAll(selector)).filter((el) => el.offsetParent !== null || el === document.activeElement);
    }

    /* ---- Keyboard: prevent Tab escaping the open mobile menu ---- */
    function trapTab(e) {
        if (!isOpen || e.key !== 'Tab') return;

        /* Menu is considered closed if nav not visually open (desktop) */
        const menuOpen = nav.classList.contains('main-nav--open');
        if (!menuOpen) return;

        const focusables = getMenuFocusables();
        if (!focusables.length) {
            e.preventDefault();
            return;
        }

        const first = focusables[0];
        const last  = focusables[focusables.length - 1];
        const active = document.activeElement;

        /* Shift+Tab from first wraps to last */
        if (e.shiftKey && (active === first || active === nav || active === document.body)) {
            e.preventDefault();
            last.focus();
            return;
        }
        /* Tab from last wraps to first */
        if (!e.shiftKey && (active === last || active === document.body || active === nav)) {
            e.preventDefault();
            first.focus();
        }
    }

    /* ---- Open mobile menu ---- */
    function openMenu() {
        isOpen = true;
        toggle.setAttribute('aria-expanded', 'true');
        nav.classList.add('main-nav--open');
        document.body.style.overflow = 'hidden';
        if (overlay) overlay.classList.add('main-nav__overlay--visible');

        /* Move focus into menu (first focusable item), not the toggle */
        const focusables = getMenuFocusables();
        if (focusables.length) {
            focusables[0].focus();
        } else {
            toggle.focus();
        }
    }

    /* ---- Close mobile menu ---- */
    function closeMenu() {
        isOpen = false;
        toggle.setAttribute('aria-expanded', 'false');
        nav.classList.remove('main-nav--open');
        document.body.style.overflow = '';
        if (overlay) overlay.classList.remove('main-nav__overlay--visible');

        /* Close all open dropdowns */
        dropdowns?.forEach((dd) => {
            dd.classList.remove('main-nav__item--open');
            const btn = dd.querySelector('.main-nav__dropdown-toggle');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        });

        /* Return focus to toggle after closing */
        if (document.activeElement && nav.contains(document.activeElement)) {
            toggle.focus();
        }
    }

    /* ---- Toggle dropdown (mobile) ---- */
    function toggleDropdown(item) {
        const isCurrentlyOpen = item.classList.contains('main-nav__item--open');

        /* Close all other dropdowns */
        dropdowns?.forEach((dd) => {
            dd.classList.remove('main-nav__item--open');
            const btn = dd.querySelector('.main-nav__dropdown-toggle');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        });

        /* Toggle this one */
        if (!isCurrentlyOpen) {
            item.classList.add('main-nav__item--open');
            const btn = item.querySelector('.main-nav__dropdown-toggle');
            if (btn) btn.setAttribute('aria-expanded', 'true');
        }
    }

    /* ---- Keyboard: trap Tab while open, close menu on Escape ---- */
    function onKeyDown(e) {
        if (e.key === 'Tab') {
            trapTab(e);
            return;
        }
        if (e.key === 'Escape' && isOpen) {
            closeMenu();
            toggle.focus();
        }
    }

    /* ---- Keyboard: open/close dropdown with Enter/Space ---- */
    function onDropdownKeyDown(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            toggleDropdown(this.closest('.main-nav__item--has-dropdown'));
        }
    }

    /* ---- Handle link clicks: close mobile menu ---- */
    function onLinkClick(e) {
        if (!isOpen) return;
        const link = e.target.closest('.main-nav__link:not(.main-nav__dropdown-toggle), .main-nav__dropdown-link');
        if (link) {
            /* Allow dropdown toggle clicks to pass through */
            if (link.classList.contains('main-nav__dropdown-toggle')) return;
            closeMenu();
        }
    }

    /* ---- Handle resize: switch between desktop/mobile behavior ---- */
    function onResize() {
        const nowDesktop = window.matchMedia('(min-width: 1024px)').matches;
        if (nowDesktop !== isDesktop) {
            isDesktop = nowDesktop;
            if (isDesktop) {
                closeMenu();
                removeOverlay();
            } else {
                createOverlay();
            }
        }
    }

    /* ---- Event Listeners ---- */
    toggle.addEventListener('click', () => (isOpen ? closeMenu() : openMenu()));

    menu.addEventListener('click', onLinkClick);

    document.addEventListener('keydown', onKeyDown);

    /* Dropdown toggle buttons (for mobile click behavior) */
    dropdowns?.forEach((item) => {
        const toggleBtn = item.querySelector('.main-nav__dropdown-toggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', (e) => {
                if (!isDesktop) {
                    e.preventDefault();
                    toggleDropdown(item);
                }
            });
            toggleBtn.addEventListener('keydown', onDropdownKeyDown);
        }
    });

    /* Scroll event */
    window.addEventListener('scroll', onScroll, { passive: true });

    /* Resize handler */
    window.addEventListener('resize', debounce(onResize, 150));

    /* Init overlay on mobile */
    if (!isDesktop) createOverlay();

    /* Initial scroll check */
    onScroll();
})();
