/**
 * Swap Design - Site Search Component
 *
 * Handles the header search trigger (desktop expandable panel + mobile
 * full-screen overlay), debounced live suggestions, keyboard navigation,
 * popular/recent searches, and click logging.
 */
(() => {
    const container  = document.getElementById('site-search');
    if (!container) return;

    const toggle   = document.querySelector('.main-header__search-toggle');
    const input    = document.getElementById('site-search-input');
    const clearBtn = container.querySelector('.site-search__clear');
    const closeBtn = container.querySelector('.site-search__close');
    const hintBox  = container.querySelector('.site-search__empty');
    const popularBox = container.querySelector('.site-search__popular');
    const popularList = container.querySelector('.site-search__popular-list');
    const resultsBox  = container.querySelector('.site-search__results');
    const resultsList = container.querySelector('.site-search__results-list');
    const footer      = container.querySelector('.site-search__footer');
    const allLink     = container.querySelector('.site-search__all');

    if (!input || !toggle) return;

    const API = '/ajax/search.php';
    const csrf = container.dataset.csrf || '';
    let isOpen = false;
    let debounceTimer = null;
    let highlightedIndex = -1;
    let currentItems = [];
    let lastQuery = '';

    /* Recent searches (localStorage, max 6) */
    const RECENT_KEY = 'swap-design-recent-searches';

    function getRecent() {
        try {
            const raw = localStorage.getItem(RECENT_KEY);
            const arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr.slice(0, 6) : [];
        } catch (e) {
            return [];
        }
    }

    function addRecent(query) {
        if (!query) return;
        try {
            const arr = getRecent().filter((q) => q.toLowerCase() !== query.toLowerCase());
            arr.unshift(query);
            localStorage.setItem(RECENT_KEY, JSON.stringify(arr.slice(0, 6)));
        } catch (e) { /* ignore */ }
    }

    /* ---- Open / close ---- */
    function open() {
        if (isOpen) return;
        isOpen = true;
        container.classList.add('is-open');
        container.removeAttribute('hidden');
        toggle.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            input.focus();
        }, 60);
        renderEmptyState();
    }

    function close() {
        if (!isOpen) return;
        isOpen = false;
        container.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
        clearDebounce();
        input.value = '';
        lastQuery = '';
        setTimeout(() => {
            container.setAttribute('hidden', '');
        }, 250);
    }

    /* ---- State rendering ---- */
    function resetDropdown() {
        highlightedIndex = -1;
        currentItems = [];
        resultsBox.hidden = true;
        popularBox.hidden = true;
        footer.hidden = true;
        resultsList.innerHTML = '';
        popularList.innerHTML = '';
    }

    function renderEmptyState() {
        resetDropdown();
        hintBox.hidden = false;

        const recent = getRecent();
        const hasRecent = recent.length > 0;

        if (hasRecent) {
            const title = document.createElement('p');
            title.className = 'site-search__section-title';
            title.textContent = 'Recent Searches';
            popularList.appendChild(title);
            recent.forEach((query) => {
                const li = document.createElement('li');
                li.className = 'site-search__popular-item';
                const a = document.createElement('a');
                a.className = 'site-search__popular-link';
                a.href = '/search?q=' + encodeURIComponent(query);
                a.textContent = query;
                li.appendChild(a);
                popularList.appendChild(li);
            });
            popularBox.hidden = false;
        } else {
            loadPopular();
        }
    }

    function loadPopular() {
        fetch(API + '?action=popular&limit=6')
            .then((res) => res.json())
            .then((data) => {
                if (!data.ok || !data.items || data.items.length === 0) return;
                popularList.innerHTML = '';
                data.items.forEach((item) => {
                    if (!item.query) return;
                    const li = document.createElement('li');
                    li.className = 'site-search__popular-item';
                    const a = document.createElement('a');
                    a.className = 'site-search__popular-link';
                    a.href = '/search?q=' + encodeURIComponent(item.query);
                    const span = document.createElement('span');
                    span.textContent = item.query;
                    const count = document.createElement('span');
                    count.className = 'site-search__popular-count';
                    count.textContent = item.count + (item.count === 1 ? ' search' : ' searches');
                    a.appendChild(span);
                    a.appendChild(count);
                    li.appendChild(a);
                    popularList.appendChild(li);
                });
                popularBox.hidden = false;
            })
            .catch(() => { /* ignore network errors */ });
    }

    function renderResults(items, query) {
        hintBox.hidden = true;
        resultsList.innerHTML = '';
        currentItems = items;
        highlightedIndex = -1;
        resultsBox.hidden = false;
        footer.hidden = false;

        allLink.href = '/search?q=' + encodeURIComponent(query);

        if (!items || items.length === 0) {
            const li = document.createElement('li');
            const p = document.createElement('p');
            p.className = 'site-search__no-results';
            p.textContent = 'No matching results. Press Enter to search all content.';
            li.appendChild(p);
            resultsList.appendChild(li);
            return;
        }

        items.forEach((item, idx) => {
            const li = document.createElement('li');
            const a = document.createElement('a');
            a.className = 'site-search__result-link';
            a.href = item.url || '#';
            a.dataset.index = idx;

            if (item.image) {
                const img = document.createElement('img');
                img.className = 'site-search__result-img';
                img.src = item.image;
                img.alt = '';
                img.loading = 'lazy';
                a.appendChild(img);
            }

            const body = document.createElement('span');
            body.className = 'site-search__result-body';

            const title = document.createElement('span');
            title.className = 'site-search__result-title';
            title.textContent = item.title;

            const metaBits = [];
            if (item.category) metaBits.push(item.category);
            const meta = document.createElement('span');
            meta.className = 'site-search__result-meta';
            meta.textContent = metaBits.join(' · ');

            body.appendChild(title);
            body.appendChild(meta);

            const type = document.createElement('span');
            type.className = 'site-search__result-type';
            type.textContent = item.type_label || item.content_type || '';

            a.appendChild(body);
            a.appendChild(type);

            a.addEventListener('click', (e) => {
                recordClick(item, idx);
                addRecent(query);
            });

            li.appendChild(a);
            resultsList.appendChild(li);
        });
    }

    /* ---- Live suggestions ---- */
    function fetchSuggestions(query) {
        fetch(API + '?action=suggest&q=' + encodeURIComponent(query) + '&limit=8')
            .then((res) => res.json())
            .then((data) => {
                if (lastQuery !== query) return;
                renderResults(data.ok ? data.items : [], query);
            })
            .catch(() => { /* ignore */ });
    }

    function clearDebounce() {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
            debounceTimer = null;
        }
    }

    function onInput() {
        const value = input.value.trim();

        if (value === '') {
            clearDebounce();
            lastQuery = '';
            renderEmptyState();
            return;
        }

        if (value.length < 2) {
            clearDebounce();
            lastQuery = '';
            resetDropdown();
            hintBox.hidden = false;
            return;
        }

        lastQuery = value;
        clearDebounce();
        debounceTimer = setTimeout(() => fetchSuggestions(value), 300);
    }

    /* ---- Click logging ---- */
    function recordClick(item, position) {
        if (!csrf) return;

        const body = new URLSearchParams({
            action: 'record_click',
            token: csrf,
            content_type: item.content_type || '',
            content_id: item.content_id || 0,
            title: item.title || '',
            url: item.url || '',
            position: position + 1,
        });

        fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString(),
        }).catch(() => { /* ignore */ });
    }

    /* ---- Keyboard navigation ---- */
    function onKeyDown(e) {
        if (!isOpen) return;

        if (e.key === 'Escape') {
            close();
            toggle.focus();
            e.preventDefault();
            return;
        }

        if (e.key === 'Enter') {
            if (highlightedIndex >= 0 && currentItems[highlightedIndex]) {
                e.preventDefault();
                const item = currentItems[highlightedIndex];
                recordClick(item, highlightedIndex);
                addRecent(input.value.trim());
                window.location.href = item.url;
                return;
            }
            const value = input.value.trim();
            if (value) {
                addRecent(value);
                window.location.href = '/search?q=' + encodeURIComponent(value);
            }
            return;
        }

        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            if (currentItems.length === 0) return;
            e.preventDefault();
            highlightSibling(e.key === 'ArrowDown' ? 1 : -1);
        }
    }

    function highlightSibling(dir) {
        const items = [...resultsList.querySelectorAll('.site-search__result-link')];
        if (items.length === 0) return;

        items.forEach((el) => el.classList.remove('is-highlighted'));

        highlightedIndex += dir;
        if (highlightedIndex < 0) highlightedIndex = items.length - 1;
        if (highlightedIndex >= items.length) highlightedIndex = 0;

        items[highlightedIndex].classList.add('is-highlighted');
        items[highlightedIndex].scrollIntoView({ block: 'nearest' });
    }

    /* ---- Event listeners ---- */
    toggle.addEventListener('click', () => (isOpen ? close() : open()));
    closeBtn.addEventListener('click', () => {
        close();
        toggle.focus();
    });
    clearBtn.addEventListener('click', () => {
        input.value = '';
        input.focus();
        renderEmptyState();
    });

    input.addEventListener('input', onInput);
    input.addEventListener('keydown', onKeyDown);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isOpen) {
            close();
            toggle.focus();
        }
    });
})();
