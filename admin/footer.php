<?php
/**
 * Swap Design - Footer Manager Admin Page
 *
 * Manage footer links grouped by column, copyright text, and footer settings.
 *
 * @package SwapDesign
 */

require_once __DIR__ . '/includes/init.php';
Auth::require();

$pageTitle      = 'Footer';
$currentSection = 'footer';

$db       = Database::getInstance();
$settings = new SettingsManager();
$message  = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message     = 'Security check failed.';
        $messageType = 'error';
    } else {
        $postAction = $_POST['action'] ?? '';

        switch ($postAction) {
            case 'save_link':
                $linkId = (int)($_POST['link_id'] ?? 0);
                $data = [
                    'label'      => sanitizeString($_POST['label'] ?? ''),
                    'url'        => sanitizeString($_POST['url'] ?? '#'),
                    'group_name' => sanitizeString($_POST['group_name'] ?? ''),
                    'sort_order' => (int)($_POST['sort_order'] ?? 0),
                ];

                if ($linkId > 0) {
                    $db->update('footer_links', $data, 'id = ?', [$linkId]);
                    $message     = 'Link updated.';
                } else {
                    $db->insert('footer_links', $data);
                    $message     = 'Link created.';
                }
                $messageType = 'success';
                break;

            case 'delete_link':
                $linkId = (int)($_POST['link_id'] ?? 0);
                if ($linkId > 0) {
                    $db->delete('footer_links', 'id = ?', [$linkId]);
                    $message     = 'Link deleted.';
                    $messageType = 'success';
                }
                break;

            case 'save_settings':
                $settings->set('footer.copyright_text', sanitizeString($_POST['copyright_text'] ?? ''));
                $message     = 'Footer settings saved.';
                $messageType = 'success';
                break;

            case 'reorder_links':
                $order = json_decode($_POST['order'] ?? '[]', true);
                if (is_array($order)) {
                    foreach ($order as $pos => $linkId) {
                        $db->update('footer_links', ['sort_order' => $pos], 'id = ?', [(int)$linkId]);
                    }
                    $message = 'Order updated.';
                    $messageType = 'success';
                }
                break;
        }
    }
}

/* Load footer links grouped */
$links = $db->fetchAll('SELECT * FROM footer_links ORDER BY group_name, sort_order ASC');
$grouped = [];
foreach ($links as $link) {
    $grouped[$link['group_name']][] = $link;
}

$copyrightText = $settings->get('footer.copyright_text', '&copy; ' . date('Y') . ' Swap Design. All rights reserved.');

$csrfToken = csrfToken();
require __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-header__title">Footer Manager</h1>
    <div class="admin-page-header__actions">
        <button type="button" class="admin-btn admin-btn--primary" onclick="openLinkForm()">Add Link</button>
    </div>
</div>

<?php if ($message): ?>
    <div class="admin-flash admin-flash--<?php echo $messageType; ?>" role="alert">
        <?php echo esc($message); ?>
        <button class="admin-flash__close" aria-label="Dismiss">&times;</button>
    </div>
<?php endif; ?>

<!-- Add/Edit Link Modal -->
<div class="admin-modal" id="link-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="link-modal-title" tabindex="-1">
    <div class="admin-modal__backdrop" onclick="closeLinkForm()"></div>
    <div class="admin-modal__content">
        <div class="admin-modal__header">
            <h3 id="link-modal-title">Add Footer Link</h3>
            <button class="admin-modal__close" onclick="closeLinkForm()" aria-label="Close">&times;</button>
        </div>
        <form method="POST" action="/admin/footer.php">
            <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
            <input type="hidden" name="action" value="save_link">
            <input type="hidden" name="link_id" value="">

            <div class="admin-form-group">
                <label class="admin-form-label">Label <span class="admin-required">*</span></label>
                <input type="text" name="label" class="admin-form-input" required>
            </div>

            <div class="admin-form-group">
                <label class="admin-form-label">URL <span class="admin-required">*</span></label>
                <input type="text" name="url" class="admin-form-input" required>
            </div>

            <div class="admin-form-group">
                <label class="admin-form-label">Group</label>
                <input type="text" name="group_name" class="admin-form-input" placeholder="e.g., Services, Company, Legal">
            </div>

            <div class="admin-form-group">
                <label class="admin-form-label">Sort Order</label>
                <input type="number" name="sort_order" value="0" class="admin-form-input" min="0">
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="admin-btn admin-btn--primary">Save Link</button>
                <button type="button" class="admin-btn admin-btn--secondary" onclick="closeLinkForm()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Footer Settings -->
<div class="admin-card u-mb-md">
    <div class="admin-card__header">
        <h2 class="admin-card__title">Footer Settings</h2>
    </div>
    <div class="admin-card__body">
        <form method="POST" action="/admin/footer.php">
            <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
            <input type="hidden" name="action" value="save_settings">

            <div class="admin-form-group">
                <label class="admin-form-label">Copyright Text</label>
                <input type="text" name="copyright_text" value="<?php echo esc($copyrightText); ?>" class="admin-form-input">
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="admin-btn admin-btn--primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>

<!-- Footer Links by Group -->
<?php if (empty($grouped)): ?>
    <div class="admin-card">
        <div class="admin-card__body">
            <div class="admin-empty">
                <p>No footer links yet. <button type="button" class="admin-btn admin-btn--sm admin-btn--primary" onclick="openLinkForm()">Add your first link</button></p>
            </div>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($grouped as $groupName => $groupLinks): ?>
    <div class="admin-card u-mb-md">
        <div class="admin-card__header">
            <h2 class="admin-card__title"><?php echo esc($groupName ?: 'Ungrouped'); ?></h2>
            <span class="admin-card__count"><?php echo count($groupLinks); ?> links</span>
        </div>
        <div class="admin-card__body">
            <div class="admin-footer-links">
                <?php foreach ($groupLinks as $link): ?>
                <div class="admin-footer-link-item">
                    <span class="admin-footer-link-label">
                        <a href="<?php echo esc($link['url']); ?>" target="_blank" rel="noopener"><?php echo esc($link['label']); ?></a>
                    </span>
                    <span class="admin-footer-link-url"><?php echo esc($link['url']); ?></span>
                    <div class="admin-footer-link-actions">
                        <button type="button" class="admin-btn admin-btn--sm admin-btn--secondary"
                            onclick="editLink(<?php echo $link['id']; ?>, '<?php echo escJs($link['label']); ?>', '<?php echo escJs($link['url']); ?>', '<?php echo escJs($link['group_name']); ?>', <?php echo $link['sort_order']; ?>)">
                            Edit
                        </button>
                        <form method="POST" action="/admin/footer.php" style="display:inline" data-confirm="Delete this link?">
                            <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
                            <input type="hidden" name="action" value="delete_link">
                            <input type="hidden" name="link_id" value="<?php echo $link['id']; ?>">
                            <button type="submit" class="admin-btn admin-btn--sm admin-btn--danger">Delete</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
(function() {
    var modal = document.getElementById('link-modal');
    var form = modal.querySelector('form');
    var _linkTrap = null;

    window.openLinkForm = function() {
        form.querySelector('[name="link_id"]').value = '';
        form.querySelector('[name="label"]').value = '';
        form.querySelector('[name="url"]').value = '';
        form.querySelector('[name="group_name"]').value = '';
        form.querySelector('[name="sort_order"]').value = '0';
        document.getElementById('link-modal-title').textContent = 'Add Footer Link';
        modal.setAttribute('aria-hidden', 'false');
        _linkTrap = adminModalTrap(modal, document.activeElement);
        _linkTrap.activate();
    };

    window.editLink = function(id, label, url, group, order) {
        form.querySelector('[name="link_id"]').value = id;
        form.querySelector('[name="label"]').value = label;
        form.querySelector('[name="url"]').value = url;
        form.querySelector('[name="group_name"]').value = group;
        form.querySelector('[name="sort_order"]').value = order;
        document.getElementById('link-modal-title').textContent = 'Edit Footer Link';
        modal.setAttribute('aria-hidden', 'false');
        _linkTrap = adminModalTrap(modal, document.activeElement);
        _linkTrap.activate();
    };

    window.closeLinkForm = function() {
        modal.setAttribute('aria-hidden', 'true');
        if (_linkTrap) {
            _linkTrap.deactivate();
            _linkTrap = null;
        }
    };

    /* Close modal on Escape */
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
            closeLinkForm();
        }
    });

    /* Close modal on backdrop click */
    modal.querySelector('.admin-modal__backdrop').addEventListener('click', function() {
        closeLinkForm();
    });
})();
</script>

<?php require __DIR__ . '/includes/footer.php';
