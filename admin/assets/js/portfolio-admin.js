(function() {
    'use strict';

    var table = document.getElementById('pf-table');
    if (!table) return;

    var form = document.getElementById('pf-list-form');
    var bulkIds = document.getElementById('pf-bulk-ids');
    var toolbar = document.getElementById('pf-toolbar');
    var selectAll = document.getElementById('pf-select-all');
    var countSpan = document.getElementById('pf-selected-count');
    var rows = table.querySelectorAll('.svc-table__row');

    function updateBulk() {
        var checked = table.querySelectorAll('.svc-checkbox:checked');
        var ids = [];
        checked.forEach(function(cb) { ids.push(cb.value); });
        bulkIds.value = ids.join(',');
        countSpan.textContent = ids.length;
        toolbar.style.display = ids.length ? '' : 'none';
    }

    selectAll && selectAll.addEventListener('change', function() {
        table.querySelectorAll('.svc-checkbox').forEach(function(cb) { cb.checked = selectAll.checked; });
        updateBulk();
    });

    rows.forEach(function(row) {
        var cb = row.querySelector('.svc-checkbox');
        cb && cb.addEventListener('change', updateBulk);

        var handle = row.querySelector('.svc-drag-handle');
        if (!handle) return;

        handle.addEventListener('mousedown', function() { row.draggable = true; });
        row.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('text/plain', row.dataset.projectId);
            row.classList.add('svc-table__row--dragging');
        });
        row.addEventListener('dragend', function() { row.classList.remove('svc-table__row--dragging'); row.draggable = false; });
    });

    var tbody = document.getElementById('pf-table-body');
    tbody && tbody.addEventListener('dragover', function(e) { e.preventDefault(); e.dataTransfer.dropEffect = 'move'; });
    tbody && tbody.addEventListener('drop', function(e) {
        e.preventDefault();
        var id = e.dataTransfer.getData('text/plain');
        var draggable = tbody.querySelector('[data-project-id="' + id + '"]');
        var after = null;
        var items = tbody.querySelectorAll('.svc-table__row');
        var dropY = e.clientY;
        items.forEach(function(item) {
            if (item === draggable) return;
            var box = item.getBoundingClientRect();
            if (dropY > box.top + box.height / 2) after = item;
        });

        if (after) { after.parentNode.insertBefore(draggable, after.nextSibling); tbody.insertBefore(draggable, after); }
        else tbody.appendChild(draggable);

        var order = [];
        tbody.querySelectorAll('.svc-table__row').forEach(function(r) { order.push(parseInt(r.dataset.projectId)); });

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/admin/ajax/portfolio.php');
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send('action=reorder&project_id=0&order=' + encodeURIComponent(JSON.stringify(order)));
    });
})();
