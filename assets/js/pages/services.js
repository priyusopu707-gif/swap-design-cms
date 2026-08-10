/**
 * Swap Design - Service Page JavaScript
 * FAQ accordion + scroll animations.
 */
(function () {
    'use strict';

    /* ---- FAQ Accordion ---- */
    function initFaq() {
        var faqList = document.querySelector('.faq__list');
        if (!faqList) return;

        faqList.addEventListener('click', function (e) {
            var trigger = e.target.closest('.js-faq-trigger');
            if (!trigger) return;
            e.preventDefault();

            var expanded = trigger.getAttribute('aria-expanded') === 'true';
            var answer = document.getElementById(trigger.getAttribute('aria-controls'));

            faqList.querySelectorAll('.js-faq-trigger[aria-expanded="true"]').forEach(function (t) {
                t.setAttribute('aria-expanded', 'false');
                var a = document.getElementById(t.getAttribute('aria-controls'));
                if (a) a.hidden = true;
            });

            if (!expanded && answer) {
                trigger.setAttribute('aria-expanded', 'true');
                answer.hidden = false;
            }
        });

        faqList.addEventListener('keydown', function (e) {
            var trigger = e.target.closest('.js-faq-trigger');
            if (!trigger) return;
            if (e.key === ' ' || e.key === 'Enter') {
                e.preventDefault();
                trigger.click();
            }
        });
    }

    /* ---- Fade-in on Scroll ---- */
    function initFadeIn() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
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

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initFaq(); initFadeIn(); });
    } else {
        initFaq();
        initFadeIn();
    }
})();
