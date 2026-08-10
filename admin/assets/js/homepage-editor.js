/**
 * Swap Design - Homepage Editor JavaScript
 *
 * Features:
 *  - Drag-drop section reorder
 *  - Drag-drop repeater item reorder
 *  - Auto-save on field change (debounced)
 *  - Manual save / save as draft
 *  - Enable/disable toggle
 *  - Publish all
 *  - Device preview switching
 *  - Toast notifications
 *  - Expand/collapse sections
 */

(function () {
    'use strict';

    var editor     = document.getElementById('hp-editor');
    var toast      = document.getElementById('hp-toast');
    var toastMsg   = toast ? toast.querySelector('.hp-toast__message') : null;
    var toastTimer = null;

    if (!editor) return;

    /* =================================================================
       Toast Notifications
       ================================================================= */
    function showToast(message, type) {
        if (!toast || !toastMsg) return;
        type = type || 'info';
        toast.className = 'hp-toast hp-toast--' + type;
        toastMsg.textContent = message;
        toast.hidden = false;

        if (toastTimer) clearTimeout(toastTimer);
        toastTimer = setTimeout(function () {
            toast.hidden = true;
        }, 3000);
    }

    /* =================================================================
       AJAX Helper
       ================================================================= */
    function ajaxPost(data, callback) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', window.HP_AJAX_URL, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-CSRF-Token', window.HP_CSRF_TOKEN || '');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.onload = function () {
            var resp;
            try { resp = JSON.parse(xhr.responseText); } catch (e) { resp = { ok: false, message: 'Invalid response' }; }
            callback(resp);
        };

        xhr.onerror = function () {
            callback({ ok: false, message: 'Network error' });
        };

        var parts = [];
        for (var key in data) {
            if (!data.hasOwnProperty(key)) continue;
            parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
        }

        xhr.send(parts.join('&'));
    }

    /* =================================================================
       Section Expand / Collapse
       ================================================================= */
    editor.addEventListener('click', function (e) {
        var expandBtn = e.target.closest('.hp-section-card__expand');
        if (!expandBtn) return;

        var sectionId = expandBtn.getAttribute('data-section-id');
        var body      = document.getElementById('hp-body-' + sectionId);
        if (!body) return;

        var expanded = expandBtn.getAttribute('aria-expanded') === 'true';
        expandBtn.setAttribute('aria-expanded', !expanded);
        body.hidden = expanded;
    });

    /* =================================================================
       Enable / Disable Toggle
       ================================================================= */
    editor.addEventListener('change', function (e) {
        var toggle = e.target;
        if (!toggle.matches('.hp-section-card__toggle-input')) return;

        var sectionId  = toggle.getAttribute('data-section-id');
        var card       = toggle.closest('.hp-section-card');
        var isChecked  = toggle.checked;

        card.classList.toggle('hp-section-card--disabled', !isChecked);

        ajaxPost({ action: 'toggle', section_id: sectionId, enabled: isChecked ? '1' : '0' }, function (resp) {
            if (!resp.ok) {
                toggle.checked = !isChecked;
                card.classList.toggle('hp-section-card--disabled', !isChecked);
                showToast(resp.message || 'Toggle failed', 'error');
            }
        });
    });

    /* =================================================================
       Auto-Save (debounced on field change)
       ================================================================= */
    var autoSaveTimers = {};

    editor.addEventListener('change', function (e) {
        var field = e.target.closest('.hp-field');
        if (!field) return;

        var form = field.closest('.hp-section-form');
        if (!form) return;

        if (form.getAttribute('data-autosave') !== 'true') return;

        var sectionId  = form.getAttribute('data-section-id');
        var sectionKey = form.getAttribute('data-section-key');

        if (autoSaveTimers[sectionId]) clearTimeout(autoSaveTimers[sectionId]);

        autoSaveTimers[sectionId] = setTimeout(function () {
            saveForm(form, sectionId, sectionKey, false, true);
        }, 800);
    });

    /* =================================================================
       Manual Save (submit button)
       ================================================================= */
    editor.addEventListener('click', function (e) {
        if (e.target.closest('.hp-section-form [type="submit"]')) {
            e.preventDefault();
            var form = e.target.closest('.hp-section-form');
            if (!form) return;

            var sectionId  = form.getAttribute('data-section-id');
            var sectionKey = form.getAttribute('data-section-key');

            if (autoSaveTimers[sectionId]) clearTimeout(autoSaveTimers[sectionId]);
            saveForm(form, sectionId, sectionKey, true, false);
        }
    });

    /* =================================================================
       Save as Draft
       ================================================================= */
    editor.addEventListener('click', function (e) {
        var draftBtn = e.target.closest('.hp-save-draft');
        if (!draftBtn) return;

        var form = draftBtn.closest('.hp-section-form');
        if (!form) return;

        var sectionId  = form.getAttribute('data-section-id');
        var sectionKey = form.getAttribute('data-section-key');
        saveForm(form, sectionId, sectionKey, true, true);
    });

    function saveForm(form, sectionId, sectionKey, showResult, asDraft) {
        var data = new FormData(form);
        var config = {};

        for (var pair of data.entries()) {
            var name = pair[0];
            var value = pair[1];

            if (name.indexOf('[') > -1) {
                var matches = name.match(/^(\w+)\[(\d+)\]\[(\w+)\]$/);
                if (matches) {
                    var arrName = matches[1];
                    var index   = parseInt(matches[2], 10);
                    var fName   = matches[3];
                    if (!config[arrName]) config[arrName] = [];
                    if (!config[arrName][index]) config[arrName][index] = {};
                    config[arrName][index][fName] = value;
                }
            } else {
                config[name] = value;
            }
        }

        var statusEl = document.querySelector('.hp-save-status[data-status-for="' + sectionId + '"]');
        if (statusEl) {
            statusEl.textContent = 'Saving...';
            statusEl.className = 'hp-save-status hp-save-status--saving';
        }

        ajaxPost({
            action:        'save',
            section_id:    sectionId,
            section_key:   sectionKey,
            config:        JSON.stringify(config),
            as_draft:      asDraft ? '1' : '0'
        }, function (resp) {
            if (resp.ok) {
                if (statusEl) {
                    statusEl.textContent = 'Saved';
                    statusEl.className = 'hp-save-status hp-save-status--saved';
                    setTimeout(function () {
                        statusEl.textContent = '';
                        statusEl.className = 'hp-save-status';
                    }, 2000);
                }

                var statusBadge = document.querySelector('.hp-section-card__status[data-status-for="' + sectionId + '"]');
                if (statusBadge) {
                    var newStatus = asDraft ? 'draft' : 'published';
                    statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                    statusBadge.className = 'hp-section-card__status hp-status--' + newStatus;
                }

                updateStatusSummary();

                if (showResult) showToast(resp.message || 'Saved', 'success');
            } else {
                if (statusEl) {
                    statusEl.textContent = 'Error';
                    statusEl.className = 'hp-save-status hp-save-status--error';
                }
                showToast(resp.message || 'Save failed', 'error');
            }
        });
    }

    /* =================================================================
       Publish All
       ================================================================= */
    var publishAllBtn = document.getElementById('hp-publish-all');
    if (publishAllBtn) {
        publishAllBtn.addEventListener('click', function () {
            publishAllBtn.disabled = true;
            publishAllBtn.textContent = 'Publishing...';

            ajaxPost({ action: 'publish_all' }, function (resp) {
                publishAllBtn.disabled = false;
                publishAllBtn.textContent = 'Publish All';

                if (resp.ok) {
                    var badges = editor.querySelectorAll('.hp-section-card__status');
                    badges.forEach(function (b) {
                        b.textContent = 'Published';
                        b.className = 'hp-section-card__status hp-status--published';
                    });
                    updateStatusSummary();
                    showToast(resp.message || 'All sections published', 'success');
                } else {
                    showToast(resp.message || 'Publish failed', 'error');
                }
            });
        });
    }

    /* =================================================================
       Status Summary Update
       ================================================================= */
    function updateStatusSummary() {
        var allCards = editor.querySelectorAll('.hp-section-card');
        var total    = allCards.length;
        var enabled  = 0;
        var published = 0;

        allCards.forEach(function (card) {
            var toggle = card.querySelector('.hp-section-card__toggle-input');
            var statusBadge = card.querySelector('.hp-section-card__status');
            if (toggle && toggle.checked) enabled++;
            if (statusBadge && statusBadge.textContent.trim().toLowerCase() === 'published') published++;
        });

        var summary = document.querySelector('.hp-status-summary');
        if (summary) {
            var stats = summary.querySelectorAll('.hp-stat');
            if (stats[0]) stats[0].textContent = enabled + '/' + total + ' enabled';
            if (stats[1]) {
                stats[1].textContent = published + '/' + total + ' published';
                stats[1].className = 'hp-stat hp-stat--' + (published === total ? 'ok' : 'warn');
            }
        }
    }

    /* =================================================================
       Drag-Drop Section Reorder
       ================================================================= */
    var draggedSection = null;

    editor.addEventListener('dragstart', function (e) {
        var card = e.target.closest('.hp-section-card');
        if (!card) return;

        draggedSection = card;
        card.classList.add('hp-section-card--dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', '');
    });

    editor.addEventListener('dragend', function (e) {
        var card = e.target.closest('.hp-section-card');
        if (card) card.classList.remove('hp-section-card--dragging');

        editor.querySelectorAll('.hp-section-card--drag-over').forEach(function (c) {
            c.classList.remove('hp-section-card--drag-over');
        });

        draggedSection = null;
    });

    editor.addEventListener('dragover', function (e) {
        e.preventDefault();
        var card = e.target.closest('.hp-section-card');
        if (!card || card === draggedSection) return;

        card.classList.add('hp-section-card--drag-over');
        e.dataTransfer.dropEffect = 'move';
    });

    editor.addEventListener('dragleave', function (e) {
        var card = e.target.closest('.hp-section-card');
        if (card) card.classList.remove('hp-section-card--drag-over');
    });

    editor.addEventListener('drop', function (e) {
        e.preventDefault();
        var targetCard = e.target.closest('.hp-section-card');
        if (!targetCard || targetCard === draggedSection) return;

        targetCard.classList.remove('hp-section-card--drag-over');

        var cards = Array.from(editor.querySelectorAll('.hp-section-card'));
        var targetIndex = cards.indexOf(targetCard);
        var draggedIndex = cards.indexOf(draggedSection);

        if (targetIndex < draggedIndex) {
            editor.insertBefore(draggedSection, targetCard);
        } else {
            editor.insertBefore(draggedSection, targetCard.nextSibling);
        }

        saveOrder();
    });

    function saveOrder() {
        var cards = editor.querySelectorAll('.hp-section-card');
        var ids   = [];

        cards.forEach(function (card) {
            ids.push(card.getAttribute('data-section-id'));
        });

        ajaxPost({
            action: 'reorder',
            order:  ids.join(',')
        }, function (resp) {
            if (resp.ok) {
                showToast('Order saved', 'success');
            } else {
                showToast(resp.message || 'Reorder failed', 'error');
            }
        });
    }

    /* =================================================================
       Repeater: Add Item
       ================================================================= */
    editor.addEventListener('click', function (e) {
        var addBtn = e.target.closest('.hp-repeater-add');
        if (!addBtn) return;

        e.preventDefault();
        var repeater = addBtn.closest('.hp-repeater');
        if (!repeater) return;

        addRepeaterItem(repeater);
    });

    /* =================================================================
       Repeater: Remove Item
       ================================================================= */
    editor.addEventListener('click', function (e) {
        var removeBtn = e.target.closest('.hp-repeater__remove');
        if (!removeBtn) return;

        var item     = removeBtn.closest('.hp-repeater__item');
        var repeater = removeBtn.closest('.hp-repeater');

        if (!item || !repeater) return;

        var items = repeater.querySelectorAll('.hp-repeater__item');
        if (items.length <= 1) {
            showToast('Cannot remove the last item', 'error');
            return;
        }

        item.remove();
        reindexRepeater(repeater);
    });

    function addRepeaterItem(repeater) {
        var items    = repeater.querySelector('.hp-repeater__items');
        var existing = items.querySelector('.hp-repeater__item');
        if (!existing) return;

        var clone     = existing.cloneNode(true);
        var newIndex  = items.children.length;

        clone.setAttribute('data-index', newIndex);
        clone.querySelectorAll('input, textarea, select').forEach(function (field) {
            field.value = '';
            var name = field.getAttribute('name');
            if (name) {
                name = name.replace(/\[\d+\]/, '[' + newIndex + ']');
                field.setAttribute('name', name);
            }
        });

        var indexBadge = clone.querySelector('.hp-repeater__index');
        if (indexBadge) indexBadge.textContent = '#' + (newIndex + 1);

        items.appendChild(clone);
    }

    function reindexRepeater(repeater) {
        var items = repeater.querySelectorAll('.hp-repeater__item');
        var repeaterName = repeater.getAttribute('data-repeater');

        items.forEach(function (item, idx) {
            item.setAttribute('data-index', idx);
            var indexBadge = item.querySelector('.hp-repeater__index');
            if (indexBadge) indexBadge.textContent = '#' + (idx + 1);

            item.querySelectorAll('input, textarea, select').forEach(function (field) {
                var name = field.getAttribute('name');
                if (name) {
                    name = name.replace(new RegExp(repeaterName + '\\[\\d+\\]'), repeaterName + '[' + idx + ']');
                    field.setAttribute('name', name);
                }
            });
        });
    }

    /* =================================================================
       Device Preview
       ================================================================= */
    var previewBtns = document.querySelectorAll('.hp-preview-btn');
    var adminMain   = document.getElementById('admin-main');

    previewBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            previewBtns.forEach(function (b) { b.classList.remove('hp-preview-btn--active'); });
            btn.classList.add('hp-preview-btn--active');

            var mode = btn.getAttribute('data-preview');

            adminMain.classList.remove('hp-preview--tablet', 'hp-preview--mobile');

            if (mode === 'tablet') {
                adminMain.classList.add('hp-preview--tablet');
            } else if (mode === 'mobile') {
                adminMain.classList.add('hp-preview--mobile');
            }
        });
    });

    /* =================================================================
       Keyboard: Enter/Space on draggable sections, arrow keys reorder
       ================================================================= */
    editor.addEventListener('keydown', function (e) {
        var card = document.activeElement.closest('.hp-section-card');
        if (!card) return;

        if (e.key === 'ArrowUp' && e.altKey) {
            e.preventDefault();
            var prev = card.previousElementSibling;
            if (prev && prev.classList.contains('hp-section-card')) {
                editor.insertBefore(card, prev);
                saveOrder();
            }
        }

        if (e.key === 'ArrowDown' && e.altKey) {
            e.preventDefault();
            var next = card.nextElementSibling;
            if (next && next.classList.contains('hp-section-card')) {
                editor.insertBefore(next, card);
                saveOrder();
            }
        }
    });

})();
