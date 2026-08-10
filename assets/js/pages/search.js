/**
 * Swap Design - Search Results Page JS
 * Sort select, featured toggle, deferred result click logging.
 */
(function () {
    'use strict';

    var page = document.getElementById('search-page');
    if (!page) return;

    var csrf = (page.querySelector('input[name="search_csrf"]') || {}).value || '';

    /* ---- Sort select ---- */
    var sortSelect = page.querySelector('[data-sort-select]');
    if (sortSelect) {
        sortSelect.addEventListener('change', function () {
            var base = sortSelect.getAttribute('data-sort-url') || '/search';
            var sep = base.indexOf('?') === -1 ? '?' : '&';
            window.location.href = base + sep + 'sort=' + encodeURIComponent(sortSelect.value);
        });
    }

    /* ---- Featured toggle ---- */
    var featuredToggle = page.querySelector('[data-featured-toggle]');
    if (featuredToggle) {
        featuredToggle.addEventListener('change', function () {
            var link = featuredToggle.closest('a');
            if (link) window.location.href = link.getAttribute('href');
        });
    }

    /* ---- Click logging ---- */
    var searchResults = page.querySelectorAll('[data-search-result]');
    if (searchResults.length === 0 || !csrf) return;

    function getMeta(name) {
        var meta = document.querySelector('meta[name="' + name + '"]');
        return meta ? (meta.getAttribute('content') || '') : '';
    }

    function logClick(link) {
        var payload = [
            'action=' + encodeURIComponent('record_click'),
            'token=' + encodeURIComponent(csrf),
            'content_type=' + encodeURIComponent(link.getAttribute('data-content-type') || ''),
            'content_id=' + encodeURIComponent(link.getAttribute('data-content-id') || ''),
            'title=' + encodeURIComponent(link.querySelector('.search-result__title') ? link.querySelector('.search-result__title').textContent : ''),
            'url=' + encodeURIComponent(link.getAttribute('href') || ''),
            'search_log_id=' + encodeURIComponent(link.getAttribute('data-search-log-id') || ''),
            'query=' + encodeURIComponent(getMeta('search-query')),
            'position=' + encodeURIComponent(link.getAttribute('data-position') || '')
        ];

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/ajax/search.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('X-CSRF-Token', csrf);
        xhr.send(payload.join('&'));
    }

    var logged = {};
    searchResults.forEach(function (link) {
        link.addEventListener('click', function () {
            var contentId = link.getAttribute('data-content-id');
            if (logged[contentId]) return;
            logged[contentId] = true;
            logClick(link);
        });
    });
})();
