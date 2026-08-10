/**
 * Swap Design - Search Dashboard JS
 * Rebuild index, clear logs, toggle logging, export CSV.
 */
(function () {
    'use strict';

    var buttons = document.querySelectorAll('[data-search-action]');
    if (buttons.length === 0) return;

    var AJAX_URL = '/admin/ajax/search.php';
    var feedback = document.getElementById('search-action-feedback');

    function setFeedback(message, isError) {
        if (!feedback) return;
        feedback.textContent = message;
        feedback.className = 'admin-search-actions__hint' + (isError ? ' admin-search-actions__hint--error' : '');
    }

    function ajax(action, data, callback) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', AJAX_URL, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('X-CSRF-Token', window.csrfToken || '');

        xhr.onload = function () {
            var resp;
            try { resp = JSON.parse(xhr.responseText); } catch (e) { resp = { ok: false, message: 'Invalid response' }; }
            if (callback) callback(resp);
        };

        xhr.onerror = function () {
            if (callback) callback({ ok: false, message: 'Network error' });
        };

        var parts = ['action=' + encodeURIComponent(action), 'token=' + encodeURIComponent(window.csrfToken || '')];
        for (var key in data) {
            if (!data.hasOwnProperty(key)) continue;
            parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
        }
        xhr.send(parts.join('&'));
    }

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var action = btn.getAttribute('data-search-action');

            if (action === 'clear_logs' && !confirm('Clear all search logs and click analytics? This cannot be undone.')) {
                return;
            }
            if (action === 'rebuild_index' && !confirm('Rebuild the full search index from current content?')) {
                return;
            }

            btn.disabled = true;
            setFeedback('Working...', false);

            if (action === 'export_csv') {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = AJAX_URL;
                form.style.display = 'none';

                ['action', 'token'].forEach(function (name) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = name === 'action' ? 'export_csv' : (window.csrfToken || '');
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
                btn.disabled = false;
                setFeedback('Export started. Check your downloads.', false);
                return;
            }

            var data = {};
            if (action === 'toggle_logging') {
                var currentlyEnabled = btn.getAttribute('data-enabled') === '1';
                data.enabled = currentlyEnabled ? '0' : '1';
            }

            ajax(action, data, function (resp) {
                btn.disabled = false;
                setFeedback(resp.message || (resp.ok ? 'Done' : 'Failed'), !resp.ok);

                if (resp.ok && action === 'rebuild_index') {
                    setTimeout(function () { window.location.reload(); }, 600);
                }

                if (resp.ok && action === 'toggle_logging') {
                    setTimeout(function () { window.location.reload(); }, 600);
                }
            });
        });
    });
})();
