/* SEO Dashboard JavaScript */

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('show');
    }, 10);

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

/**
 * Get CSRF token from DOM
 */
function getCsrfToken() {
    return document.querySelector('input[name="csrf_token"]')?.value ||
           document.querySelector('meta[name="csrf-token"]')?.content ||
           '';
}

/**
 * Export audit results as CSV
 */
function exportAuditCSV() {
    let csv = "Type,Title,Issue,Status\n";

    document.querySelectorAll('.audit-table tbody tr').forEach(row => {
        const cells = row.querySelectorAll('td');
        const type = cells[1]?.textContent?.trim() || '';
        const title = cells[0]?.textContent?.trim() || '';
        const issue = cells[2]?.textContent?.trim() || '';
        const status = cells[3]?.textContent?.trim() || '';

        csv += `"${escapeCSV(title)}","${escapeCSV(type)}","${escapeCSV(issue)}","${escapeCSV(status)}"\n`;
    });

    downloadCSV(csv, 'seo-audit-' + new Date().toISOString().split('T')[0] + '.csv');
}

/**
 * Escape CSV values
 */
function escapeCSV(str) {
    if (typeof str !== 'string') return '';
    return str.replace(/"/g, '""');
}

/**
 * Download CSV file
 */
function downloadCSV(csv, filename) {
    const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

/**
 * Regenerate sitemap
 */
function regenerateSitemap() {
    if (!confirm('Regenerate sitemap? This may take a few seconds.')) return;

    const btn = event.target;
    btn.disabled = true;
    btn.textContent = 'Regenerating...';

    fetch('/admin/ajax/seo.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN': getCsrfToken()
        },
        body: 'action=regenerate_sitemap&token=' + encodeURIComponent(getCsrfToken())
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            showToast('✅ Sitemap regenerated successfully!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast('❌ Error: ' + (data.message || 'Unknown error'), 'error');
            btn.disabled = false;
            btn.textContent = 'Regenerate Sitemap';
        }
    })
    .catch(err => {
        showToast('❌ Error: ' + err.message, 'error');
        btn.disabled = false;
        btn.textContent = 'Regenerate Sitemap';
    });
}

/**
 * Email audit report
 */
function emailAuditReport() {
    showToast('📧 Feature coming soon. Use "Export CSV" to share the report.', 'info');
}

/**
 * Initialize dashboard
 */
document.addEventListener('DOMContentLoaded', function() {
    // Add any initialization code here
});
