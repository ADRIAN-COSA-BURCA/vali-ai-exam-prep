<?php
// AuthController.php - Authentication Controller

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Analytics.php';

class AuthController
{
    public static function register($email, $password, $confirmPassword)
    {
        if (empty($email) || empty($password)) {
            return "Email and password are required.";
        }

        if ($password !== $confirmPassword) {
            return "Passwords do not match.";
        }

        if (User::findByEmail($email)) {
            return "Email is already registered.";
        }

        if (User::create($email, $password)) {
            Analytics::log(null, null, 'user_registered');

            header("Location: /login.php?registered=1");
            exit;
        }

        return "Registration failed. Please try again.";
    }

    public static function login($email, $password, $requiredRole = 'user')
    {
        if (empty($email) || empty($password)) {
            Analytics::log(null, null, 'login_failed');

            return "Email and password are required.";
        }

        $user = User::findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Analytics::log(null, null, 'login_failed');

            return "Invalid email or password.";
        }

        if ($user['status'] === 'blocked') {
            Analytics::log($user['id'], null, 'login_blocked');

            return "Your account has been blocked by an administrator.";
        }

        if ($requiredRole === 'admin' && $user['role'] !== 'admin') {
            Analytics::log($user['id'], null, 'admin_access_denied');

            return "Access denied. You are not an administrator.";
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];

        $actionType = ($user['role'] === 'admin')
            ? 'admin_login'
            : 'user_login';

        Analytics::log($user['id'], null, $actionType);

        $redirect = ($user['role'] === 'admin')
            ? '/admin-dashboard.php'
            : '/dashboard.php';

        header("Location: " . $redirect);
        exit;
    }

    public static function logout()
    {
        $userId = $_SESSION['user_id'] ?? null;

        if ($userId) {
            $user = User::findById($userId);

            if ($user) {
                $actionType = ($user['role'] === 'admin')
                    ? 'admin_logout'
                    : 'user_logout';

                Analytics::log($userId, null, $actionType);
            }
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();

        header("Location: /index.php");
        exit;
    }
}