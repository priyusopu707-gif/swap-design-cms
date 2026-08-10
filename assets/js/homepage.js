/**
 * Swap Design - Homepage JavaScript
 *
 * Features:
 *  - Counter animation (IntersectionObserver + countUp)
 *  - FAQ accordion (expand/collapse, search filter)
 *  - Portfolio filter (category buttons)
 *  - Scroll-based fade-in animations
 *  - Lazy image loading
 */

(function () {
    'use strict';

    /* =================================================================
       Hero Entrance Animation & Mouse Parallax
       ================================================================= */
    function initHero() {
        var hero = document.querySelector('.hero');
        if (!hero) return;
        var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var visualWrap = hero.querySelector('.hero__visual-wrap');
        var panel = hero.querySelector('.hero__panel');

        // Hero content entrance stagger (if GSAP available, use it; else IO fallback is fine)
        if (window.SwapGSAP && window.SwapGSAP.gsap && !reducedMotion) {
            var gsap = window.SwapGSAP.gsap;
            var heroEls = hero.querySelectorAll('[data-reveal]');
            if (heroEls.length) {
                heroEls.forEach(function (el, i) {
                    var delay = parseInt(el.getAttribute('data-reveal-delay'), 10) || i * 120;
                    gsap.set(el, { opacity: 0, y: 28 });
                    gsap.to(el, {
                        opacity: 1,
                        y: 0,
                        duration: 0.9,
                        delay: delay / 1000,
                        ease: 'power3.out',
                        onComplete: function () { el.classList.add('is-visible'); }
                    });
                });
            }

            // Floating chips stagger animation
            var chips = hero.querySelectorAll('.hero__chip');
            if (chips.length) {
                chips.forEach(function (chip, i) {
                    gsap.set(chip, { opacity: 0, scale: 0.8 });
                    gsap.to(chip, {
                        opacity: 1,
                        scale: 1,
                        duration: 0.6,
                        delay: 0.8 + i * 0.15,
                        ease: 'back.out(1.4)'
                    });
                });
            }

            // Glass layer entrance
            var glassLayers = hero.querySelectorAll('.hero__glass-layer');
            if (glassLayers.length) {
                glassLayers.forEach(function (layer, i) {
                    gsap.set(layer, { opacity: 0, scale: 0.7 });
                    gsap.to(layer, {
                        opacity: 1,
                        scale: 1,
                        duration: 1.2,
                        delay: 0.3 + i * 0.2,
                        ease: 'power2.out'
                    });
                });
            }
        }

// Mouse parallax REMOVED: parallax.js handles all [data-parallax] elements globally
        // including .hero__visual-wrap. Duplicate transforms caused jitter.

        // 3D panel tilt REMOVED: tilt.js handles .hero__panel via [data-tilt="4"].
        // Duplicate transform writes caused flicker.
    }

    /* =================================================================
       Counter Animation (legacy delegate — design-system counter.js
       handles [data-counter] natively; kept as fallback for inline
       usages where counter.js hasn't fired yet.
       ================================================================= */
    // Counters are now driven by /assets/js/components/counter.js via
    // the [data-counter] attribute on .card__number spans.

    /* =================================================================
       FAQ Accordion
       ================================================================= */
    function initFaq() {
        var faqList = document.querySelector('.faq__list');
        if (!faqList) return;

        /* Expand/Collapse */
        faqList.addEventListener('click', function (e) {
            var trigger = e.target.closest('.js-faq-trigger');
            if (!trigger) return;

            e.preventDefault();
            var expanded = trigger.getAttribute('aria-expanded') === 'true';
            var answer = document.getElementById(trigger.getAttribute('aria-controls'));

            /* Close all others */
            var allTriggers = faqList.querySelectorAll('.js-faq-trigger[aria-expanded="true"]');
            allTriggers.forEach(function (t) {
                t.setAttribute('aria-expanded', 'false');
                var a = document.getElementById(t.getAttribute('aria-controls'));
                if (a) a.hidden = true;
            });

            /* Open clicked one */
            if (!expanded && answer) {
                trigger.setAttribute('aria-expanded', 'true');
                answer.hidden = false;
            }
        });

        /* Keyboard: Space/Enter on trigger */
        faqList.addEventListener('keydown', function (e) {
            var trigger = e.target.closest('.js-faq-trigger');
            if (!trigger) return;
            if (e.key === ' ' || e.key === 'Enter') {
                e.preventDefault();
                trigger.click();
            }
        });

        /* Search filter */
        var searchInput = document.querySelector('.js-faq-search');
        if (!searchInput) return;

        searchInput.addEventListener('input', function () {
            var query = searchInput.value.toLowerCase().trim();
            var items = faqList.querySelectorAll('.js-faq-item');

            items.forEach(function (item) {
                var question = (item.querySelector('.js-faq-trigger span') || {}).textContent || '';
                var answer   = (item.querySelector('.faq-item__answer p') || {}).textContent || '';
                var text     = (question + ' ' + answer).toLowerCase();

                if (!query || text.indexOf(query) !== -1) {
                    item.classList.remove('faq-item--hidden');
                } else {
                    item.classList.add('faq-item--hidden');
                }
            });
        });
    }

    /* =================================================================
       Portfolio Filter
       ================================================================= */
    function initPortfolioFilter() {
        var filtersWrap = document.querySelector('.portfolio__filters');
        if (!filtersWrap) return;

        var cards = document.querySelectorAll('.pf-card[data-category]');

        filtersWrap.addEventListener('click', function (e) {
            var btn = e.target.closest('.pf-filter-btn');
            if (!btn) return;

            var filter = btn.getAttribute('data-filter');

            /* Active state */
            filtersWrap.querySelectorAll('.pf-filter-btn').forEach(function (b) {
                b.classList.remove('pf-filter-btn--active');
                b.setAttribute('aria-pressed', 'false');
            });
            btn.classList.add('pf-filter-btn--active');
            btn.setAttribute('aria-pressed', 'true');

            /* Filter cards */
            cards.forEach(function (card) {
                if (filter === 'all' || card.getAttribute('data-category') === filter) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }

    /* =================================================================
       Fade-in Animation on Scroll
       ================================================================= */
    function initFadeIn() {
        var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (prefersReduced) return;

        var elements = document.querySelectorAll('.fade-in');
        if (!elements.length) return;

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.style.animationPlayState = 'running';
                }
            });
        }, { threshold: 0.1 });

        elements.forEach(function (el) {
            el.style.animationPlayState = 'paused';
            observer.observe(el);
        });
    }

/* =================================================================
       3D Card Tilt — DEPRECATED (kept as no-op for backward compat)
       tilt.js handles all [data-tilt] globally. This function is a stub.
       ================================================================= */
    function initCardTilt() {
        // No-op: /assets/js/components/tilt.js owns all [data-tilt] interactions.
    }

    /* =================================================================
       Stagger Grid Reveal on Scroll (legacy, kept for grid containers)
       ================================================================= */
    function initStaggerGrids() {
        var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (prefersReduced) return;

        var grids = document.querySelectorAll('.problems__grid, .industries__grid, .work-me__grid, .technologies__grid, .technologies__wall, .experience__stats');

        grids.forEach(function (grid) {
            var items = grid.querySelectorAll('[data-reveal]');
            if (!items.length) return;

            var gsap = window.SwapGSAP && window.SwapGSAP.gsap ? window.SwapGSAP.gsap : null;

            items.forEach(function (item, i) {
                if (gsap) {
                    var delay = parseInt(item.getAttribute('data-reveal-delay'), 10) || i * 60;
                    gsap.set(item, { opacity: 0, y: 30 });
                    gsap.to(item, {
                        opacity: 1,
                        y: 0,
                        duration: 0.7,
                        delay: delay / 1000,
                        ease: 'power2.out'
                    });
                } else {
                    var observer = new IntersectionObserver(function (entries) {
                        if (entries[0].isIntersecting) {
                            item.classList.add('is-visible');
                            observer.unobserve(item);
                        }
                    }, { threshold: 0.1 });
                    observer.observe(item);
                }
            });
        });
    }

    /* =================================================================
       GSAP ScrollTrigger Section Reveal (Premium Stagger)
       Uses GSAP if available, else falls back to IntersectionObserver.
       Targets each section's [data-reveal] children for staggered reveal.
       ================================================================= */
    function initSectionReveal() {
        var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (prefersReduced) return;

        /* The global reveal.js already registers a ScrollTrigger per
           [data-reveal] element. Skip our own per-section stagger when
           that system is active to avoid double-animating the same nodes. */
        if (window.SwapRevealState && window.SwapRevealState.loaded) return;

        var gsap = window.SwapGSAP && window.SwapGSAP.gsap ? window.SwapGSAP.gsap : null;
        var ScrollTrigger = window.SwapGSAP && window.SwapGSAP.ScrollTrigger ? window.SwapGSAP.ScrollTrigger : null;

        var sections = document.querySelectorAll('section');
        if (!sections.length) return;

        sections.forEach(function (section) {
            var items = section.querySelectorAll('[data-reveal]');
            if (!items.length) return;

            if (gsap && ScrollTrigger) {
                gsap.from(items, {
                    opacity: 0,
                    y: 24,
                    duration: 0.6,
                    stagger: 0.08,
                    ease: 'power2.out',
                    scrollTrigger: {
                        trigger: section,
                        start: 'top 80%',
                        toggleActions: 'play none none reverse'
                    }
                });
            } else {
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            var delay = parseInt(entry.target.getAttribute('data-reveal-delay'), 10) || 0;
                            setTimeout(function () {
                                entry.target.classList.add('is-visible');
                                entry.target.style.opacity = '1';
                                entry.target.style.transform = 'none';
                            }, delay);
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

                items.forEach(function (item) {
                    observer.observe(item);
                });
            }
        });
    }

    /* =================================================================
       Scroll Progress Bar
       ================================================================= */
    function initScrollProgress() {
        var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (prefersReduced) return;

        var progressBar = document.querySelector('.scroll-progress');
        if (!progressBar) return;

        window.addEventListener('scroll', function () {
            var scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
            var scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            var scrollPercent = (scrollTop / scrollHeight) * 100;
            progressBar.style.width = scrollPercent + '%';
        }, { passive: true });
    }

    /* =================================================================
       Lazy Image Loading Polyfill (for browsers without native support)
       ================================================================= */
    function initLazyImages() {
        if ('loading' in HTMLImageElement.prototype) return;

        var images = document.querySelectorAll('img[loading="lazy"]');
        if (!images.length) return;

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                var img = entry.target;
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                }
                observer.unobserve(img);
            });
        });

        images.forEach(function (img) {
            if (!img.dataset.src && img.src) {
                img.dataset.src = img.src;
                img.removeAttribute('src');
            }
            observer.observe(img);
        });
    }

    /* =================================================================
       Init all modules on DOMContentLoaded
       ================================================================= */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

    function initAll() {
        // Counter animation handled by /assets/js/components/counter.js ([data-counter])
        // Scroll reveal handled by /assets/js/components/reveal.js ([data-reveal])
        // 3D tilt handled by /assets/js/components/tilt.js ([data-tilt]) — global, no dup here.
        // Parallax handled by /assets/js/components/parallax.js ([data-parallax]) — global.
        initHero();
        initFaq();
        initPortfolioFilter();
        initFadeIn();   // legacy fallback for [.fade-in] elements
        initLazyImages();
        // initCardTilt() REMOVED: tilt.js handles all [data-tilt] globally.
        initStaggerGrids();
        initSectionReveal(); // Premium GSAP ScrollTrigger stagger
        initScrollProgress(); // Scroll progress bar
    }
})();
