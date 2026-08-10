/**
 * Swap Design - Global UI Kit
 * Vanilla JS (ES6+). Lightweight, performant.
 *
 * Features:
 *  - GSAP integration (progressive enhancement)
 *  - Custom dual cursor (desktop only)
 *  - 3D tilt cards
 *  - Scroll reveal animations (IntersectionObserver)
 *  - Magnetic hover buttons
 *  - Back to top button
 *  - Counter animation
 *
 * @package SwapDesign
 */
(function () {
    'use strict';

    /* =================================================================
       Scroll Reveal Animations (IntersectionObserver)
       ================================================================= */
    function initReveal() {
        var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (prefersReduced) return;

        var elements = document.querySelectorAll('[data-reveal], .fade-in-up, .slide-in-left, .slide-in-right, .scale-in');
        if (!elements.length) return;

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                entry.target.style.animationPlayState = 'running';
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.1 });

        elements.forEach(function (el) {
            el.style.animationPlayState = 'paused';
            observer.observe(el);
        });
    }

    /* =================================================================
       Custom Dual Cursor (Desktop Only)
       Auto-disabled on touch devices.
       ================================================================= */
    function initCursor() {
        if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;
        if (window.innerWidth < 768) return;

        var dot = document.querySelector('.custom-cursor');
        var ring = document.querySelector('.custom-cursor-ring');
        if (!dot || !ring) return;

        var mouseX = 0, mouseY = 0;
        var ringX = 0, ringY = 0;
        var hovered = false;

        /* Inner dot follows mouse directly */
        document.addEventListener('mousemove', function (e) {
            mouseX = e.clientX;
            mouseY = e.clientY;
            dot.style.left = mouseX + 'px';
            dot.style.top = mouseY + 'px';
            document.body.style.cursor = 'none';
        });

        /* Ring follows with easing (magnetic/spring feel) */
        function animateRing() {
            ringX += (mouseX - ringX) * 0.15;
            ringY += (mouseY - ringY) * 0.15;
            ring.style.left = ringX + 'px';
            ring.style.top = ringY + 'px';
            requestAnimationFrame(animateRing);
        }
        animateRing();

        /* Hover states on interactive elements */
        var interactive = 'a, button, .btn, input, select, textarea, .card, [data-cursor]';
        document.addEventListener('mouseover', function (e) {
            if (e.target.closest(interactive)) {
                dot.classList.add('hovering');
                ring.classList.add('hovering');
            }
        });
        document.addEventListener('mouseout', function (e) {
            if (e.target.closest(interactive)) {
                dot.classList.remove('hovering');
                ring.classList.remove('hovering');
            }
        });
    }

    /* =================================================================
       3D Tilt Cards (Mouse Tilt)
       Lightweight, smooth, professional.
       ================================================================= */
    function initTilt() {
        var cards = document.querySelectorAll('[data-tilt]');
        if (!cards.length) return;
        var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (prefersReduced) return;

        cards.forEach(function (card) {
            card.addEventListener('mousemove', function (e) {
                var rect = card.getBoundingClientRect();
                var x = (e.clientX - rect.left) / rect.width - 0.5;
                var y = (e.clientY - rect.top) / rect.height - 0.5;
                var maxTilt = 10; /* degrees */
                card.style.transform =
                    'perspective(1000px) rotateX(' + (-y * maxTilt) + 'deg) rotateY(' + (x * maxTilt) + 'deg)';
            });
            card.addEventListener('mouseleave', function () {
                card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0)';
            });
        });
    }

    /* =================================================================
       Magnetic Hover (Buttons)
       ================================================================= */
    function initMagnetic() {
        var elements = document.querySelectorAll('[data-magnetic]');
        if (!elements.length) return;

        elements.forEach(function (el) {
            el.addEventListener('mousemove', function (e) {
                var rect = el.getBoundingClientRect();
                var x = (e.clientX - rect.left - rect.width / 2) * 0.2;
                var y = (e.clientY - rect.top - rect.height / 2) * 0.2;
                el.style.transform = 'translate(' + x + 'px, ' + y + 'px)';
            });
            el.addEventListener('mouseleave', function () {
                el.style.transform = 'translate(0, 0)';
            });
        });
    }

    /* =================================================================
       Button Ripple
       ================================================================= */
    function initRipple() {
        var buttons = document.querySelectorAll('.btn[data-ripple], .btn-primary, .btn-cta');
        if (!buttons.length) return;

        buttons.forEach(function (btn) {
            btn.style.position = 'relative';
            btn.style.overflow = 'hidden';
            btn.addEventListener('click', function (e) {
                var rect = btn.getBoundingClientRect();
                var ripple = document.createElement('span');
                var size = Math.max(rect.width, rect.height);
                ripple.style.cssText =
                    'position:absolute;width:' + size + 'px;height:' + size + 'px;' +
                    'background:rgba(255,255,255,0.4);border-radius:50%;' +
                    'left:' + (e.clientX - rect.left - size / 2) + 'px;' +
                    'top:' + (e.clientY - rect.top - size / 2) + 'px;' +
                    'transform:scale(0);animation:ripple 0.6s ease-out;' +
                    'pointer-events:none;';
                btn.appendChild(ripple);
                setTimeout(function () { ripple.remove(); }, 600);
            });
        });
    }

    /* =================================================================
       Counter Animation
       ================================================================= */
    function initCounters() {
        var counters = document.querySelectorAll('[data-counter]');
        if (!counters.length) return;

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                var el = entry.target;
                var target = parseInt(el.getAttribute('data-counter'), 10) || 0;
                var duration = 2000;
                var start = null;

                function step(timestamp) {
                    if (!start) start = timestamp;
                    var progress = Math.min((timestamp - start) / duration, 1);
                    el.textContent = Math.floor(progress * target).toLocaleString();
                    if (progress < 1) {
                        requestAnimationFrame(step);
                    } else {
                        el.textContent = target.toLocaleString();
                    }
                }
                requestAnimationFrame(step);
                observer.unobserve(el);
            });
        }, { threshold: 0.5 });

        counters.forEach(function (el) { observer.observe(el); });
    }

    /* =================================================================
       Back To Top
       ================================================================= */
    function initBackToTop() {
        var btn = document.querySelector('.back-to-top');
        if (!btn) return;

        window.addEventListener('scroll', function () {
            if (window.scrollY > 400) {
                btn.classList.add('visible');
            } else {
                btn.classList.remove('visible');
            }
        }, { passive: true });

        btn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    /* =================================================================
       Init All
       ================================================================= */
    function init() {
        initReveal();
        initCursor();
        initTilt();
        initMagnetic();
        initRipple();
        initCounters();
        initBackToTop();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
