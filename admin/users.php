<?php
/**
 * Swap Design - User Management
 *
 * Admin interface for managing CMS users: list, add,
 * edit role/status, and reset passwords.
 *
 * @package SwapDesign
 */

require_once __DIR__ . '/includes/init.php';
Auth::require();

$db            = Database::getInstance();
$pageTitle     = 'Users';
$currentSection = 'users';

$message     = '';
$messageType = '';

/* Prevent non-admin from managing users */
if (Auth::user()['role'] !== 'admin') {
    header('Location: /admin/index.php');
    exit;
}

/* Handle form submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message     = 'Security check failed.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $email    = strtolower(trim($_POST['email'] ?? ''));
            $name     = trim($_POST['name'] ?? '');
            $password = $_POST['password'] ?? '';
            $role     = in_array($_POST['role'] ?? '', ['admin', 'editor'], true) ? $_POST['role'] : 'editor';

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message     = 'Please enter a valid email address.';
                $messageType = 'error';
            } elseif (strlen($password) < 8) {
                $message     = 'Password must be at least 8 characters long.';
                $messageType = 'error';
            } elseif ($db->exists('users', 'email = ?', [$email])) {
                $message     = 'A user with that email already exists.';
                $messageType = 'error';
            } else {
                $db->insert('users', [
                    'email'         => $email,
                    'password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
                    'name'          => sanitizeString($name),
                    'role'          => $role,
                    'status'        => 'active',
                ]);
                logInfo('User created', ['email' => $email, 'role' => $role]);
                $message     = 'User created successfully.';
                $messageType = 'success';
            }
        } elseif ($action === 'update') {
            $id   = (int)($_POST['id'] ?? 0);
            $user = $db->fetch("SELECT * FROM users WHERE id = ?", [$id]);
            if (!$user) {
                $message     = 'User not found.';
                $messageType = 'error';
            } else {
                $name   = trim($_POST['name'] ?? '');
                $role   = in_array($_POST['role'] ?? '', ['admin', 'editor'], true) ? $_POST['role'] : 'editor';
                $status = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? $_POST['status'] : 'inactive';

                /* Prevent deactivating or demoting your own account */
                if ($id === (int)Auth::user()['id']) {
                    $role   = 'admin';
                    $status = 'active';
                }

                $data = [
                    'name'   => sanitizeString($name),
                    'role'   => $role,
                    'status' => $status,
                ];

                $password = $_POST['password'] ?? '';
                if ($password !== '') {
                    if (strlen($password) < 8) {
                        $message     = 'Password must be at least 8 characters long.';
                        $messageType = 'error';
                    } else {
                        $data['password_hash'] = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    }
                }

                if (!$message) {
                    $db->update('users', $data, 'id = ?', [$id]);
                    logInfo('User updated', ['id' => $id]);
                    $message     = 'User updated successfully.';
                    $messageType = 'success';
                }
            }
        }
    }
}

$users = $db->fetchAll("SELECT id, email, name, role, status, last_login_at, created_at FROM users ORDER BY created_at ASC");
$currentUserId = (int)Auth::user()['id'];
$csrfToken = csrfToken();

require __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-header__title">Users</h1>
    <div class="admin-page-header__actions">
        <button type="button" class="btn btn--primary" id="users-add-toggle">Add User</button>
    </div>
</div>

<?php if ($message): ?>
<div class="admin-flash admin-flash--<?php echo esc($messageType); ?>" role="alert">
    <?php echo esc($message); ?>
    <button class="admin-flash__close" aria-label="Dismiss">&times;</button>
</div>
<?php endif; ?>

<!-- Add User Form -->
<div class="admin-card" id="users-add-card" hidden>
    <h2 class="admin-card__title">Add New User</h2>
    <form method="POST" action="/admin/users.php" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
        <input type="hidden" name="action" value="create">
        <div class="admin-form-grid">
            <div class="admin-form-group">
                <label for="user-name">Full Name</label>
                <input type="text" id="user-name" name="name" class="admin-form-input" required>
            </div>
            <div class="admin-form-group">
                <label for="user-email">Email</label>
                <input type="email" id="user-email" name="email" class="admin-form-input" required>
            </div>
            <div class="admin-form-group">
                <label for="user-password">Password (min 8 chars)</label>
                <input type="password" id="user-password" name="password" class="admin-form-input" minlength="8" required>
            </div>
            <div class="admin-form-group">
                <label for="user-role">Role</label>
                <select id="user-role" name="role" class="admin-form-input">
                    <option value="editor">Editor</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
        </div>
        <div class="admin-form-actions">
            <button type="submit" class="btn btn--primary">Create User</button>
            <button type="button" class="btn btn--ghost" id="users-add-cancel">Cancel</button>
        </div>
    </form>
</div>

<!-- Users Table -->
<div class="admin-table-wrapper">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Last Login</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr class="users-row" data-user-id="<?php echo (int)$user['id']; ?>">
                <td>#<?php echo (int)$user['id']; ?></td>
                <td><strong><?php echo esc($user['name']); ?></strong><?php echo (int)$user['id'] === $currentUserId ? ' <span class="admin-badge admin-badge--info">You</span>' : ''; ?></td>
                <td><?php echo esc($user['email']); ?></td>
                <td><span class="admin-badge admin-badge--default"><?php echo esc($user['role']); ?></span></td>
                <td><span class="admin-badge admin-badge--<?php echo esc($user['status'] === 'active' ? 'success' : 'error'); ?>"><?php echo esc($user['status']); ?></span></td>
                <td><?php echo esc($user['last_login_at'] ?: 'Never'); ?></td>
                <td><?php echo esc(substr($user['created_at'], 0, 10)); ?></td>
                <td>
                    <button type="button" class="btn btn--small btn--secondary users-edit-toggle">Edit</button>
                </td>
            </tr>

            <!-- Edit Form (hidden) -->
            <tr class="admin-form-group" hidden>
                <td colspan="8">
                    <form method="POST" action="/admin/users.php" class="admin-form">
                        <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">
                        <div class="admin-form-grid">
                            <div class="admin-form-group">
                                <label>Name</label>
                                <input type="text" name="name" class="admin-form-input" value="<?php echo esc($user['name']); ?>" required>
                            </div>
                            <div class="admin-form-group">
                                <label>Role</label>
                                <select name="role" class="admin-form-input" <?php echo (int)$user['id'] === $currentUserId ? 'disabled' : ''; ?>>
                                    <option value="editor" <?php echo $user['role'] === 'editor' ? 'selected' : ''; ?>>Editor</option>
                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                            <div class="admin-form-group">
                                <label>Status</label>
                                <select name="status" class="admin-form-input" <?php echo (int)$user['id'] === $currentUserId ? 'disabled' : ''; ?>>
                                    <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="admin-form-group">
                                <label>New Password (leave blank to keep)</label>
                                <input type="password" name="password" class="admin-form-input" minlength="8" placeholder="••••••••">
                            </div>
                        </div>
                        <div class="admin-form-actions">
                            <button type="submit" class="btn btn--primary">Save Changes</button>
                            <button type="button" class="btn btn--ghost users-edit-cancel">Cancel</button>
                        </div>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
(function () {
    var addCard = document.getElementById('users-add-card');
    var addToggle = document.getElementById('users-add-toggle');
    var addCancel = document.getElementById('users-add-cancel');

    if (addToggle) {
        addToggle.addEventListener('click', function () {
            addCard.hidden = !addCard.hidden;
        });
    }
    if (addCancel) {
        addCancel.addEventListener('click', function () {
            addCard.hidden = true;
        });
    }

    document.querySelectorAll('.users-edit-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var row = this.closest('tr');
            var editRow = row.nextElementSibling;
            if (editRow && editRow.classList.contains('admin-form-group')) {
                editRow.hidden = !editRow.hidden;
            }
        });
    });

    document.querySelectorAll('.users-edit-cancel').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var editRow = this.closest('tr');
            editRow.hidden = true;
        });
    });

    document.querySelectorAll('.admin-flash__close').forEach(function (btn) {
        btn.addEventListener('click', function () {
            this.parentElement.remove();
        });
    });
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
