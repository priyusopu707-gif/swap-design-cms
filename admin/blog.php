<?php
/**
 * Swap Design - Blog Admin
 *
 * Dual-mode page: list dashboard with filters OR post editor.
 * Rich text editing, taxonomy, SEO, revision history, related content.
 */

require __DIR__ . '/includes/init.php';
Auth::require();

$blogManager  = new BlogManager();
$currentSection = 'blog';
$pageTitle      = 'Blog';
$db = Database::getInstance();

$editId  = $_GET['edit'] ?? null;
$action  = $_GET['action'] ?? 'list';

/* Handle POST for quick category/tag management */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'list') {
    $postAction = $_POST['action'] ?? '';
    if ($postAction === 'add_category' && !empty($_POST['name'])) {
        $blogManager->createCategory(['name' => sanitizeString($_POST['name'])]);
    } elseif ($postAction === 'add_tag' && !empty($_POST['name'])) {
        $blogManager->createTag(['name' => sanitizeString($_POST['name'])]);
    } elseif ($postAction === 'delete_category' && !empty($_POST['id'])) {
        $blogManager->deleteCategory((int)$_POST['id']);
    } elseif ($postAction === 'delete_tag' && !empty($_POST['id'])) {
        $blogManager->deleteTag((int)$_POST['id']);
    } elseif ($postAction === 'delete_post' && !empty($_POST['id'])) {
        $blogManager->deletePost((int)$_POST['id']);
    } elseif ($postAction === 'duplicate_post' && !empty($_POST['id'])) {
        $newId = $blogManager->duplicatePost((int)$_POST['id']);
        header('Location: /admin/blog.php?edit=' . $newId);
        exit;
    } elseif ($postAction === 'update_status' && !empty($_POST['id']) && !empty($_POST['status'])) {
        $blogManager->setStatus((int)$_POST['id'], $_POST['status']);
    }
    header('Location: /admin/blog.php');
    exit;
}

/* Editor mode */
if ($editId || $action === 'new') {
    $post = null;
    $postCategories = [];
    $postTags = [];
    $relationships = [];

    if ($action === 'new') {
        $editId = null;
    } elseif ($editId) {
        $post = $blogManager->getPostById((int)$editId);
        if (!$post) {
            header('Location: /admin/blog.php');
            exit;
        }
        $postCategories = $blogManager->getPostCategories($post['id']);
        $postTags = $blogManager->getPostTags($post['id']);
        $relationships = $blogManager->getRelationships($post['id']);
    }

    $categories = $blogManager->getAllCategories();
    $allTags = $blogManager->getAllTags();
    $authors = $db->fetchAll("SELECT id, username, display_name FROM users ORDER BY username");
    $services = $db->fetchAll("SELECT id, title FROM services WHERE status = 'published' ORDER BY title");
    $portfolio = $db->fetchAll("SELECT id, title FROM portfolio_items WHERE status = 'published' ORDER BY title");

    /* Revisions */
    $revisions = $editId ? $blogManager->getRevisions($post['id']) : [];

    $pageTitle = $post ? 'Edit Post' : 'New Post';
    $postCatIds = array_column($postCategories, 'id');
    $postTagNames = array_column($postTags, 'name');
    $relPostIds = $relationships['post'] ?? [];
    $relServiceIds = $relationships['service'] ?? [];
    $relPortfolioIds = $relationships['portfolio'] ?? [];

    /* Gallery array */
    $gallery = $post['gallery'] ?? [];

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <?php include __DIR__ . '/includes/head.php'; ?>
        <link rel="stylesheet" href="/admin/assets/css/blog-editor.css">
    </head>
    <body class="admin-body">
        <a href="#admin-content" class="admin-skip-link">Skip to main content</a>
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        <?php require __DIR__ . '/includes/topbar.php'; ?>
        <main class="admin-main">
            <div class="admin-content" id="admin-content">
                <div class="admin-page-header">
                    <a href="/admin/blog.php" class="btn btn--ghost">&larr; Back to Posts</a>
                    <h1><?php echo esc($pageTitle); ?></h1>
                    <div class="admin-page-header__actions">
                        <?php if ($post): ?>
                        <a href="/blog/<?php echo esc($post['slug']); ?>" target="_blank" class="btn btn--secondary" rel="noopener">View</a>
                        <button type="button" class="btn btn--secondary" id="blog-save-revision">Save Revision</button>
                        <?php endif; ?>
                        <button type="button" class="btn btn--primary" id="blog-save-publish">Publish</button>
                        <button type="button" class="btn btn--secondary" id="blog-save-draft">Save Draft</button>
                        <span class="blog-editor__saved" id="blog-save-status" hidden>Saved</span>
                    </div>
                </div>

                <input type="hidden" id="blog-post-id" value="<?php echo $post['id'] ?? ''; ?>">

                <div class="blog-editor__layout">
                    <!-- Main -->
                    <div class="blog-editor__main">
                        <div class="blog-editor__section">
                            <input type="text" id="blog-title" class="blog-editor__title-input" placeholder="Post Title" value="<?php echo esc($post['title'] ?? ''); ?>">
                            <input type="text" id="blog-slug" class="blog-editor__slug-input" placeholder="post-slug" value="<?php echo esc($post['slug'] ?? ''); ?>">
                            <textarea id="blog-desc" class="blog-editor__desc-input" rows="2" placeholder="Short description..."><?php echo esc($post['short_description'] ?? ''); ?></textarea>
                        </div>

                        <div class="blog-editor__section">
                            <div class="blog-editor__toolbar" id="blog-toolbar">
                                <button type="button" data-cmd="bold" title="Bold"><b>B</b></button>
                                <button type="button" data-cmd="italic" title="Italic"><i>I</i></button>
                                <button type="button" data-cmd="underline" title="Underline"><u>U</u></button>
                                <span class="blog-editor__toolbar-sep"></span>
                                <button type="button" data-cmd="formatBlock" data-arg="h2" title="Heading 2">H2</button>
                                <button type="button" data-cmd="formatBlock" data-arg="h3" title="Heading 3">H3</button>
                                <button type="button" data-cmd="formatBlock" data-arg="p" title="Paragraph">P</button>
                                <span class="blog-editor__toolbar-sep"></span>
                                <button type="button" data-cmd="insertUnorderedList" title="Bullet List">&#8226; List</button>
                                <button type="button" data-cmd="insertOrderedList" title="Numbered List">1. List</button>
                                <button type="button" data-cmd="formatBlock" data-arg="blockquote" title="Quote">Quote</button>
                                <span class="blog-editor__toolbar-sep"></span>
                                <button type="button" data-cmd="createLink" title="Insert Link">Link</button>
                                <button type="button" id="blog-insert-image" title="Insert Image">Image</button>
                                <button type="button" id="blog-insert-code" title="Code Block">Code</button>
                                <button type="button" id="blog-insert-callout" title="Callout Box">Callout</button>
                                <button type="button" id="blog-insert-video" title="Video Embed">Video</button>
                                <span class="blog-editor__toolbar-sep"></span>
                                <button type="button" id="blog-generate-toc" title="Insert TOC placeholder">TOC</button>
                            </div>
                            <div class="blog-editor__content" id="blog-content" contenteditable="true"><?php echo $post['content'] ?? ''; ?></div>
                            <input type="hidden" id="blog-content-hidden" value="<?php echo esc(json_encode($post['content'] ?? '')); ?>">
                        </div>

                        <!-- Gallery -->
                        <div class="blog-editor__section">
                            <h3 class="blog-editor__section-title">Gallery</h3>
                            <div class="blog-editor__gallery" id="blog-gallery">
                                <?php foreach ($gallery as $gi => $gimg): ?>
                                <div class="blog-editor__gallery-item">
                                    <img src="<?php echo esc($gimg); ?>" alt="">
                                    <button type="button" class="blog-editor__gallery-remove">&times;</button>
                                    <input type="hidden" name="gallery[]" value="<?php echo esc($gimg); ?>">
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="blog-add-gallery" class="btn btn--secondary">+ Add Image</button>
                        </div>

                        <!-- SEO -->
                        <div class="blog-editor__section">
                            <h3 class="blog-editor__section-title">SEO</h3>
                            <div class="blog-editor__seo-grid">
                                <div class="blog-editor__field">
                                    <label>SEO Title</label>
                                    <input type="text" id="blog-seo-title" value="<?php echo esc($post['seo_title'] ?? ''); ?>">
                                </div>
                                <div class="blog-editor__field">
                                    <label>Meta Description</label>
                                    <textarea id="blog-meta-desc" rows="2"><?php echo esc($post['meta_description'] ?? ''); ?></textarea>
                                </div>
                                <div class="blog-editor__field">
                                    <label>Focus Keyword</label>
                                    <input type="text" id="blog-focus-keyword" value="<?php echo esc($post['focus_keyword'] ?? ''); ?>">
                                </div>
                                <div class="blog-editor__field">
                                    <label>Canonical URL</label>
                                    <input type="text" id="blog-canonical" value="<?php echo esc($post['canonical_url'] ?? ''); ?>">
                                </div>
                                <div class="blog-editor__field">
                                    <label>OG Image</label>
                                    <input type="text" id="blog-og-image" value="<?php echo esc($post['og_image'] ?? ''); ?>">
                                </div>
                                <div class="blog-editor__field">
                                    <label>Twitter Card</label>
                                    <select id="blog-twitter-card">
                                        <option value="summary_large_image" <?php echo ($post['twitter_card'] ?? '') === 'summary_large_image' ? 'selected' : ''; ?>>Summary Large Image</option>
                                        <option value="summary" <?php echo ($post['twitter_card'] ?? '') === 'summary' ? 'selected' : ''; ?>>Summary</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Revisions -->
                        <?php if ($post && $revisions): ?>
                        <div class="blog-editor__section">
                            <h3 class="blog-editor__section-title">Revision History</h3>
                            <div class="blog-revisions" id="blog-revisions">
                                <?php foreach ($revisions as $rev): ?>
                                <div class="blog-revision">
                                    <span class="blog-revision__date"><?php echo esc($rev['created_at']); ?></span>
                                    <span class="blog-revision__note"><?php echo esc($rev['revision_note'] ?? 'Revision'); ?></span>
                                    <button type="button" class="btn btn--secondary btn--sm blog-revision__restore" data-rev-id="<?php echo (int)$rev['id']; ?>">Restore</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Sidebar -->
                    <div class="blog-editor__sidebar">
                        <!-- Featured Image -->
                        <div class="blog-editor__panel">
                            <h3 class="blog-editor__panel-title">Featured Image</h3>
                            <div class="blog-editor__image-preview" id="blog-featured-preview">
                                <?php if ($post['featured_image'] ?? ''): ?>
                                <img src="<?php echo esc($post['featured_image']); ?>" alt="">
                                <?php endif; ?>
                            </div>
                            <input type="text" id="blog-featured-image" placeholder="/uploads/image.jpg" value="<?php echo esc($post['featured_image'] ?? ''); ?>">
                        </div>

                        <!-- Status -->
                        <div class="blog-editor__panel">
                            <h3 class="blog-editor__panel-title">Status</h3>
                            <select id="blog-status">
                                <?php foreach (BlogManager::STATUSES as $st): ?>
                                <option value="<?php echo esc($st); ?>" <?php echo ($post['status'] ?? 'draft') === $st ? 'selected' : ''; ?>><?php echo esc(BlogManager::STATUS_LABELS[$st]); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label class="blog-editor__label-mt">Publish Date</label>
                            <input type="datetime-local" id="blog-published-at" value="<?php echo $post['published_at'] ? date('Y-m-d\TH:i', strtotime($post['published_at'])) : ''; ?>">
                        </div>

                        <!-- Author -->
                        <div class="blog-editor__panel">
                            <h3 class="blog-editor__panel-title">Author</h3>
                            <select id="blog-author">
                                <option value="">-- Select --</option>
                                <?php foreach ($authors as $a): ?>
                                <option value="<?php echo (int)$a['id']; ?>" <?php echo ($post['author_id'] ?? '') == $a['id'] ? 'selected' : ''; ?>><?php echo esc($a['display_name'] ?: $a['username']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Categories -->
                        <div class="blog-editor__panel">
                            <h3 class="blog-editor__panel-title">Categories</h3>
                            <div class="blog-editor__checklist" id="blog-categories">
                                <?php foreach ($categories as $cat): ?>
                                <label class="blog-editor__checkitem">
                                    <input type="checkbox" value="<?php echo (int)$cat['id']; ?>" <?php echo in_array($cat['id'], $postCatIds) ? 'checked' : ''; ?>>
                                    <span><?php echo esc($cat['name']); ?> (<?php echo (int)$cat['post_count']; ?>)</span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="blog-editor__add-term">
                                <input type="text" id="blog-new-category" placeholder="New category...">
                                <button type="button" id="blog-add-category" class="btn btn--secondary btn--sm">Add</button>
                            </div>
                        </div>

                        <!-- Tags -->
                        <div class="blog-editor__panel">
                            <h3 class="blog-editor__panel-title">Tags</h3>
                            <div class="blog-editor__tags-input-wrap">
                                <input type="text" id="blog-tags-input" placeholder="Type and press Enter" value="<?php echo esc(implode(', ', $postTagNames)); ?>">
                            </div>
                            <div class="blog-editor__suggested-tags">
                                <?php foreach ($allTags as $tg): ?>
                                <span class="blog-editor__suggested-tag" data-tag="<?php echo esc($tg['name']); ?>"><?php echo esc($tg['name']); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Options -->
                        <div class="blog-editor__panel">
                            <h3 class="blog-editor__panel-title">Options</h3>
                            <label class="blog-editor__checkitem">
                                <input type="checkbox" id="blog-is-featured" <?php echo ($post['is_featured'] ?? 0) ? 'checked' : ''; ?>>
                                <span>Featured Post</span>
                            </label>
                            <label class="blog-editor__checkitem">
                                <input type="checkbox" id="blog-is-sticky" <?php echo ($post['is_sticky'] ?? 0) ? 'checked' : ''; ?>>
                                <span>Sticky Post</span>
                            </label>
                        </div>

                        <!-- Related Content -->
                        <div class="blog-editor__panel">
                            <h3 class="blog-editor__panel-title">Related Services</h3>
                            <div class="blog-editor__checklist" id="blog-rel-services">
                                <?php foreach ($services as $svc): ?>
                                <label class="blog-editor__checkitem">
                                    <input type="checkbox" value="<?php echo (int)$svc['id']; ?>" <?php echo in_array($svc['id'], $relServiceIds) ? 'checked' : ''; ?>>
                                    <span><?php echo esc($svc['title']); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="blog-editor__panel">
                            <h3 class="blog-editor__panel-title">Related Portfolio</h3>
                            <div class="blog-editor__checklist" id="blog-rel-portfolio">
                                <?php foreach ($portfolio as $pf): ?>
                                <label class="blog-editor__checkitem">
                                    <input type="checkbox" value="<?php echo (int)$pf['id']; ?>" <?php echo in_array($pf['id'], $relPortfolioIds) ? 'checked' : ''; ?>>
                                    <span><?php echo esc($pf['title']); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="blog-editor__panel">
                            <h3 class="blog-editor__panel-title">Related Posts</h3>
                            <div class="blog-editor__checklist" id="blog-rel-posts">
                                <?php
                                $allPosts = $blogManager->getAllPosts(['status' => 'published'], 1, 100);
                                foreach ($allPosts as $bp): if ($bp['id'] == ($post['id'] ?? 0)) continue; ?>
                                <label class="blog-editor__checkitem">
                                    <input type="checkbox" value="<?php echo (int)$bp['id']; ?>" <?php echo in_array($bp['id'], $relPostIds) ? 'checked' : ''; ?>>
                                    <span><?php echo esc($bp['title']); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <script>
            window.BLOG_AJAX_URL = '/admin/ajax/blog.php';
            window.BLOG_CSRF_TOKEN = '<?php echo csrfToken(); ?>';
        </script>
        <script src="/admin/assets/js/blog-editor.js"></script>
    </body>
    </html>
    <?php
    exit;
}

/* ========== LIST VIEW ========== */

$filterStatus = $_GET['status'] ?? '';
$filterCat    = $_GET['category_id'] ?? '';
$searchQuery  = $_GET['q'] ?? '';
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 20;

$filters = array_filter(['status' => $filterStatus, 'category_id' => $filterCat, 'search' => $searchQuery]);
$posts   = $blogManager->getAllPosts($filters, $page, $perPage);
$total   = $blogManager->countPosts($filters);
$totalPages = max(1, ceil($total / $perPage));

$categories = $blogManager->getAllCategories();
$allTags    = $blogManager->getAllTags();

$statusCounts = [];
foreach (BlogManager::STATUSES as $st) {
    $statusCounts[$st] = $blogManager->countPosts(['status' => $st]);
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/includes/head.php'; ?>
    <link rel="stylesheet" href="/admin/assets/css/blog-editor.css">
</head>
<body class="admin-body">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="admin-main">
        <div class="admin-content">
            <div class="admin-page-header">
                <h1>Blog</h1>
                <div class="admin-page-header__actions">
                    <a href="/admin/blog.php?action=new" class="btn btn--primary">+ New Post</a>
                </div>
            </div>

            <!-- Status Tabs -->
            <div class="blog-tabs">
                <a href="/admin/blog.php" class="blog-tab <?php echo empty($filterStatus) ? 'blog-tab--active' : ''; ?>">All <span class="blog-tab__count"><?php echo (int)array_sum($statusCounts); ?></span></a>
                <?php foreach (BlogManager::STATUSES as $st): ?>
                <a href="/admin/blog.php?status=<?php echo esc($st); ?>" class="blog-tab <?php echo $filterStatus === $st ? 'blog-tab--active' : ''; ?>">
                    <?php echo esc(BlogManager::STATUS_LABELS[$st]); ?>
                    <span class="blog-tab__count"><?php echo (int)$statusCounts[$st]; ?></span>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Search -->
            <form class="blog-filters" method="GET">
                <input type="hidden" name="status" value="<?php echo esc($filterStatus); ?>">
                <input type="text" name="q" class="blog-filters__search" placeholder="Search posts..." value="<?php echo esc($searchQuery); ?>">
                <select name="category_id" class="blog-filters__select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo (int)$cat['id']; ?>" <?php echo $filterCat === (string)$cat['id'] ? 'selected' : ''; ?>><?php echo esc($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn--primary">Filter</button>
                <?php if ($searchQuery || $filterStatus || $filterCat): ?>
                <a href="/admin/blog.php" class="btn btn--ghost">Clear</a>
                <?php endif; ?>
            </form>

            <!-- Posts Table -->
            <?php if (empty($posts)): ?>
            <div class="blog-empty"><p>No posts found.</p></div>
            <?php else: ?>
            <div class="blog-table-wrap">
                <table class="blog-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Categories</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $p): ?>
                        <?php
                            $pCats = $blogManager->getPostCategories($p['id']);
                            $catNames = array_map(fn($c) => esc($c['name']), $pCats);
                            $authorName = '--';
                            if ($p['author_id']) {
                                $au = $db->fetch("SELECT display_name, username FROM users WHERE id = ?", [(int)$p['author_id']]);
                                if ($au) $authorName = esc($au['display_name'] ?: $au['username']);
                            }
                        ?>
                        <tr>
                            <td>
                                <a href="/admin/blog.php?edit=<?php echo (int)$p['id']; ?>" class="blog-table__title">
                                    <?php if ($p['is_sticky']) echo '&#128204; '; ?>
                                    <?php echo esc($p['title']); ?>
                                </a>
                            </td>
                            <td><?php echo $authorName; ?></td>
                            <td><?php echo implode(', ', $catNames); ?></td>
                            <td><?php echo esc(substr($p['published_at'] ?? $p['created_at'], 0, 10)); ?></td>
                            <td><span class="blog-status blog-status--<?php echo esc($p['status']); ?>"><?php echo esc(BlogManager::STATUS_LABELS[$p['status']] ?? $p['status']); ?></span></td>
                            <td class="blog-table__actions">
                                <a href="/admin/blog.php?edit=<?php echo (int)$p['id']; ?>" class="btn btn--ghost btn--sm">Edit</a>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Duplicate this post?')">
                                    <input type="hidden" name="action" value="duplicate_post">
                                    <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                    <button type="submit" class="btn btn--ghost btn--sm">Dup</button>
                                </form>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this post?')">
                                    <input type="hidden" name="action" value="delete_post">
                                    <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                    <button type="submit" class="btn btn--ghost btn--sm btn--danger">Del</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="blog-pagination">
                <?php
                $qs = '';
                if ($filterStatus) $qs .= '&status=' . urlencode($filterStatus);
                if ($searchQuery) $qs .= '&q=' . urlencode($searchQuery);
                if ($filterCat) $qs .= '&category_id=' . urlencode($filterCat);
                ?>
                <?php if ($page > 1): ?><a href="?page=<?php echo $page-1 . $qs; ?>" class="blog-pagination__link">&laquo; Prev</a><?php endif; ?>
                <span class="blog-pagination__info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                <?php if ($page < $totalPages): ?><a href="?page=<?php echo $page+1 . $qs; ?>" class="blog-pagination__link">Next &raquo;</a><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
