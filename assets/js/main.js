/**
 * Swap Design - Main Script
 *
 * Global initialization and shared functionality:
 *   - Smooth scroll for anchor links (respects prefers-reduced-motion)
 *   - IntersectionObserver scroll animations ([data-animate])
 *   - Focus management for main-content landmark on page load
 *   - Accessibility: autofocus #main-content when Skip Link used
 */
document.addEventListener('DOMContentLoaded', () => {
    initSmoothScroll();
    initAnimations();
    initMainContentFocus();
    initSkipLinkFocus();
});

/**
 * Smooth scroll for anchor links.
 * Respects reduced-motion preference.
 */
function initSmoothScroll() {
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const behavior = prefersReduced ? 'auto' : 'smooth';

    document.addEventListener('click', (e) => {
        const link = e.target.closest('a[href^="#"]');
        if (!link) return;

        const href = link.getAttribute('href');
        if (href === '#') return;

        const target = document.querySelector(href);
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior, block: 'start' });
            target.focus({ preventScroll: true });
        }
    });
}

/**
 * Scroll-based reveal animations.
 * Elements with [data-animate] are observed and receive
 * the .animated class when they enter the viewport.
 */
function initAnimations() {
    const animatedElements = $$('[data-animate]');

    if (!animatedElements.length) return;

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.1, rootMargin: '0px 0px -40px 0px' }
    );

    animatedElements.forEach((el) => observer.observe(el));
}

/**
 * When the page loads with a hash fragment, scroll to and
 * focus the main-content element for keyboard users after
 * skip-link navigation.
 */
function initMainContentFocus() {
    const main = document.getElementById('main-content');
    if (!main) return;

    /* If navigated via skip-link or URL hash, ensure main is reachable */
    window.addEventListener('hashchange', () => {
        const hash = window.location.hash;
        if (hash === '#main-content') {
            main.focus({ preventScroll: true });
        }
    });
}

/**
 * Handle skip-link: shift focus into main-content.
 * The skip-link href is #main-content, so when clicked the
 * browser naturally scrolls there. This ensures the element
 * is programmatically focusable.
 */
function initSkipLinkFocus() {
    const skipLink = document.querySelector('.skip-link');
    const main = document.getElementById('main-content');

    if (!skipLink || !main) return;

    skipLink.addEventListener('click', () => {
        /* Small delay to let the browser finish scrolling */
        requestAnimationFrame(() => {
            main.focus({ preventScroll: true });
        });
    });
}
