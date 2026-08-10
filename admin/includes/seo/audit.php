<?php
/**
 * Full Audit Tab - Detailed findings
 */

$auditor = new SEOAuditor();
$audit = $auditor->runAudit();
$groups = $audit['groups'];
$totals = $audit['totals'];
?>

<div class="seo-audit-results">
    <h2>Full SEO Audit Results</h2>

    <?php if (array_sum($totals) === 0): ?>
        <div class="success-message">
            ✅ Perfect! No SEO issues found.
        </div>
    <?php else: ?>
        <!-- Issues Summary -->
        <div class="audit-summary">
            <h3>Issues Found: <?php echo array_sum($totals); ?></h3>
            <div class="summary-grid">
                <?php foreach ($totals as $check => $count): ?>
                    <?php if ($count > 0): ?>
                        <div class="summary-item">
                            <span class="count"><?php echo $count; ?></span>
                            <span class="label"><?php echo ucwords(str_replace('_', ' ', $check)); ?></span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Detailed Findings by Category -->
        <?php foreach ($groups as $category => $findings): ?>
            <?php if (!empty($findings)): ?>
                <div class="audit-section">
                    <h3><?php echo ucwords(str_replace('_', ' ', $category)); ?> (<?php echo count($findings); ?>)</h3>
                    <table class="audit-table">
                        <thead>
                            <tr>
                                <th>Content</th>
                                <th>Type</th>
                                <th>Issue</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($findings, 0, 20) as $finding): ?>
                                <tr>
                                    <td><strong><?php echo esc($finding['title'] ?? 'Unknown'); ?></strong></td>
                                    <td><span class="badge"><?php echo ucfirst($finding['type'] ?? 'page'); ?></span></td>
                                    <td><?php echo esc($finding['issue'] ?? 'Issue found'); ?></td>
                                    <td><span class="status-warning">⚠️ Needs Fix</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (count($findings) > 20): ?>
                        <p class="help-text">Showing 20 of <?php echo count($findings); ?> issues. Run audit in batches to fix priority items first.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Export Audit -->
    <div class="audit-export">
        <button class="btn btn-secondary" onclick="exportAuditCSV()">
            Export as CSV
        </button>
        <button class="btn btn-secondary" onclick="emailAuditReport()">
            Email Report
        </button>
    </div>
</div>

<script>
function exportAuditCSV() {
    let csv = "Type,Title,Issue,Status\n";
    // Generate CSV from table data
    document.querySelectorAll('.audit-table tbody tr').forEach(row => {
        let cells = row.querySelectorAll('td');
        csv += `"${cells[0].textContent}","${cells[1].textContent}","${cells[2].textContent}","${cells[3].textContent}"\n`;
    });

    let blob = new Blob([csv], {type: 'text/csv'});
    let url = window.URL.createObjectURL(blob);
    let a = document.createElement('a');
    a.href = url;
    a.download = 'seo-audit-' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
}

function emailAuditReport() {
    alert('Email feature coming soon. For now, use Export CSV to share the report.');
}
</script>
