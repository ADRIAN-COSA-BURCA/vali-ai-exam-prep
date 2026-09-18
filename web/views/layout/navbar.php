<?php
// navbar.php - Global Navigation Bar
$current = basename($_SERVER['SCRIPT_NAME']);
?>
<nav class="navbar">
    <div class="brand">
        <a href="/index.php" style="text-decoration:none; color:inherit;">PrepAI</a>
    </div>

    <div class="nav-links">
        <a href="/index.php" class="<?= $current === 'index.php' ? 'active' : '' ?>">Home</a>

        <a href="/about.php" class="<?= $current === 'about.php' ? 'active' : '' ?>">About Us</a>

        <a href="/contact.php" class="<?= $current === 'contact.php' ? 'active' : '' ?>">Contact</a>

        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="/login.php" class="<?= $current === 'login.php' ? 'active' : '' ?>">Login</a>
            <a href="/register.php" class="<?= $current === 'register.php' ? 'active' : '' ?>">Register</a>
        <?php else: ?>
            <a href="/dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
			<a href="/analytics.php" class="<?= $current === 'analytics.php' ? 'active' : '' ?>">Analytics</a>
            <a href="/logout.php">Logout</a>
        <?php endif; ?>

        <a href="/admin-login.php" class="<?= $current === 'admin-login.php' ? 'active' : '' ?>">Admin</a>
    </div>
</nav>
