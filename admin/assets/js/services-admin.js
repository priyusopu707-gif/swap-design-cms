/**
 * Swap Design - Services Admin List JS
 * Drag-drop reorder + bulk select for service list.
 */
(function () {
    'use strict';

    var tableBody = document.getElementById('svc-table-body');
    if (!tableBody) return;

    var toolbar = document.getElementById('svc-toolbar');
    var selectAll = document.getElementById('svc-select-all');
    var selectedCount = document.getElementById('svc-selected-count');
    var bulkIds = document.getElementById('svc-bulk-ids');

    /* ---- Bulk select ---- */
    function updateBulkState() {
        var boxes = tableBody.querySelectorAll('.svc-checkbox');
        var checked = tableBody.querySelectorAll('.svc-checkbox:checked');
        var ids = [];

        checked.forEach(function (cb) { ids.push(cb.value); });

        if (selectedCount) selectedCount.textContent = ids.length;
        if (bulkIds) bulkIds.value = ids.join(',');
        if (toolbar) toolbar.style.display = ids.length > 0 ? '' : 'none';
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            var boxes = tableBody.querySelectorAll('.svc-checkbox');
            boxes.forEach(function (cb) { cb.checked = selectAll.checked; });
            updateBulkState();
        });
    }

    tableBody.addEventListener('change', function (e) {
        if (e.target.matches('.svc-checkbox')) {
            if (!e.target.checked && selectAll) selectAll.checked = false;
            updateBulkState();
        }
    });

    /* ---- Drag-drop reorder ---- */
    var draggedRow = null;

    tableBody.addEventListener('dragstart', function (e) {
        var row = e.target.closest('.svc-table__row');
        if (!row) return;
        draggedRow = row;
        row.classList.add('svc-table__row--dragging');
        e.dataTransfer.effectAllowed = 'move';
    });

    tableBody.addEventListener('dragend', function () {
        if (draggedRow) draggedRow.classList.remove('svc-table__row--dragging');
        tableBody.querySelectorAll('.svc-table__row--drag-over').forEach(function (r) {
            r.classList.remove('svc-table__row--drag-over');
        });
        draggedRow = null;
    });

    tableBody.addEventListener('dragover', function (e) {
        e.preventDefault();
        var row = e.target.closest('.svc-table__row');
        if (!row || row === draggedRow) return;
        row.classList.add('svc-table__row--drag-over');
    });

    tableBody.addEventListener('dragleave', function (e) {
        var row = e.target.closest('.svc-table__row');
        if (row) row.classList.remove('svc-table__row--drag-over');
    });

    tableBody.addEventListener('drop', function (e) {
        e.preventDefault();
        var target = e.target.closest('.svc-table__row');
        if (!target || target === draggedRow) return;
        target.classList.remove('svc-table__row--drag-over');

        var rows = Array.from(tableBody.querySelectorAll('.svc-table__row'));
        var tIdx = rows.indexOf(target);
        var dIdx = rows.indexOf(draggedRow);

        if (tIdx < dIdx) {
            tableBody.insertBefore(draggedRow, target);
        } else {
            tableBody.insertBefore(draggedRow, target.nextSibling);
        }

        saveServiceOrder();
    });

    function saveServiceOrder() {
        var ids = [];
        tableBody.querySelectorAll('.svc-table__row').forEach(function (row) {
            ids.push(row.getAttribute('data-service-id'));
        });

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/admin/ajax/services.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send('action=reorder_services&order=' + encodeURIComponent(ids.join(',')));
    }
})();
