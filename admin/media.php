<?php
/**
 * Swap Design - Media Library Admin Page
 *
 * Upload, browse, search, and manage media files.
 * Supports drag-and-drop upload with folder organization.
 *
 * @package SwapDesign
 */

require_once __DIR__ . '/includes/init.php';
Auth::require();

$pageTitle      = 'Media Library';
$currentSection = 'media';

$mediaLibrary   = new MediaLibrary();
$settings       = new SettingsManager();
$message        = '';
$messageType    = '';

$search   = $_GET['search'] ?? '';
$folderId = isset($_GET['folder_id']) ? (int)$_GET['folder_id'] : 0;
$mimeType = $_GET['mime'] ?? '';
$page     = max(1, (int)($_GET['p'] ?? 1));
$perPage  = 20;

/* Handle uploads (API-style POST — JS-driven) */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message     = 'Security check failed.';
        $messageType = 'error';
    } else {
        $postAction = $_POST['action'] ?? '';

        switch ($postAction) {
            case 'upload':
                if (!empty($_FILES['files'])) {
                    $uploaded = 0;
                    $errors   = [];
                    $fileCount = count($_FILES['files']['name']);

                    for ($i = 0; $i < $fileCount; $i++) {
                        $file = [
                            'name'     => $_FILES['files']['name'][$i],
                            'type'     => $_FILES['files']['type'][$i],
                            'tmp_name' => $_FILES['files']['tmp_name'][$i],
                            'error'    => $_FILES['files']['error'][$i],
                            'size'     => $_FILES['files']['size'][$i],
                        ];

                        try {
                            $mediaLibrary->upload($file, [
                                'alt_text'  => sanitizeString($_POST['alt'] ?? ''),
                                'folder_id' => $folderId,
                            ]);
                            $uploaded++;
                        } catch (\Exception $e) {
                            $errors[] = $file['name'] . ': ' . $e->getMessage();
                        }
                    }

                    $message = "Uploaded $uploaded of $fileCount files.";
                    $messageType = $uploaded > 0 ? 'success' : 'error';
                    if (!empty($errors)) {
                        $message .= ' Errors: ' . implode('; ', $errors);
                        $messageType = 'warning';
                    }
                }
                break;

            case 'delete':
                $mediaId = (int)($_POST['media_id'] ?? 0);
                if ($mediaId > 0) {
                    $mediaLibrary->trash($mediaId);
                    $message     = 'File moved to trash.';
                    $messageType = 'success';
                }
                break;

            case 'update_meta':
                $mediaId = (int)($_POST['media_id'] ?? 0);
                if ($mediaId > 0) {
                    $mediaLibrary->updateMeta($mediaId, [
                        'alt_text' => sanitizeString($_POST['alt'] ?? ''),
                        'title'    => sanitizeString($_POST['title'] ?? ''),
                        'caption'  => sanitizeString($_POST['caption'] ?? ''),
                    ]);
                    $message     = 'Media metadata updated.';
                    $messageType = 'success';
                }
                break;

            case 'create_folder':
                $folderName = sanitizeString($_POST['folder_name'] ?? '');
                if ($folderName) {
                    $mediaLibrary->createFolder($folderName, $folderId);
                    $message     = 'Folder created.';
                    $messageType = 'success';
                }
                break;

            case 'delete_folder':
                $delFolderId = (int)($_POST['folder_id'] ?? 0);
                if ($delFolderId > 0) {
                    $mediaLibrary->deleteFolder($delFolderId);
                    $message     = 'Folder deleted.';
                    $messageType = 'success';
                }
                break;
        }
    }
}

/* Load media */
$mediaItems = $mediaLibrary->getMedia([
    'search'    => $search,
    'folder_id' => $folderId,
    'mime_type' => $mimeType,
    'limit'     => $perPage,
    'offset'    => ($page - 1) * $perPage,
]);
$totalMedia  = $mediaLibrary->countMedia([
    'search'    => $search,
    'folder_id' => $folderId,
    'mime_type' => $mimeType,
]);
$folders = $mediaLibrary->getFolders();

$csrfToken  = csrfToken();

/**
 * Build a file URL from a media record.
 */
function mediaFileUrl(array $item): string
{
    $hash     = $item['file_hash'];
    $datePart = substr($hash, 0, 4) . '/' . substr($hash, 4, 2);
    $ext      = pathinfo($item['filename'], PATHINFO_EXTENSION);
    return '/uploads/originals/' . $datePart . '/' . $hash . '.' . $ext;
}

function mediaThumbUrl(array $item): string
{
    $hash     = $item['file_hash'];
    $datePart = substr($hash, 0, 4) . '/' . substr($hash, 4, 2);
    $ext      = pathinfo($item['filename'], PATHINFO_EXTENSION);
    return '/uploads/generated/admin/' . $datePart . '/' . $hash . '.' . $ext;
}

require __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-header__title">Media Library</h1>
    <div class="admin-page-header__actions">
        <button type="button" class="admin-btn admin-btn--secondary" onclick="openFolderModal()">New Folder</button>
        <button type="button" class="admin-btn admin-btn--primary" onclick="document.getElementById('file-upload-input').click()">Upload Files</button>
    </div>
</div>

<?php if ($message): ?>
    <div class="admin-flash admin-flash--<?php echo $messageType; ?>" role="alert">
        <?php echo esc($message); ?>
        <button class="admin-flash__close" aria-label="Dismiss">&times;</button>
    </div>
<?php endif; ?>

<!-- Drag & Drop Upload Zone -->
<div class="admin-card u-mb-md">
    <div class="admin-card__body">
        <div class="admin-dropzone" id="upload-dropzone">
            <span class="admin-dropzone__icon">&#x1F4C1;</span>
            <p class="admin-dropzone__text">
                <strong>Click to upload</strong> or drag and drop files here
            </p>
            <p class="admin-dropzone__hint">SVG, PNG, JPG, GIF, WebP &mdash; up to 20MB each</p>
        </div>
        <form id="upload-form" method="POST" action="/admin/media.php" enctype="multipart/form-data" style="display:none">
            <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
            <input type="hidden" name="action" value="upload">
            <input type="hidden" name="folder_id" value="<?php echo $folderId; ?>">
            <input type="file" id="file-upload-input" name="files[]" multiple accept="image/*" onchange="startUpload(this.files)">
        </form>
    </div>
</div>

<!-- Folders -->
<?php if (!empty($folders)): ?>
<div class="admin-card u-mb-md">
    <div class="admin-card__header">
        <h2 class="admin-card__title">Folders</h2>
    </div>
    <div class="admin-card__body">
        <div class="admin-folder-list">
            <a href="/admin/media.php" class="admin-folder-item <?php echo $folderId === 0 ? 'admin-folder-item--active' : ''; ?>">
                All Files (<?php echo $totalMedia; ?>)
            </a>
            <?php foreach ($folders as $folder): ?>
            <div class="admin-folder-item-wrapper">
                <a href="/admin/media.php?folder_id=<?php echo $folder['id']; ?>" class="admin-folder-item <?php echo $folderId === $folder['id'] ? 'admin-folder-item--active' : ''; ?>">
                    <?php echo esc($folder['name']); ?>
                </a>
                <form method="POST" action="/admin/media.php" style="display:inline" data-confirm="Delete this folder? (Files will not be deleted)">
                    <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
                    <input type="hidden" name="action" value="delete_folder">
                    <input type="hidden" name="folder_id" value="<?php echo $folder['id']; ?>">
                    <button type="submit" class="admin-btn admin-btn--sm admin-btn--danger" style="margin-left:0.25rem">&times;</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Search & Filter Toolbar -->
<div class="admin-media-toolbar">
    <form method="GET" action="/admin/media.php" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:center;flex:1">
        <input type="hidden" name="folder_id" value="<?php echo $folderId; ?>">
        <input type="text" name="search" value="<?php echo esc($search); ?>" class="admin-form-input" placeholder="Search files..." style="max-width:280px" aria-label="Search files">
        <select name="mime" class="admin-form-input" style="max-width:160px" aria-label="Filter by type">
            <option value="">All Media</option>
            <option value="image" <?php echo $mimeType === 'image' ? 'selected' : ''; ?>>Images</option>
            <option value="application" <?php echo $mimeType === 'application' ? 'selected' : ''; ?>>Documents</option>
        </select>
        <button type="submit" class="admin-btn admin-btn--primary admin-btn--sm">Filter</button>
        <?php if ($search || $mimeType): ?>
            <a href="/admin/media.php?folder_id=<?php echo $folderId; ?>" class="admin-btn admin-btn--secondary admin-btn--sm">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Media Grid -->
<?php if (empty($mediaItems)): ?>
    <div class="admin-card">
        <div class="admin-card__body">
            <div class="admin-empty">
                <p>No media files found. Upload some files to get started.</p>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="admin-media-grid">
        <?php foreach ($mediaItems as $item):
            $fileUrl    = mediaFileUrl($item);
            $thumbUrl   = mediaThumbUrl($item);
            $fileName   = $item['original_name'] ?? $item['filename'];
            $altText    = $item['alt_text'] ?? $fileName;
        ?>
        <div class="admin-media-card" data-id="<?php echo $item['id']; ?>">
            <?php if (str_starts_with($item['mime_type'] ?? '', 'image/')): ?>
                <img src="<?php echo esc($thumbUrl); ?>"
                     alt="<?php echo esc($altText); ?>"
                     class="admin-media-card__image"
                     loading="lazy">
            <?php else: ?>
                <div class="admin-media-card__image" style="display:flex;align-items:center;justify-content:center;background:var(--admin-background);font-size:2rem;color:var(--admin-text-muted)">
                    &#x1F4C4;
                </div>
            <?php endif; ?>
            <div class="admin-media-card__info">
                <p class="admin-media-card__name" title="<?php echo esc($fileName); ?>"><?php echo esc($fileName); ?></p>
                <span class="admin-media-card__meta">
                    <span><?php echo esc($item['width'] ?? ''); ?><?php echo $item['width'] ? '&times;' . esc($item['height'] ?? '') : ''; ?></span>
                    <span><?php echo esc(sizeFormat($item['file_size'] ?? 0)); ?></span>
                </span>
            </div>
            <div class="admin-media-card__actions">
                <button type="button" class="admin-btn admin-btn--sm admin-btn--secondary"
                    onclick="copyMediaUrl('<?php echo escJs($fileUrl); ?>')">Copy URL</button>
                <button type="button" class="admin-btn admin-btn--sm admin-btn--secondary"
                    onclick="editMediaMeta(<?php echo $item['id']; ?>, '<?php echo escJs($item['alt_text'] ?? ''); ?>', '<?php echo escJs($item['title'] ?? ''); ?>', '<?php echo escJs($item['caption'] ?? ''); ?>')">Edit</button>
                <form method="POST" action="/admin/media.php" style="display:inline" data-confirm="Move this file to trash?">
                    <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="media_id" value="<?php echo $item['id']; ?>">
                    <button type="submit" class="admin-btn admin-btn--sm admin-btn--danger">Delete</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php
    /* Pagination */
    $totalPages = ceil($totalMedia / $perPage);
    if ($totalPages > 1):
        $queryParams = [];
        if ($search) $queryParams['search'] = $search;
        if ($folderId) $queryParams['folder_id'] = $folderId;
        if ($mimeType) $queryParams['mime'] = $mimeType;
    ?>
    <nav class="admin-pagination" aria-label="Media pagination" style="margin-top:1.5rem">
        <?php if ($page > 1): ?>
            <a href="/admin/media.php?<?php echo http_build_query(array_merge($queryParams, ['p' => $page - 1])); ?>" class="admin-btn admin-btn--sm admin-btn--secondary">Previous</a>
        <?php endif; ?>
        <span class="admin-pagination__info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
        <?php if ($page < $totalPages): ?>
            <a href="/admin/media.php?<?php echo http_build_query(array_merge($queryParams, ['p' => $page + 1])); ?>" class="admin-btn admin-btn--sm admin-btn--secondary">Next</a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>
<?php endif; ?>

<!-- Folder Modal -->
<div class="admin-modal" id="folder-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="folder-modal-title" tabindex="-1">
    <div class="admin-modal__backdrop" onclick="closeFolderModal()"></div>
    <div class="admin-modal__content">
        <div class="admin-modal__header">
            <h3 id="folder-modal-title">New Folder</h3>
            <button class="admin-modal__close" onclick="closeFolderModal()" aria-label="Close">&times;</button>
        </div>
        <form method="POST" action="/admin/media.php">
            <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
            <input type="hidden" name="action" value="create_folder">
            <input type="hidden" name="folder_id" value="<?php echo $folderId; ?>">

            <div class="admin-form-group">
                <label class="admin-form-label">Folder Name <span class="admin-required">*</span></label>
                <input type="text" name="folder_name" class="admin-form-input" required>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="admin-btn admin-btn--primary">Create Folder</button>
                <button type="button" class="admin-btn admin-btn--secondary" onclick="closeFolderModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Media Meta Modal -->
<div class="admin-modal" id="meta-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="meta-modal-title" tabindex="-1">
    <div class="admin-modal__backdrop" onclick="closeMetaModal()"></div>
    <div class="admin-modal__content">
        <div class="admin-modal__header">
            <h3 id="meta-modal-title">Edit File Info</h3>
            <button class="admin-modal__close" onclick="closeMetaModal()" aria-label="Close">&times;</button>
        </div>
        <form method="POST" action="/admin/media.php">
            <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
            <input type="hidden" name="action" value="update_meta">
            <input type="hidden" name="media_id" id="meta-media-id">

            <div class="admin-form-group">
                <label class="admin-form-label">Title</label>
                <input type="text" name="title" id="meta-title" class="admin-form-input">
            </div>

            <div class="admin-form-group">
                <label class="admin-form-label">Alt Text</label>
                <input type="text" name="alt" id="meta-alt" class="admin-form-input">
            </div>

            <div class="admin-form-group">
                <label class="admin-form-label">Caption</label>
                <input type="text" name="caption" id="meta-caption" class="admin-form-input">
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="admin-btn admin-btn--primary">Save</button>
                <button type="button" class="admin-btn admin-btn--secondary" onclick="closeMetaModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    var dropzone = document.getElementById('upload-dropzone');

    /* Drag-and-drop */
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function(evt) {
        dropzone.addEventListener(evt, function(e) {
            e.preventDefault();
            e.stopPropagation();
        });
    });

    ['dragenter', 'dragover'].forEach(function(evt) {
        dropzone.addEventListener(evt, function() {
            dropzone.classList.add('admin-dropzone--active');
        });
    });

    ['dragleave', 'drop'].forEach(function(evt) {
        dropzone.addEventListener(evt, function() {
            dropzone.classList.remove('admin-dropzone--active');
        });
    });

    dropzone.addEventListener('drop', function(e) {
        startUpload(e.dataTransfer.files);
    });

    dropzone.addEventListener('click', function() {
        document.getElementById('file-upload-input').click();
    });

    /* Upload function */
    window.startUpload = function(files) {
        var form = document.getElementById('upload-form');
        var input = document.getElementById('file-upload-input');
        var dt = new DataTransfer();
        for (var i = 0; i < files.length; i++) {
            dt.items.add(files[i]);
        }
        input.files = dt.files;
        form.submit();
    };

    /* Folder modal */
    var _folderTrap = null;
    window.openFolderModal = function() {
        var modal = document.getElementById('folder-modal');
        modal.setAttribute('aria-hidden', 'false');
        _folderTrap = adminModalTrap(modal, document.activeElement);
        _folderTrap.activate();
    };

    window.closeFolderModal = function() {
        var modal = document.getElementById('folder-modal');
        modal.setAttribute('aria-hidden', 'true');
        if (_folderTrap) {
            _folderTrap.deactivate();
            _folderTrap = null;
        }
    };

    /* Media meta modal */
    var _metaTrap = null;
    window.editMediaMeta = function(id, alt, title, caption) {
        document.getElementById('meta-media-id').value = id;
        document.getElementById('meta-alt').value = alt;
        document.getElementById('meta-title').value = title;
        document.getElementById('meta-caption').value = caption;
        var modal = document.getElementById('meta-modal');
        modal.setAttribute('aria-hidden', 'false');
        _metaTrap = adminModalTrap(modal, document.activeElement);
        _metaTrap.activate();
    };

    window.closeMetaModal = function() {
        var modal = document.getElementById('meta-modal');
        modal.setAttribute('aria-hidden', 'true');
        if (_metaTrap) {
            _metaTrap.deactivate();
            _metaTrap = null;
        }
    };

    /* Copy URL */
    window.copyMediaUrl = function(url) {
        navigator.clipboard.writeText(url).then(function() {
            /* Brief feedback — just a silent copy */
        }).catch(function() {
            prompt('Copy this URL:', url);
        });
    };

    /* Escape to close modals */
    document.addEventListener('keydown', function(e) {
        if (e.key !== 'Escape') return;
        var folderModal = document.getElementById('folder-modal');
        var metaModal = document.getElementById('meta-modal');
        if (folderModal && folderModal.getAttribute('aria-hidden') === 'false') {
            closeFolderModal();
        }
        if (metaModal && metaModal.getAttribute('aria-hidden') === 'false') {
            closeMetaModal();
        }
    });
})();
</script>

<?php require __DIR__ . '/includes/footer.php';
