<?php
require_once __DIR__ . '/../config/db.php';

class Analytics {

    /* ---------- LOGGING ---------- */
    public static function log($userId, $examId, $actionType) {
        $db = DB::getConnection();
        $stmt = $db->prepare("
            INSERT INTO analytics_log (user_id, exam_id, action_type)
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$userId, $examId, $actionType]);
    }

    /* ---------- SYSTEM OVERVIEW ---------- */
    public static function getGlobalStats() {
        $db = DB::getConnection();
        return [
            'total_users'       => $db->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn(),
            'total_materials'   => $db->query("SELECT COUNT(*) FROM study_materials")->fetchColumn(),
            'total_flashcards'  => $db->query("SELECT COUNT(*) FROM study_materials WHERE content_type='flashcards'")->fetchColumn(),
            'total_mock_tests'  => $db->query("SELECT COUNT(*) FROM study_materials WHERE content_type='questions'")->fetchColumn()
        ];
    }

    public static function getTopExams() {
        $db = DB::getConnection();
        return $db->query("
            SELECT e.name, COUNT(sm.id) AS count
            FROM study_materials sm
            JOIN exams e ON sm.exam_id = e.id
            GROUP BY sm.exam_id
            ORDER BY count DESC
            LIMIT 5
        ")->fetchAll();
    }

    public static function getContentTypeStats() {
        $db = DB::getConnection();
        return $db->query("
            SELECT content_type, COUNT(*) AS count
            FROM study_materials
            GROUP BY content_type
        ")->fetchAll();
    }

    /* ---------- MOCK EXAM ANALYTICS ---------- */
    public static function getUserExamStats($userId) {
        $db = DB::getConnection();
        $stmt = $db->prepare("
            SELECT 
                e.name AS exam_name,
                COUNT(mr.id) AS attempts,
                ROUND(AVG(mr.score_percentage), 2) AS avg_score,
                SUM(CASE WHEN mr.passed = 1 THEN 1 ELSE 0 END) AS passed_count
            FROM mock_exam_results mr
            JOIN exams e ON mr.exam_id = e.id
            WHERE mr.user_id = ?
            GROUP BY mr.exam_id
            ORDER BY attempts DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /* ---------- FLASHCARD ANALYTICS ---------- */
    public static function getUserFlashcardStats($userId) {
        $db = DB::getConnection();

        // 1) Per-exam totals (include exams even if no sessions)
        // NOTE: include exams that either the user has created study_materials for
        // OR that already have flashcard_stats rows for this user. Previously the
        // WHERE limited exams only to those in study_materials which caused
        // flashcard sessions to be hidden when materials were created under a
        // different user account (or system user).
        $stmt = $db->prepare("
            SELECT
                e.id AS exam_id,
                e.name AS exam_name,
                COALESCE(SUM(fs.correct), 0) AS correct,
                COALESCE(SUM(fs.incorrect), 0) AS incorrect,
                COALESCE(COUNT(fs.id), 0) AS sessions,
                COALESCE(MAX(fs.created_at), NULL) AS last_activity
            FROM exams e
            LEFT JOIN flashcard_stats fs ON fs.exam_id = e.id AND fs.user_id = ?
            WHERE (
                e.id IN (SELECT DISTINCT exam_id FROM study_materials WHERE user_id = ?)
                OR e.id IN (SELECT DISTINCT exam_id FROM flashcard_stats WHERE user_id = ?)
            )
            GROUP BY e.id, e.name
            ORDER BY sessions DESC, e.name ASC
        ");
        // parameters: fs.user_id (for join), study_materials.user_id, flashcard_stats.user_id
        $stmt->execute([$userId, $userId, $userId]);

        $totals = $stmt->fetchAll();

        return $totals;
    }

    public static function getUserFlashcardTimeSeries($userId, $days = 30) {
        $db = DB::getConnection();
        $stmt = $db->prepare("
            SELECT DATE(created_at) AS day,
                   SUM(correct) AS correct,
                   SUM(incorrect) AS incorrect,
                   COUNT(id) AS sessions
            FROM flashcard_stats
            WHERE user_id = ?
              AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY DATE(created_at) ASC
        ");
        $stmt->execute([$userId, (int)$days]);
        return $stmt->fetchAll();
    }


    public static function getRecentLogs()
{
    $db = DB::getConnection();

    $stmt = $db->query(
        "SELECT
            al.*,
            e.code AS exam_code,
            u.email AS user_email
         FROM analytics_log al
         LEFT JOIN exams e
            ON al.exam_id = e.id
         LEFT JOIN users u
            ON al.user_id = u.id
         ORDER BY al.created_at DESC"
    );

    return $stmt->fetchAll();
}


    /* ---------- STUDY MATERIAL ANALYTICS ---------- */
    public static function getUserStudyStats($userId) {
        $db = DB::getConnection();
        $stmt = $db->prepare("
            SELECT 
                e.name AS exam_name,
                sm.content_type,
                COUNT(sm.id) AS count
            FROM study_materials sm
            JOIN exams e ON sm.exam_id = e.id
            WHERE sm.user_id = ?
            GROUP BY sm.exam_id, sm.content_type
            ORDER BY count DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

}
