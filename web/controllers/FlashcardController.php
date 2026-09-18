<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

class FlashcardController {
    public static function logSession($materialId, $userId = null) {
        if (!$userId && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        }

        if (!$userId) {
            return ['success' => false, 'error' => 'Unauthorized'];
        }

        if (!$materialId) {
            return ['success' => false, 'error' => 'Missing material_id'];
        }

        $db = DB::getConnection();
        $stmt = $db->prepare("SELECT exam_id FROM study_materials WHERE id = ?");
        $stmt->execute([$materialId]);
        $examId = $stmt->fetchColumn();

        if (!$examId) {
            return ['success' => false, 'error' => 'Invalid material_id'];
        }


        $stmt = $db->prepare("
            INSERT INTO flashcard_stats (user_id, exam_id, correct, incorrect)
            VALUES (?, ?, 0, 0)
        ");

        $stmt->execute([$userId, $examId]);
        $insertId = $db->lastInsertId();
        return ['success' => true, 'id' => $insertId, 'exam_id' => $examId];
    }
}
