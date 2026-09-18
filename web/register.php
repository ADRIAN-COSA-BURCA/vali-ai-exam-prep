<?php
// register.php - User Registration Page
require_once __DIR__ . '/controllers/AuthController.php';

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = AuthController::register($_POST['email'], $_POST['password'], $_POST['confirm_password']);
}

require_once __DIR__ . '/views/layout/header.php';
require_once __DIR__ . '/views/layout/navbar.php';
?>
<div class="container" style="max-width: 480px; margin-top: 4rem;">
    <div class="card">
        <h2 style="margin-bottom: 1.5rem; text-align: center;"><i class="fa-solid fa-user-plus" style="color: var(--accent);"></i> Register</h2>
        <?php if ($error): ?>
            <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid var(--error); color: #fca5a5; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.9rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="name@domain.com" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Create Account</button>
        </form>
        <p style="margin-top: 1.5rem; text-align: center; font-size: 0.9rem; color: var(--text-muted);">
            Already have an account? <a href="/login.php" style="color: var(--accent); text-decoration: none;">Sign In</a>
        </p>
    </div>
</div>
<?php require_once __DIR__ . '/views/layout/footer.php'; ?>
