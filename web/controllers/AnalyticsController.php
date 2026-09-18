<?php
// AnalyticsController.php - Provides JSON analytics data
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Analytics.php';

class AnalyticsController {

    public static function getExamStats() {
        $userId = $_SESSION['user_id'];
        $data = Analytics::getUserExamStats($userId);
        self::respondJSON($data);
    }

    public static function getFlashcardStats() {
        $userId = $_SESSION['user_id'];
        $data = Analytics::getUserFlashcardStats($userId);
        self::respondJSON($data);
    }

    public static function getStudyStats() {
        $userId = $_SESSION['user_id'];
        $data = Analytics::getUserStudyStats($userId);
        self::respondJSON($data);
    }

    private static function respondJSON($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
