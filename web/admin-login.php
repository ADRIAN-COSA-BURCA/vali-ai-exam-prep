<?php
// admin-login.php - Admin Login Page
require_once __DIR__ . '/controllers/AuthController.php';

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = AuthController::login($_POST['email'], $_POST['password'], 'admin');
}

require_once __DIR__ . '/views/layout/header.php';
require_once __DIR__ . '/views/layout/navbar.php';
?>
<div class="container" style="max-width: 480px; margin-top: 4rem;">
    <div class="card">
        <h2 style="margin-bottom: 1.5rem; text-align: center;"><i class="fa-solid fa-lock" style="color: var(--accent);"></i> Admin Portal</h2>
        <?php if ($error): ?>
            <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid var(--error); color: #fca5a5; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.9rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="email">Admin Email</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="admin@domain.com" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Administrator Login</button>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/views/layout/footer.php'; ?>
