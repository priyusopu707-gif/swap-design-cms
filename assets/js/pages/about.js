/**
 * Swap Design - About Page
 * FAQ accordion, counter animation, IntersectionObserver animations.
 */
(function () {
    'use strict';

    /* ====================================================================
       FAQ Accordion
       ==================================================================== */
    const faqQuestions = document.querySelectorAll('.about-faq__question');
    faqQuestions.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const expanded = btn.getAttribute('aria-expanded') === 'true';
            const answerId = btn.getAttribute('aria-controls');
            const answer = document.getElementById(answerId);

            /* Close any open FAQ */
            faqQuestions.forEach(function (q) {
                q.setAttribute('aria-expanded', 'false');
                var aId = q.getAttribute('aria-controls');
                var aEl = document.getElementById(aId);
                if (aEl) aEl.hidden = true;
            });

            /* Toggle clicked */
            if (!expanded) {
                btn.setAttribute('aria-expanded', 'true');
                if (answer) answer.hidden = false;
            }
        });
    });

    /* ====================================================================
       Counter Animation (IntersectionObserver)
       ==================================================================== */
    const counters = document.querySelectorAll('.about-experience__number[data-count]');
    if (counters.length) {
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    const target = parseInt(el.dataset.count, 10);
                    const duration = 1500;
                    const start = performance.now();

                    function updateCounter(now) {
                        const elapsed = now - start;
                        const progress = Math.min(elapsed / duration, 1);
                        const eased = 1 - Math.pow(1 - progress, 3);
                        const current = Math.floor(eased * target);
                        el.textContent = current;

                        if (progress < 1) {
                            requestAnimationFrame(updateCounter);
                        } else {
                            el.textContent = target;
                        }
                    }

                    requestAnimationFrame(updateCounter);
                    observer.unobserve(el);
                }
            });
        }, { threshold: 0.5 });

        counters.forEach(function (el) { observer.observe(el); });
    }

    /* ====================================================================
       Timeline fade-in animation
       ==================================================================== */
    const timelineItems = document.querySelectorAll('.about-timeline__item');
    if (timelineItems.length) {
        const tlObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('about-timeline__item--visible');
                    tlObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.2 });

        timelineItems.forEach(function (el) { tlObserver.observe(el); });
    }

    /* ====================================================================
       Skill bar animation
       ==================================================================== */
    const skillBars = document.querySelectorAll('.about-skills__bar-fill');
    if (skillBars.length) {
        const skillObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    const pct = entry.target.style.width;
                    entry.target.style.width = '0';
                    requestAnimationFrame(function () {
                        entry.target.style.width = pct;
                    });
                    skillObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });

        skillBars.forEach(function (el) { skillObserver.observe(el); });
    }

    /* ====================================================================
       WhatsApp button handler
       ==================================================================== */
    document.addEventListener('click', function (e) {
        var waBtn = e.target.closest('.js-whatsapp-open');
        if (!waBtn) return;

        var waManager = document.querySelector('a[href*="wa.me"]');
        if (waManager) {
            waManager.click();
        }
    });
})();
