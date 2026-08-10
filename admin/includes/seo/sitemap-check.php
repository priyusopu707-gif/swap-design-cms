<?php
/**
 * Sitemap Verification Tab
 */

$sitemapGenerator = new SitemapGenerator();
$stats = $sitemapGenerator->getStats();
?>

<div class="sitemap-check">
    <div class="sitemap-header">
        <h2>Sitemap Verification</h2>
        <p>Monitor sitemap health and submission status</p>
    </div>

    <!-- Sitemap Stats -->
    <div class="sitemap-stats">
        <div class="stat-card">
            <h4><?php echo (int)$stats['total_urls']; ?></h4>
            <p>Total URLs in Sitemap</p>
        </div>
        <div class="stat-card">
            <h4><?php echo (int)$stats['pages']; ?></h4>
            <p>Pages</p>
        </div>
        <div class="stat-card">
            <h4><?php echo (int)$stats['blog_posts']; ?></h4>
            <p>Blog Posts</p>
        </div>
        <div class="stat-card">
            <h4><?php echo (int)$stats['services']; ?></h4>
            <p>Services</p>
        </div>
        <div class="stat-card">
            <h4><?php echo (int)$stats['portfolio']; ?></h4>
            <p>Portfolio Items</p>
        </div>
        <div class="stat-card">
            <h4><?php echo (int)$stats['images']; ?></h4>
            <p>Images</p>
        </div>
        <div class="stat-card">
            <h4><?php echo date('Y-m-d H:i:s', $stats['last_generated']); ?></h4>
            <p>Last Generated</p>
        </div>
    </div>

    <!-- Sitemap Files -->
    <div class="sitemap-files">
        <h3>Sitemap Files</h3>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Sitemap</th>
                    <th>URL Count</th>
                    <th>Last Modified</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>sitemap.xml</strong> (Index)</td>
                    <td>7 files</td>
                    <td><?php echo date('Y-m-d H:i', $stats['last_generated']); ?></td>
                    <td>
                        <a href="/sitemap.xml" target="_blank" class="btn-link">View</a>
                    </td>
                </tr>
                <tr>
                    <td>sitemap-pages.xml</td>
                    <td><?php echo (int)$stats['pages']; ?></td>
                    <td><?php echo date('Y-m-d H:i', $stats['last_generated']); ?></td>
                    <td>
                        <a href="/sitemap-pages.xml" target="_blank" class="btn-link">View</a>
                    </td>
                </tr>
                <tr>
                    <td>sitemap-blog.xml</td>
                    <td><?php echo (int)$stats['blog_posts']; ?></td>
                    <td><?php echo date('Y-m-d H:i', $stats['last_generated']); ?></td>
                    <td>
                        <a href="/sitemap-blog.xml" target="_blank" class="btn-link">View</a>
                    </td>
                </tr>
                <tr>
                    <td>sitemap-services.xml</td>
                    <td><?php echo (int)$stats['services']; ?></td>
                    <td><?php echo date('Y-m-d H:i', $stats['last_generated']); ?></td>
                    <td>
                        <a href="/sitemap-services.xml" target="_blank" class="btn-link">View</a>
                    </td>
                </tr>
                <tr>
                    <td>sitemap-portfolio.xml</td>
                    <td><?php echo (int)$stats['portfolio']; ?></td>
                    <td><?php echo date('Y-m-d H:i', $stats['last_generated']); ?></td>
                    <td>
                        <a href="/sitemap-portfolio.xml" target="_blank" class="btn-link">View</a>
                    </td>
                </tr>
                <tr>
                    <td>sitemap-images.xml</td>
                    <td><?php echo (int)$stats['images']; ?></td>
                    <td><?php echo date('Y-m-d H:i', $stats['last_generated']); ?></td>
                    <td>
                        <a href="/sitemap-images.xml" target="_blank" class="btn-link">View</a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Submission Instructions -->
    <div class="submission-instructions">
        <h3>Search Engine Submission</h3>
        <div class="instruction-card">
            <h4>🔍 Google Search Console</h4>
            <ol>
                <li>Go to <a href="https://search.google.com/search-console" target="_blank">Google Search Console</a></li>
                <li>Select your property</li>
                <li>Go to Sitemaps (left menu)</li>
                <li>Click "Add a new sitemap"</li>
                <li>Enter: <code>https://swapdesign.com/sitemap.xml</code></li>
                <li>Click Submit</li>
            </ol>
        </div>

        <div class="instruction-card">
            <h4>🔗 Bing Webmaster Tools</h4>
            <ol>
                <li>Go to <a href="https://www.bing.com/webmasters" target="_blank">Bing Webmaster Tools</a></li>
                <li>Select your site</li>
                <li>Go to Sitemaps (left menu)</li>
                <li>Submit: <code>https://swapdesign.com/sitemap.xml</code></li>
            </ol>
        </div>

        <div class="instruction-card">
            <h4>📝 Robots.txt Configuration</h4>
            <p>Your robots.txt already includes the sitemap reference:</p>
            <pre><code>Sitemap: https://swapdesign.com/sitemap.xml</code></pre>
            <a href="/robots.txt" target="_blank" class="btn-link">View robots.txt</a>
        </div>
    </div>

    <!-- Regenerate Button -->
    <div class="sitemap-actions">
        <button class="btn btn-primary" onclick="regenerateSitemap()">
            Regenerate Sitemap
        </button>
        <p class="help-text">Regenerate if you've made major content changes. This happens automatically on content updates.</p>
    </div>
</div>

<script>
function regenerateSitemap() {
    if (!confirm('This may take a few seconds. Continue?')) return;

    fetch('/admin/ajax/seo.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN': getCsrfToken()
        },
        body: 'action=regenerate_sitemap'
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            showToast('Sitemap regenerated successfully!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('Error: ' + (data.message || 'Unknown error'), 'error');
        }
    })
    .catch(err => showToast('Error: ' + err.message, 'error'));
}
</script>
