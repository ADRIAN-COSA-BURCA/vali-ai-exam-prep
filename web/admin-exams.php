<?php
// admin-exams.php - Manage Exam Library
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/models/Exam.php';

AdminController::checkAdminAuth();

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_exam'])) {
        $res = AdminController::addExam($_POST['code'], $_POST['name'], $_POST['description'], $_POST['category']);
        if ($res === true) {
            $success = "Exam added to library successfully.";
        } else {
            $error = $res;
        }
    } elseif (isset($_POST['delete_exam_id'])) {
        AdminController::deleteExam($_POST['delete_exam_id']);
        $success = "Exam deleted successfully.";
    }
}

$exams = Exam::getAll();

require_once __DIR__ . '/views/layout/header.php';
require_once __DIR__ . '/views/layout/navbar.php';
?>
<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2>Exam Library Management</h2>
        <div style="display: flex; gap: 1rem;">
            <a href="/admin-dashboard.php" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.9rem;"><i class="fa-solid fa-users"></i> Users</a>
            <a href="/admin-exams.php" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.9rem;"><i class="fa-solid fa-book"></i> Library</a>
            <a href="/admin-logs.php" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.9rem;"><i class="fa-solid fa-list-check"></i> Logs</a>
        </div>
    </div>

    <?php if ($success): ?>
        <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid var(--success); color: #a7f3d0; padding: 0.75rem; border-radius: 8px; margin-bottom: 2rem;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid var(--error); color: #fca5a5; padding: 0.75rem; border-radius: 8px; margin-bottom: 2rem;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="grid" style="grid-template-columns: 1.2fr 1fr; gap: 2rem; margin-top: 1rem;">
        <div class="card">
            <h3 style="margin-bottom: 1.5rem;"><i class="fa-solid fa-list"></i> Exam Catalog</h3>
            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table>
                    <thead>
                        <tr><th>Code</th><th>Name</th><th>Category</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exams as $e): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($e['code']) ?></strong></td>
                                <td><?= htmlspecialchars($e['name']) ?></td>
                                <td><span class="badge badge-tech"><?= htmlspecialchars($e['category']) ?></span></td>
                                <td>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                                        <input type="hidden" name="delete_exam_id" value="<?= $e['id'] ?>">
                                        <button type="submit" class="btn" style="background: rgba(239, 68, 68, 0.1); color: var(--error); border: 1px solid rgba(239, 68, 68, 0.2); padding: 0.3rem 0.6rem; font-size: 0.8rem; border-radius: 4px;">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-bottom: 1.5rem;"><i class="fa-solid fa-plus"></i> Add New Exam</h3>
            <form method="POST">
                <input type="hidden" name="add_exam" value="1">
                <div class="form-group">
                    <label for="code">Exam Code (e.g. AZ-900)</label>
                    <input type="text" id="code" name="code" class="form-control" placeholder="AZ-900" required>
                </div>
                <div class="form-group">
                    <label for="name">Exam Name</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="Microsoft Azure Fundamentals" required>
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <input type="text" id="category" name="category" class="form-control" placeholder="IT & Cloud" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3" placeholder="Overview..." style="resize: none;"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;"><i class="fa-solid fa-save"></i> Save Exam</button>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/views/layout/footer.php'; ?>
