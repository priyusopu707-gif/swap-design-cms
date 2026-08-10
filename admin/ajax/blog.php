<?php
/**
 * Swap Design - Blog AJAX Handler
 *
 * save, delete, toggle status, save_revision, get_revisions,
 * restore_revision, duplicate.
 */

define('SWAP_ROOT', true);
define('IS_ADMIN', true);
define('IS_AJAX', true);

require_once __DIR__ . '/../../includes/config/site.php';
require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/config/environment.php';
require_once __DIR__ . '/../../includes/functions/logger.php';
require_once __DIR__ . '/../../includes/config/error-handler.php';
require_once __DIR__ . '/../../includes/functions/helpers.php';
require_once __DIR__ . '/../../includes/functions/sanitize.php';
require_once __DIR__ . '/../../includes/functions/security.php';
require_once __DIR__ . '/../../includes/Session.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/auth/Auth.php';
require_once __DIR__ . '/../../includes/content/BlogManager.php';

Session::start();

if (empty($_SESSION['user_id'])) {
    respond(false, 'Unauthorized', 401);
}

$trustedReferer = (!empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], SITE_URL) === 0);
if (!$trustedReferer) {
    respond(false, 'Invalid referer', 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Method not allowed', 405);
}

if (empty($_POST['action'])) {
    respond(false, 'Missing action', 400);
}

/* CSRF protection */
$token = $_POST['token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verifyCsrfToken($token)) {
    respond(false, 'Invalid security token', 403);
}

$action  = $_POST['action'];
$manager = new BlogManager();

switch ($action) {
    case 'save':
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
        $title    = sanitizeString($_POST['title'] ?? '');
        $slug     = sanitizeString($_POST['slug'] ?? '');
        $desc     = sanitizeString($_POST['short_description'] ?? '');
        $content  = $_POST['content'] ?? '';
        $image    = sanitizeString($_POST['featured_image'] ?? '');
        $gallery  = json_decode($_POST['gallery'] ?? '[]', true) ?: [];
        $authorId = !empty($_POST['author_id']) ? (int)$_POST['author_id'] : null;
        $pubAt    = $_POST['published_at'] ?? '';
        $status   = $_POST['status'] ?? 'draft';
        $featured = !empty($_POST['is_featured']);
        $sticky   = !empty($_POST['is_sticky']);
        $seoTitle = sanitizeString($_POST['seo_title'] ?? '');
        $metaDesc = sanitizeString($_POST['meta_description'] ?? '');
        $focusKw  = sanitizeString($_POST['focus_keyword'] ?? '');
        $canonical= sanitizeString($_POST['canonical_url'] ?? '');
        $ogImage  = sanitizeString($_POST['og_image'] ?? '');
        $twCard   = sanitizeString($_POST['twitter_card'] ?? 'summary_large_image');

        $data = compact('title', 'slug', 'desc', 'content', 'image', 'gallery', 'status', 'featured', 'sticky', 'seoTitle', 'metaDesc', 'focusKw', 'canonical', 'ogImage', 'twCard');
        $data['short_description'] = $desc;
        $data['featured_image']   = $image;
        $data['is_featured']      = $featured;
        $data['is_sticky']        = $sticky;
        $data['seo_title']        = $seoTitle;
        $data['meta_description'] = $metaDesc;
        $data['focus_keyword']    = $focusKw;
        $data['canonical_url']    = $canonical;
        $data['og_image']         = $ogImage;
        $data['twitter_card']     = $twCard;
        $data['author_id']        = $authorId;

        if ($pubAt) {
            $data['published_at'] = date('Y-m-d H:i:s', strtotime($pubAt));
        }

        if ($id) {
            $manager->updatePost($id, $data);
        } else {
            $id = $manager->createPost($data);
        }

        /* Sync taxonomy */
        if (isset($_POST['categories'])) {
            $catIds = array_map('intval', explode(',', $_POST['categories']));
            $manager->syncPostCategories($id, $catIds);
        }
        if (isset($_POST['tags'])) {
            $tagNames = array_map('trim', explode(',', $_POST['tags']));
            $manager->syncPostTags($id, array_filter($tagNames));
        }

        /* Sync relationships */
        if (isset($_POST['related_services'])) {
            $relSvcIds = array_filter(array_map('intval', explode(',', $_POST['related_services'])));
            $manager->syncRelationships($id, 'service', $relSvcIds);
        }
        if (isset($_POST['related_portfolio'])) {
            $relPfIds = array_filter(array_map('intval', explode(',', $_POST['related_portfolio'])));
            $manager->syncRelationships($id, 'portfolio', $relPfIds);
        }
        if (isset($_POST['related_posts'])) {
            $relPostIds = array_filter(array_map('intval', explode(',', $_POST['related_posts'])));
            $manager->syncRelationships($id, 'post', $relPostIds);
        }

        /* Fetch updated post for slug */
        $post = $manager->getPostById($id);
        respond(true, 'Saved', 200, ['id' => $id, 'slug' => $post['slug'] ?? '']);

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if ($id < 1) respond(false, 'Missing id');
        $manager->deletePost($id);
        respond(true, 'Deleted');

    case 'status':
        $id     = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if ($id < 1) respond(false, 'Missing id');
        $manager->setStatus($id, $status);
        respond(true, 'Status updated');

    case 'save_revision':
        $postId = (int)($_POST['id'] ?? 0);
        $note   = sanitizeString($_POST['note'] ?? '');
        if ($postId < 1) respond(false, 'Missing id');
        $revId = $manager->saveRevision($postId, $_SESSION['user_id'] ?? null, $note);
        respond(true, 'Revision saved', 200, ['revision_id' => $revId]);

    case 'get_revisions':
        $postId = (int)($_POST['id'] ?? 0);
        if ($postId < 1) respond(false, 'Missing id');
        $revs = $manager->getRevisions($postId);
        respond(true, '', 200, ['revisions' => $revs]);

    case 'restore_revision':
        $revId = (int)($_POST['revision_id'] ?? 0);
        if ($revId < 1) respond(false, 'Missing revision_id');
        $ok = $manager->restoreRevision($revId);
        respond($ok, $ok ? 'Restored' : 'Revision not found');

    case 'duplicate':
        $id = (int)($_POST['id'] ?? 0);
        if ($id < 1) respond(false, 'Missing id');
        $newId = $manager->duplicatePost($id);
        respond(true, 'Duplicated', 200, ['id' => $newId]);

    case 'add_category':
        $name = sanitizeString($_POST['name'] ?? '');
        if (empty($name)) respond(false, 'Name required');
        $catId = $manager->createCategory(['name' => $name]);
        $cat = $manager->getCategoryById($catId);
        respond(true, 'Created', 200, ['category' => $cat]);

    case 'add_tag':
        $name = sanitizeString($_POST['name'] ?? '');
        if (empty($name)) respond(false, 'Name required');
        $tagId = $manager->createTag(['name' => $name]);
        $tag = $manager->getTagById($tagId);
        respond(true, 'Created', 200, ['tag' => $tag]);

    default:
        respond(false, 'Unknown action');
}

function respond(bool $ok, string $message = '', int $httpCode = 200, array $extra = []): void
{
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    $resp = array_merge(['ok' => $ok, 'message' => $message], $extra);
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}
