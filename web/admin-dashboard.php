<?php
// admin-dashboard.php - Admin Control Panel
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Material.php';

AdminController::checkAdminAuth();

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_user_id'])) {
        AdminController::toggleUserStatus($_POST['toggle_user_id'], $_POST['current_status']);
    } elseif (isset($_POST['delete_material_id'])) {
        AdminController::deleteUpload($_POST['delete_material_id']);
        $success = "Revision material deleted.";
    } elseif (isset($_POST['delete_user_id'])) {
        AdminController::deleteUser($_POST['delete_user_id']);
        $success = "User account and all materials deleted.";
    } elseif (isset($_POST['add_user'])) {
        $res = AdminController::addUser($_POST['email'], $_POST['password'], $_POST['role']);
        if ($res === true) {
            $success = "New account registered successfully.";
        } else {
            $error = $res;
        }
    }
}

$users = User::getAll();
$materials = Material::getAllAdmin();

require_once __DIR__ . '/views/layout/header.php';
require_once __DIR__ . '/views/layout/navbar.php';
?>
<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2>User & Material Administration</h2>
        <div style="display: flex; gap: 1rem;">
            <a href="/admin-dashboard.php" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.9rem;"><i class="fa-solid fa-users"></i> Users</a>
            <a href="/admin-exams.php" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.9rem;"><i class="fa-solid fa-book"></i> Library</a>
            <a href="/admin-logs.php" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.9rem;"><i class="fa-solid fa-list-check"></i> Logs</a>
        </div>
    </div>

    <?php if ($success): ?>
        <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid var(--success); color: #a7f3d0; padding: 0.75rem; border-radius: 8px; margin-bottom: 2rem;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid var(--error); color: #fca5a5; padding: 0.75rem; border-radius: 8px; margin-bottom: 2rem;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="grid" style="grid-template-columns: 1.2fr 1fr; gap: 2rem; margin-top: 1rem; margin-bottom: 2rem;">
        <div class="card">
            <h3 style="margin-bottom: 1.5rem;"><i class="fa-solid fa-users-gear"></i> System Accounts</h3>
            <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                <table>
                    <thead>
                        <tr><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td><?= ucfirst($u['role']) ?></td>
                                <td><span style="color: <?= $u['status'] === 'active' ? 'var(--success)' : 'var(--error)' ?>;"><?= ucfirst($u['status']) ?></span></td>
                                <td>
                                    <?php if ($u['role'] !== 'admin'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="toggle_user_id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="current_status" value="<?= $u['status'] ?>">
                                            <button type="submit" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                                <?= $u['status'] === 'active' ? 'Block' : 'Unblock' ?>
                                            </button>
                                        </form>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete user?');">
                                            <input type="hidden" name="delete_user_id" value="<?= $u['id'] ?>">
                                            <button type="submit" class="btn" style="background: rgba(239, 68, 68, 0.1); color: var(--error); border: 1px solid rgba(239, 68, 68, 0.2); padding: 0.25rem 0.5rem; font-size: 0.75rem; border-radius: 4px; margin-left: 0.25rem;">
                                                Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-bottom: 1.5rem;"><i class="fa-solid fa-user-plus"></i> Create Account</h3>
            <form method="POST">
                <input type="hidden" name="add_user" value="1">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="user@domain.com" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label for="role">User Role</label>
                    <select id="role" name="role" class="form-control" required>
                        <option value="user" selected>Standard User</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;"><i class="fa-solid fa-user-check"></i> Register Account</button>
            </form>
        </div>
    </div>

    <div class="card">
        <h3 style="margin-bottom: 1.5rem;"><i class="fa-solid fa-file-shield"></i> Material Moderation Panel</h3>
        <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
            <table>
                <thead>
                    <tr><th>Exam</th><th>Format</th><th>Creator</th><th>Date</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($materials)): ?>
                        <tr><td colspan="5" style="text-align: center; color: var(--text-muted);">No revision materials generated yet.</td></tr>
                    <?php else: foreach ($materials as $m): ?>
                        <tr>
                            <td><a href="/results.php?id=<?= $m['id'] ?>" style="color: #fff; font-weight: 500; text-decoration: none;"><?= htmlspecialchars($m['exam_name']) ?></a></td>
                            <td><?= ucfirst($m['content_type']) ?></td>
                            <td><?= htmlspecialchars($m['user_email']) ?></td>
                            <td><small><?= htmlspecialchars($m['created_at']) ?></small></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Are you sure?');">
                                    <input type="hidden" name="delete_material_id" value="<?= $m['id'] ?>">
                                    <button type="submit" class="btn" style="background: rgba(239, 68, 68, 0.1); color: var(--error); border: 1px solid rgba(239, 68, 68, 0.2); padding: 0.25rem 0.5rem; font-size: 0.75rem; border-radius: 4px;">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/views/layout/footer.php'; ?>
