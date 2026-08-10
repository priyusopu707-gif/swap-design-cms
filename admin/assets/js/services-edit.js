/**
 * Swap Design - Services Editor JS
 * Tab switching, sub-item CRUD, relation linking/unlinking.
 */
(function () {
    'use strict';

    if (document.querySelector('.svc-edit-form') === null) return;

    var AJAX_URL = '/admin/ajax/services.php';

    /* ---- Tab Switching ---- */
    var tabs = document.querySelectorAll('.svc-tab');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var tabId = tab.getAttribute('data-tab');

            document.querySelectorAll('.svc-tab').forEach(function (t) {
                t.classList.remove('svc-tab--active');
                t.setAttribute('aria-selected', 'false');
            });
            document.querySelectorAll('.svc-tab-panel').forEach(function (p) {
                p.classList.remove('svc-tab-panel--active');
                p.hidden = true;
            });

            tab.classList.add('svc-tab--active');
            tab.setAttribute('aria-selected', 'true');

            var panel = document.getElementById('tab-' + tabId);
            if (panel) {
                panel.classList.add('svc-tab-panel--active');
                panel.hidden = false;
            }
        });
    });

    /* ---- Sub-Item CRUD (Features, Benefits, Process, FAQ) ---- */
    document.querySelectorAll('.svc-sub-list').forEach(function (list) {
        var type      = list.getAttribute('data-type');
        var serviceId = list.getAttribute('data-service-id');
        var container = list.querySelector('.svc-sub-items');

        // Add new item
        list.querySelector('.svc-sub-add').addEventListener('click', function () {
            addSubItem(list, type, serviceId, container);
        });

        // Remove button
        list.addEventListener('click', function (e) {
            var rm = e.target.closest('.svc-sub-remove');
            if (!rm) return;
            var item = rm.closest('.svc-sub-item');
            var id   = item.getAttribute('data-id');
            if (!id || id === 'new') { item.remove(); return; }
            if (!confirm('Delete this item?')) return;

            ajax('delete_sub', { sub_id: id, type: type, service_id: serviceId }, function () {
                item.remove();
            });
        });

        // Inline save on blur
        list.addEventListener('blur', function (e) {
            var field = e.target.closest('.svc-sub-field');
            if (!field) return;
            saveSubItem(field.closest('.svc-sub-item'), type, serviceId);
        }, true);
    });

    function addSubItem(list, type, serviceId, container) {
        var existing = container.querySelector('.svc-sub-item');
        var clone    = existing ? existing.cloneNode(true) : null;
        if (!clone) {
            // Create fresh item
            clone = document.createElement('div');
            clone.className = 'svc-sub-item';
            clone.setAttribute('data-id', 'new');
            var isFaq = (type === 'faqs');

            var input1 = document.createElement('input');
            input1.type = 'text';
            input1.className = 'svc-sub-field' + (isFaq ? ' svc-sub-field--wide' : '');
            input1.setAttribute('data-field', isFaq ? 'question' : 'icon');
            input1.placeholder = isFaq ? 'Question' : 'Icon name';

            var input2 = document.createElement('input');
            input2.type = 'text';
            input2.className = 'svc-sub-field' + (isFaq ? '' : '');
            input2.setAttribute('data-field', isFaq ? 'answer' : 'title');
            input2.placeholder = isFaq ? 'Answer' : 'Title';

            var input3 = document.createElement('input');
            input3.type = 'text';
            input3.className = 'svc-sub-field svc-sub-field--wide';
            input3.setAttribute('data-field', 'description');
            input3.placeholder = 'Description';

            var rm = document.createElement('button');
            rm.type = 'button';
            rm.className = 'svc-sub-remove';
            rm.title = 'Remove';
            rm.textContent = '\u2715';

            clone.appendChild(input1);
            clone.appendChild(input2);
            if (!isFaq) clone.appendChild(input3);
            clone.appendChild(rm);
        } else {
            clone.setAttribute('data-id', 'new');
            clone.querySelectorAll('.svc-sub-field').forEach(function (f) { f.value = ''; });
        }
        container.appendChild(clone);
        clone.querySelector('.svc-sub-field').focus();
    }

    function saveSubItem(item, type, serviceId) {
        var id   = item.getAttribute('data-id');
        var data = {};
        item.querySelectorAll('.svc-sub-field').forEach(function (f) {
            data[f.getAttribute('data-field')] = f.value;
        });

        ajax('save_sub', {
            type: type,
            service_id: serviceId,
            sub_id: id !== 'new' ? id : '',
            data: JSON.stringify(data)
        }, function (resp) {
            if (resp.id) item.setAttribute('data-id', resp.id);
        });
    }

    /* ---- Relations (Portfolio, Testimonials, Blocks) ---- */
    document.querySelectorAll('.svc-relation-link').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var type     = btn.getAttribute('data-type');
            var serviceId = document.querySelector('.svc-sub-list')?.getAttribute('data-service-id') || '';
            var selectId;

            if (type === 'portfolio')   selectId = '#svc-pf-select';
            if (type === 'testimonial') selectId = '#svc-testimonial-select';
            if (type === 'block')       selectId = '#svc-blocks-select';

            var select  = document.querySelector(selectId);
            var relationId = select ? select.value : '';
            if (!relationId) return;

            var label = select.options[select.selectedIndex].text;

            ajax('link_relation', {
                type: type,
                service_id: serviceId,
                relation_id: relationId
            }, function () {
                var linkedContainer = document.getElementById('svc-' + (type === 'block' ? 'blocks' : (type === 'testimonial' ? 'testimonial' : 'pf')) + '-linked');
                if (!linkedContainer) return;

                var chip = document.createElement('div');
                chip.className = 'svc-relation-chip';
                chip.setAttribute('data-relation-id', relationId);
                chip.innerHTML = label + ' <button type="button" class="svc-relation-remove" data-type="' + type + '" data-relation-id="' + relationId + '">&times;</button>';
                linkedContainer.appendChild(chip);

                select.querySelector('option[value="' + relationId + '"]').remove();
                select.value = '';
            });
        });
    });

    // Unlink via chip remove button
    document.addEventListener('click', function (e) {
        var rm = e.target.closest('.svc-relation-remove');
        if (!rm) return;

        var type       = rm.getAttribute('data-type');
        var relationId = rm.getAttribute('data-relation-id');
        var serviceId  = document.querySelector('.svc-sub-list')?.getAttribute('data-service-id') || '';

        ajax('unlink_relation', {
            type: type,
            service_id: serviceId,
            relation_id: relationId
        }, function () {
            rm.closest('.svc-relation-chip').remove();
        });
    });

    /* ---- AJAX Helper ---- */
    function ajax(action, data, callback) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', AJAX_URL, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.onload = function () {
            var resp;
            try { resp = JSON.parse(xhr.responseText); } catch (e) { resp = { ok: false }; }
            if (callback) callback(resp);
        };

        var parts = ['action=' + encodeURIComponent(action)];
        for (var key in data) {
            if (!data.hasOwnProperty(key)) continue;
            parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
        }
        xhr.send(parts.join('&'));
    }
})();
