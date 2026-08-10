/**
 * Swap Design - Blog Frontend
 * Reading progress bar, TOC scroll spy, share tracking.
 */
(function () {
    'use strict';

    /* ================================================================
       Reading Progress Bar
       ================================================================ */
    var progressBar = document.querySelector('.blog-progress-bar');
    var blogPost = document.querySelector('.blog-post');

    if (progressBar && blogPost) {
        window.addEventListener('scroll', function () {
            var rect = blogPost.getBoundingClientRect();
            var postTop = rect.top + window.pageYOffset;
            var postHeight = rect.height;
            var scrollPos = window.pageYOffset - postTop + window.innerHeight / 2;
            var progress = Math.min(1, Math.max(0, scrollPos / postHeight));
            progressBar.style.width = (progress * 100) + '%';
        }, { passive: true });
    }

    /* ================================================================
       TOC Scroll Spy
       ================================================================ */
    var tocLinks = document.querySelectorAll('.blog-toc__link');
    if (tocLinks.length) {
        var headings = [];
        tocLinks.forEach(function (link) {
            var href = link.getAttribute('href');
            if (href && href.startsWith('#')) {
                var el = document.querySelector(href);
                if (el) headings.push({ link: link, el: el });
            }
        });

        if (headings.length) {
            window.addEventListener('scroll', function () {
                var scrollTop = window.pageYOffset + 120;
                var active = null;

                for (var i = 0; i < headings.length; i++) {
                    if (headings[i].el.offsetTop <= scrollTop) {
                        active = headings[i];
                    }
                }

                headings.forEach(function (h) {
                    h.link.classList.remove('blog-toc__link--active');
                });
                if (active) {
                    active.link.classList.add('blog-toc__link--active');
                }
            }, { passive: true });
        }
    }

    /* ================================================================
       Smooth scroll for heading links
       ================================================================ */
    document.addEventListener('click', function (e) {
        var anchor = e.target.closest('a[href^="#"]');
        if (!anchor) return;

        var target = document.querySelector(anchor.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
})();
