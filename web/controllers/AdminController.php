<?php
// AdminController.php - Admin Portal Controller
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Material.php';
require_once __DIR__ . '/../models/Exam.php';
require_once __DIR__ . '/../models/Analytics.php';

class AdminController
{
    public static function checkAdminAuth()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header("Location: /admin-login.php");
            exit;
        }
    }

    public static function toggleUserStatus($userId, $currentStatus)
    {
        $newStatus = ($currentStatus === 'active') ? 'blocked' : 'active';
        $result = User::updateStatus($userId, $newStatus);

        if ($result) {
            $actionType = ($newStatus === 'blocked') ? 'user_blocked' : 'user_unblocked';
            Analytics::log($_SESSION['user_id'] ?? null, null, $actionType);
        }

        return $result;
    }

    public static function deleteUpload($materialId)
    {
        $material = Material::findById($materialId);

        if ($material && !empty($material['file_path'])) {
            $fullPath = UPLOAD_DIR . $material['file_path'];

            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        $result = Material::delete($materialId, null, true);

        if ($result) {
            $examId = $material['exam_id'] ?? null;
            Analytics::log($_SESSION['user_id'] ?? null, $examId, 'material_deleted');
        }

        return $result;
    }

    public static function deleteUser($userId)
    {
        $materials = Material::getByUser($userId);

        foreach ($materials as $m) {
            self::deleteUpload($m['id']);
        }

        $result = User::delete($userId);

        if ($result) {
            Analytics::log($_SESSION['user_id'] ?? null, null, 'user_deleted');
        }

        return $result;
    }

    public static function addUser($email, $password, $role)
    {
        if (empty($email) || empty($password)) {
            return "Email and password are required.";
        }

        if (User::findByEmail($email)) {
            return "Email is already registered.";
        }

        if (User::createAdmin($email, $password, $role)) {
            Analytics::log($_SESSION['user_id'] ?? null, null, 'admin_user_created');
            return true;
        }

        return "Failed to create account.";
    }

    public static function addExam($code, $name, $description, $category)
    {
        if (empty($code) || empty($name) || empty($category)) {
            return "Exam Code, Name, and Category are required.";
        }

        if (Exam::create($code, $name, $description, $category)) {
            Analytics::log($_SESSION['user_id'] ?? null, null, 'exam_created');
            return true;
        }

        return "Failed to add exam.";
    }

    public static function deleteExam($examId)
{
    $result = Exam::delete($examId);

    if ($result) {
        Analytics::log(
            $_SESSION['user_id'] ?? null,
            null,
            'exam_deleted'
        );
    }

    return $result;
}
}