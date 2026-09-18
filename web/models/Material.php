<?php
// Material.php - StudyMaterial Model
require_once __DIR__ . '/../config/db.php';

class Material {
    public static function create($userId, $examId, $sourceMode, $filePath, $contentType, $difficulty, $resultsJson) {
        $db = DB::getConnection();
        $stmt = $db->prepare("INSERT INTO study_materials (user_id, exam_id, source_mode, file_path, content_type, difficulty, raw_results_json) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$userId, $examId, $sourceMode, $filePath, $contentType, $difficulty, $resultsJson])) {
            return $db->lastInsertId();
        }
        return false;
    }

    public static function findById($id) {
        $db = DB::getConnection();
        $stmt = $db->prepare("SELECT sm.*, e.name AS exam_name, e.code AS exam_code FROM study_materials sm JOIN exams e ON sm.exam_id = e.id WHERE sm.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function getByUser($userId) {
        $db = DB::getConnection();
        $stmt = $db->prepare("SELECT sm.*, e.name AS exam_name, e.code AS exam_code FROM study_materials sm JOIN exams e ON sm.exam_id = e.id WHERE sm.user_id = ? ORDER BY sm.created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function delete($id, $userId, $isAdmin = false) {
        $db = DB::getConnection();
        if ($isAdmin) {
            $stmt = $db->prepare("DELETE FROM study_materials WHERE id = ?");
            return $stmt->execute([$id]);
        } else {
            $stmt = $db->prepare("DELETE FROM study_materials WHERE id = ? AND user_id = ?");
            return $stmt->execute([$id, $userId]);
        }
    }

    public static function getAllAdmin() {
        $db = DB::getConnection();
        $stmt = $db->query("SELECT sm.*, e.name AS exam_name, u.email AS user_email FROM study_materials sm JOIN exams e ON sm.exam_id = e.id JOIN users u ON sm.user_id = u.id ORDER BY sm.created_at DESC");
        return $stmt->fetchAll();
    }
}
