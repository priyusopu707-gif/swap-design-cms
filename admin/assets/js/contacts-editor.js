(function () {
    'use strict';

    var editor  = document.getElementById('cf-editor');
    var toast   = document.getElementById('cf-toast');
    var toastMsg = document.getElementById('cf-toast-message');
    if (!editor) return;

    var toastTimer;

    function showToast(message, type) {
        toast.className = 'cf-toast cf-toast--' + (type || 'info');
        toastMsg.textContent = message;
        toast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toast.hidden = true; }, 3000);
    }

    function ajaxPost(data, callback) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', window.CF_AJAX_URL, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-CSRF-Token', window.CF_CSRF_TOKEN || '');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function () {
            try { callback(JSON.parse(xhr.responseText)); }
            catch (e) { callback({ ok: false, message: 'Parse error' }); }
        };
        xhr.onerror = function () { callback({ ok: false, message: 'Network error' }); };
        var parts = [];
        for (var k in data) {
            if (!data.hasOwnProperty(k)) continue;
            parts.push(encodeURIComponent(k) + '=' + encodeURIComponent(data[k]));
        }
        xhr.send(parts.join('&'));
    }

    /* Save form */
    function saveForm(form, sectionId, sectionKey, publish, showResult) {
        var fd = new FormData(form);
        var payload = {
            action: 'save',
            section_id: sectionId,
            section_key: sectionKey,
        };

        /* Budget/timeline are textarea with newlines */
        var textFields = {};

        for (var pair of fd.entries()) {
            var name = pair[0];
            var val  = pair[1];

            var match = name.match(/^(\w+)\[(\d+)\]\[(\w+)\]$/);
            if (match) {
                if (!payload[match[1]]) payload[match[1]] = {};
                if (!payload[match[1]][match[2]]) payload[match[1]][match[2]] = {};
                payload[match[1]][match[2]][match[3]] = val;
                continue;
            }

            if (name === 'budget_options' || name === 'timeline_options') {
                textFields[name] = val;
                continue;
            }

            payload[name] = val;
        }

        /* Add textarea-line items */
        for (var tf in textFields) {
            if (textFields.hasOwnProperty(tf)) {
                payload[tf] = textFields[tf];
            }
        }

        if (publish) payload.publish = '1';
        if (!publish) payload.draft = '1';

        var savedEl = form.querySelector('.cf-section-form__saved');

        ajaxPost(payload, function (res) {
            if (showResult) {
                showToast(res.message || '', res.ok ? 'success' : 'error');
            }
            if (res.ok && savedEl) {
                savedEl.hidden = false;
                setTimeout(function () { savedEl.hidden = true; }, 2000);
            }

            /* Update status badge */
            var card = form.closest('.cf-section-card');
            if (card) {
                var badge = card.querySelector('.cf-section-card__status');
                if (badge) {
                    badge.textContent = publish ? 'published' : 'draft';
                    badge.className = 'cf-section-card__status cf-section-card__status--' + (publish ? 'published' : 'draft');
                }
            }

            /* Update status counts */
            var cardCount = editor.querySelectorAll('.cf-section-card__status--published').length;
            var pubCount = document.getElementById('cf-published-count');
            if (pubCount) pubCount.textContent = cardCount + ' published';
        });
    }

    /* Auto-save debounce */
    var autoSaveTimers = {};

    editor.addEventListener('change', function (e) {
        var field = e.target.closest('input, textarea, select');
        if (!field) return;
        var form = field.closest('.cf-section-form');
        if (!form || form.getAttribute('data-autosave') !== 'true') return;
        var sectionId = form.getAttribute('data-section-id');
        if (!sectionId) return;
        var sectionKey = form.closest('.cf-section-card').getAttribute('data-section-key');

        clearTimeout(autoSaveTimers[sectionId]);
        autoSaveTimers[sectionId] = setTimeout(function () {
            saveForm(form, sectionId, sectionKey, false, false);
        }, 800);
    });

    /* Expand/collapse */
    editor.addEventListener('click', function (e) {
        var expandBtn = e.target.closest('.cf-section-card__expand');
        if (expandBtn) {
            var card = expandBtn.closest('.cf-section-card');
            var body = card.querySelector('.cf-section-card__body');
            var expanded = expandBtn.getAttribute('aria-expanded') === 'true';
            expandBtn.setAttribute('aria-expanded', !expanded);
            body.hidden = !expanded;
            return;
        }

        /* Toggle switch */
        var toggle = e.target.closest('.cf-section-card__toggle-input');
        if (toggle) {
            var card = toggle.closest('.cf-section-card');
            var sectionId = card.getAttribute('data-section-id');
            ajaxPost({ action: 'toggle', section_id: sectionId, enabled: toggle.checked ? '1' : '0' }, function (res) {
                if (res.ok) {
                    showToast(res.message, 'success');
                    var enabledCount = editor.querySelectorAll('.cf-section-card__toggle-input:checked').length;
                    var el = document.getElementById('cf-enabled-count');
                    if (el) el.textContent = enabledCount + ' enabled';
                }
            });
            return;
        }

        /* Save button */
        var saveBtn = e.target.closest('.cf-save-btn');
        if (saveBtn) {
            var form = saveBtn.closest('.cf-section-form');
            var card = form.closest('.cf-section-card');
            saveForm(form, card.getAttribute('data-section-id'), card.getAttribute('data-section-key'), true, true);
            return;
        }

        /* Draft button */
        var draftBtn = e.target.closest('.cf-draft-btn');
        if (draftBtn) {
            var f = draftBtn.closest('.cf-section-form');
            var c = f.closest('.cf-section-card');
            saveForm(f, c.getAttribute('data-section-id'), c.getAttribute('data-section-key'), false, true);
            return;
        }
    });

    /* Publish all */
    var publishAllBtn = document.getElementById('cf-publish-all');
    if (publishAllBtn) {
        publishAllBtn.addEventListener('click', function () {
            ajaxPost({ action: 'publish_all' }, function (res) {
                showToast(res.message, res.ok ? 'success' : 'error');
                if (res.ok) {
                    var badges = editor.querySelectorAll('.cf-section-card__status');
                    badges.forEach(function (b) {
                        b.textContent = 'published';
                        b.className = 'cf-section-card__status cf-section-card__status--published';
                    });
                    var pc = document.getElementById('cf-published-count');
                    if (pc) pc.textContent = badges.length + ' published';
                }
            });
        });
    }

    /* Repeater add */
    editor.addEventListener('click', function (e) {
        var addBtn = e.target.closest('.cf-repeater__add');
        if (!addBtn) return;

        var name = addBtn.getAttribute('data-repeater');
        var fields = JSON.parse(addBtn.getAttribute('data-fields'));
        var list = document.getElementById('repeater-' + name);
        if (!list) return;

        var idx = list.querySelectorAll('.cf-repeater__item--' + name).length;
        var item = document.createElement('div');
        item.className = 'cf-repeater__item cf-repeater__item--' + name;
        item.setAttribute('draggable', 'true');

        var headerTitle = '';
        var bodyHtml = '';

        fields.forEach(function (f) {
            var fieldName = name + '[' + idx + '][' + f.name + ']';
            var labelHtml = f.label ? '<label class="cf-field__label">' + f.label + '</label>' : '';
            if (f.type === 'textarea') {
                bodyHtml += '<div class="cf-field cf-field--textarea">' + labelHtml + '<textarea name="' + fieldName + '" class="cf-field__textarea" rows="' + (f.attrs ? f.attrs.rows || 3 : 3) + '"></textarea></div>';
            } else {
                bodyHtml += '<div class="cf-field">' + labelHtml + '<input type="text" name="' + fieldName + '" class="cf-field__input"></div>';
            }
            if (!headerTitle && f.name === 'question') headerTitle = 'New Item';
        });

        item.innerHTML = '<div class="cf-repeater__header"><span class="cf-repeater__handle">&#9776;</span><span class="cf-repeater__title">' + (headerTitle || 'New Item') + '</span><button type="button" class="cf-repeater__remove">&times;</button></div><div class="cf-repeater__body">' + bodyHtml + '</div>';
        list.appendChild(item);
    });

    /* Repeater remove */
    editor.addEventListener('click', function (e) {
        var remBtn = e.target.closest('.cf-repeater__remove');
        if (!remBtn) return;
        var item = remBtn.closest('.cf-repeater__item');
        if (item) {
            item.parentNode.removeChild(item);
        }
    });

    /* Drag-drop reorder */
    var dragged = null;

    editor.addEventListener('dragstart', function (e) {
        dragged = e.target.closest('.cf-section-card');
        if (!dragged) return;
        dragged.style.opacity = '0.5';
        e.dataTransfer.effectAllowed = 'move';
    });

    editor.addEventListener('dragend', function (e) {
        if (dragged) dragged.style.opacity = '1';
        dragged = null;
    });

    editor.addEventListener('dragover', function (e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    });

    editor.addEventListener('drop', function (e) {
        e.preventDefault();
        if (!dragged) return;
        var target = e.target.closest('.cf-section-card');
        if (!target || target === dragged) return;

        var rect = target.getBoundingClientRect();
        var next = (e.clientY - rect.top) > (rect.height / 2);

        if (next) {
            target.parentNode.insertBefore(dragged, target.nextSibling);
        } else {
            target.parentNode.insertBefore(dragged, target);
        }

        /* Save new order */
        var cards = editor.querySelectorAll('.cf-section-card');
        var ids = [];
        cards.forEach(function (c) { ids.push(c.getAttribute('data-section-id')); });
        ajaxPost({ action: 'reorder', ids: ids.join(',') }, function (res) {
            if (res.ok) showToast(res.message, 'success');
        });
    });
})();
