/**
 * Swap Design - About Page Editor
 * Drag-drop reorder, inline editing, auto-save, revision history.
 */
(function () {
    'use strict';

    const sectionList = document.getElementById('about-section-list');
    if (!sectionList) return;

    const ajaxUrl = sectionList.dataset.ajax;
    const csrfToken = document.querySelector('input[name="csrf_token"]');
    const csrfVal = csrfToken ? csrfToken.value : '';

    let saveTimers = {};

    /* ====================================================================
       Auto-save debounced (800ms)
       ==================================================================== */
    function debouncedSave(sectionId, sectionKey, asDraft, callback) {
        if (saveTimers[sectionId]) {
            clearTimeout(saveTimers[sectionId]);
        }
        saveTimers[sectionId] = setTimeout(function () {
            doSave(sectionId, sectionKey, asDraft, callback);
        }, 800);
    }

    function doSave(sectionId, sectionKey, asDraft, callback) {
        const form = document.querySelector('.about-section-form[data-section-id="' + sectionId + '"]');
        if (!form) return;

        const config = {};
        const fields = form.querySelectorAll('.about-field');
        fields.forEach(function (f) {
            const name = f.getAttribute('name');
            if (!name) return;
            if (f.type === 'checkbox') {
                config[name] = f.checked ? '1' : '0';
            } else {
                config[name] = f.value;
            }
        });

        const repeaterContainers = form.querySelectorAll('.about-repeater');
        repeaterContainers.forEach(function (container) {
            const rname = container.dataset.name;
            const rows = container.querySelectorAll('.about-repeater__row');
            config[rname] = [];
            rows.forEach(function (row) {
                const item = {};
                const rowFields = row.querySelectorAll('.about-field');
                rowFields.forEach(function (rf) {
                    const fname = rf.getAttribute('name');
                    if (fname) {
                        const key = fname.split('[').pop().replace(']', '');
                        item[key] = rf.value;
                    }
                });
                config[rname].push(item);
            });
        });

        const statusEl = form.querySelector('.about-save-status');
        if (statusEl) statusEl.textContent = 'Saving...';

        const xhr = new XMLHttpRequest();
        xhr.open('POST', ajaxUrl);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            let resp;
            try { resp = JSON.parse(xhr.responseText); } catch (e) { resp = { ok: false }; }

            if (statusEl) {
                statusEl.textContent = resp.ok ? (asDraft ? 'Draft saved' : 'Published') : 'Error saving';
                statusEl.className = 'about-save-status about-save-status--' + (resp.ok ? 'success' : 'error');
                setTimeout(function () { if (statusEl) statusEl.textContent = ''; }, 2000);
            }

            if (resp.ok) {
                const card = form.closest('.about-section-card');
                if (card) {
                    const badge = card.querySelector('.about-section-card__status-badge');
                    if (badge) {
                        badge.textContent = asDraft ? 'draft' : 'published';
                        badge.className = 'about-section-card__status-badge about-section-card__status-badge--' + (asDraft ? 'draft' : 'published');
                    }
                    card.dataset.status = asDraft ? 'draft' : 'published';
                    if (asDraft) {
                        card.classList.add('about-section-card--draft');
                    } else {
                        card.classList.remove('about-section-card--draft');
                    }
                }
            }

            if (callback) callback(resp);
        };
        xhr.onerror = function () {
            if (statusEl) {
                statusEl.textContent = 'Network error';
                statusEl.className = 'about-save-status about-save-status--error';
            }
        };

        const body = 'action=save'
            + '&section_id=' + encodeURIComponent(sectionId)
            + '&section_key=' + encodeURIComponent(sectionKey)
            + '&as_draft=' + (asDraft ? '1' : '0')
            + '&config=' + encodeURIComponent(JSON.stringify(config));

        xhr.send(body);
    }

    /* ====================================================================
       Section expand/collapse
       ==================================================================== */
    sectionList.addEventListener('click', function (e) {
        const expandBtn = e.target.closest('.about-section-card__expand');
        if (!expandBtn) return;

        const card = expandBtn.closest('.about-section-card');
        const body = card.querySelector('.about-section-card__body');
        const isHidden = body.hidden;
        body.hidden = !isHidden;
        expandBtn.innerHTML = isHidden ? '&#9650;' : '&#9660;';
    });

    /* ====================================================================
       Save buttons
       ==================================================================== */
    sectionList.addEventListener('click', function (e) {
        const saveBtn = e.target.closest('.about-save-btn');
        if (!saveBtn) return;

        const form = saveBtn.closest('.about-section-form');
        const sectionId = parseInt(form.dataset.sectionId);
        const sectionKey = form.dataset.sectionKey;
        const action = saveBtn.dataset.action || 'save';
        const asDraft = action === 'save_draft';

        debouncedSave(sectionId, sectionKey, asDraft, clearTimeout(saveTimers[sectionId]) && doSave(sectionId, sectionKey, asDraft));
        clearTimeout(saveTimers[sectionId]);
        doSave(sectionId, sectionKey, asDraft);
    });

    /* ====================================================================
       Enable/disable toggle
       ==================================================================== */
    sectionList.addEventListener('change', function (e) {
        const toggle = e.target.closest('.about-section-enable');
        if (!toggle) return;

        const card = toggle.closest('.about-section-card');
        const sectionId = parseInt(card.dataset.sectionId);
        const enabled = toggle.checked;

        card.classList.toggle('about-section-card--disabled', !enabled);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', ajaxUrl);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send('action=toggle&section_id=' + sectionId + '&enabled=' + (enabled ? '1' : '0'));
    });

    /* ====================================================================
       Publish All
       ==================================================================== */
    document.getElementById('about-publish-all').addEventListener('click', function () {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', ajaxUrl);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            document.querySelectorAll('.about-section-card__status-badge').forEach(function (b) {
                b.textContent = 'published';
                b.className = 'about-section-card__status-badge about-section-card__status-badge--published';
            });
            document.querySelectorAll('.about-section-card').forEach(function (c) {
                c.classList.remove('about-section-card--draft');
                c.dataset.status = 'published';
            });
        };
        xhr.send('action=publish_all');
    });

    /* ====================================================================
       Drag and Drop Reorder
       ==================================================================== */
    const cards = sectionList.querySelectorAll('.about-section-card');
    let draggedEl = null;

    cards.forEach(function (card) {
        card.setAttribute('draggable', 'true');
        card.addEventListener('dragstart', function (e) {
            draggedEl = card;
            card.classList.add('about-section-card--dragging');
            e.dataTransfer.effectAllowed = 'move';
        });
        card.addEventListener('dragend', function () {
            card.classList.remove('about-section-card--dragging');
            draggedEl = null;

            const allCards = sectionList.querySelectorAll('.about-section-card');
            const ids = [];
            allCards.forEach(function (c) { ids.push(c.dataset.sectionId); });

            const xhr = new XMLHttpRequest();
            xhr.open('POST', ajaxUrl);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send('action=reorder&order=' + ids.join(','));
        });
        card.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        });
        card.addEventListener('drop', function (e) {
            e.preventDefault();
            if (draggedEl && draggedEl !== card) {
                const rect = card.getBoundingClientRect();
                const midY = rect.top + rect.height / 2;
                if (e.clientY < midY) {
                    sectionList.insertBefore(draggedEl, card);
                } else {
                    sectionList.insertBefore(draggedEl, card.nextSibling);
                }
            }
        });
    });

    /* ====================================================================
       Repeater add/remove
       ==================================================================== */
    sectionList.addEventListener('click', function (e) {
        const addBtn = e.target.closest('.about-repeater-add');
        if (!addBtn) return;

        const fieldEl = addBtn.previousElementSibling;
        if (!fieldEl || !fieldEl.classList.contains('about-repeater')) return;

        const rname = fieldEl.dataset.name;
        const fieldsDef = JSON.parse(fieldEl.dataset.fields || '[]');
        const rows = fieldEl.querySelectorAll('.about-repeater__row');
        const index = rows.length;

        const row = document.createElement('div');
        row.className = 'about-repeater__row';
        row.dataset.index = index;

        let rowHtml = '<div class="about-repeater__row-header">'
            + '<span class="about-repeater__row-handle" title="Drag to reorder">::</span>'
            + '<span class="about-repeater__row-label">New Item</span>'
            + '<button type="button" class="about-repeater__row-remove" title="Remove">&times;</button>'
            + '</div><div class="about-repeater__row-body">';

        fieldsDef.forEach(function (field) {
            const fname = rname + '[' + index + '][' + field.name + ']';
            const flabel = field.label || '';
            rowHtml += '<div class="about-form__field">';
            if (flabel) rowHtml += '<label class="about-form__label">' + flabel + '</label>';
            if (field.type === 'textarea') {
                rowHtml += '<textarea name="' + fname + '" class="about-field about-field--textarea" rows="2"></textarea>';
            } else {
                rowHtml += '<input type="' + (field.type === 'number' ? 'number' : 'text') + '" name="' + fname + '" class="about-field about-field--text">';
            }
            rowHtml += '</div>';
        });

        rowHtml += '</div>';
        row.innerHTML = rowHtml;
        fieldEl.appendChild(row);
    });

    sectionList.addEventListener('click', function (e) {
        const removeBtn = e.target.closest('.about-repeater__row-remove');
        if (!removeBtn) return;

        const row = removeBtn.closest('.about-repeater__row');
        if (row) row.remove();
    });

    /* ====================================================================
       Revisions panel
       ==================================================================== */
    sectionList.addEventListener('click', function (e) {
        const revBtn = e.target.closest('.about-revision-btn');
        if (!revBtn) return;

        const form = revBtn.closest('.about-section-form');
        const sectionId = parseInt(form.dataset.sectionId);
        const panel = form.parentElement.querySelector('.about-revisions-panel');
        const isHidden = panel.hidden;

        if (isHidden) {
            panel.hidden = false;
            const listEl = panel.querySelector('.about-revisions__list');
            listEl.innerHTML = '<p>Loading...</p>';

            const xhr = new XMLHttpRequest();
            xhr.open('POST', ajaxUrl);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function () {
                let resp;
                try { resp = JSON.parse(xhr.responseText); } catch (e) { resp = { ok: false }; }
                if (!resp.ok || !resp.revisions) {
                    listEl.innerHTML = '<p>No revisions found.</p>';
                    return;
                }
                listEl.innerHTML = '';
                resp.revisions.forEach(function (rev) {
                    const div = document.createElement('div');
                    div.className = 'about-revisions__item';
                    div.innerHTML = '<span class="about-revisions__date">' + rev.created_at + '</span>'
                        + '<span class="about-revisions__note">' + (rev.revision_note || 'Auto-save') + '</span>'
                        + '<button type="button" class="btn btn--xs about-revision-restore" data-rev-id="' + rev.id + '">Restore</button>';
                    listEl.appendChild(div);
                });
            };
            xhr.send('action=get_revisions&section_id=' + sectionId);
        } else {
            panel.hidden = true;
        }
    });

    sectionList.addEventListener('click', function (e) {
        const restoreBtn = e.target.closest('.about-revision-restore');
        if (!restoreBtn) return;

        const revId = parseInt(restoreBtn.dataset.revId);
        if (!confirm('Restore this revision? Current changes will be lost.')) return;

        const xhr = new XMLHttpRequest();
        xhr.open('POST', ajaxUrl);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            let resp;
            try { resp = JSON.parse(xhr.responseText); } catch (e) { resp = { ok: false }; }
            if (resp.ok) {
                alert('Revision restored. Reload to see changes.');
            }
        };
        xhr.send('action=restore_revision&revision_id=' + revId);
    });

    /* ====================================================================
       Relation pickers (Portfolio + Blocks)
       ==================================================================== */
    const relationsUrl = ajaxUrl;

    /* Remove chip */
    document.querySelector('.about-relations') && document.querySelector('.about-relations').addEventListener('click', function (e) {
        const removeBtn = e.target.closest('.svc-relation-remove');
        if (!removeBtn) return;
        if (!confirm('Unlink?')) return;

        const chip = removeBtn.closest('.svc-relation-chip');
        const type = removeBtn.dataset.type;
        const relationId = removeBtn.dataset.relationId;
        const action = 'unlink_' + type;

        const xhr = new XMLHttpRequest();
        xhr.open('POST', relationsUrl);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            let resp;
            try { resp = JSON.parse(xhr.responseText); } catch (e) { resp = { ok: false }; }
            if (resp.ok) {
                chip.remove();
                const selectId = type === 'portfolio' ? 'about-portfolio-select' : 'about-blocks-select';
                const select = document.getElementById(selectId);
                if (select) {
                    const opt = select.querySelector('option[value="' + relationId + '"]');
                    if (opt) opt.disabled = false;
                }
            }
        };
        xhr.send('action=' + action + '&relation_id=' + relationId);
    });

    /* Link button */
    document.querySelector('.about-relations') && document.querySelector('.about-relations').addEventListener('click', function (e) {
        const linkBtn = e.target.closest('.svc-relation-link');
        if (!linkBtn) return;

        const type = linkBtn.dataset.type;
        const selectId = type === 'portfolio' ? 'about-portfolio-select' : 'about-blocks-select';
        const select = document.getElementById(selectId);
        if (!select) return;
        const val = select.value;
        if (!val) return;
        const label = select.options[select.selectedIndex].textContent;
        const action = 'link_' + type;

        const xhr = new XMLHttpRequest();
        xhr.open('POST', relationsUrl);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            let resp;
            try { resp = JSON.parse(xhr.responseText); } catch (e) { resp = { ok: false }; }
            if (resp.ok) {
                const linkedId = type === 'portfolio' ? 'about-portfolio-linked' : 'about-blocks-linked';
                const linked = document.getElementById(linkedId);
                if (linked) {
                    const chip = document.createElement('div');
                    chip.className = 'svc-relation-chip';
                    chip.dataset.relationId = val;
                    chip.innerHTML = label + '<button type="button" class="svc-relation-remove" data-type="' + type + '" data-relation-id="' + val + '">&times;</button>';
                    linked.appendChild(chip);
                    select.options[select.selectedIndex].disabled = true;
                    select.value = '';
                }
            }
        };
        xhr.send('action=' + action + '&relation_id=' + val);
    });
})();
