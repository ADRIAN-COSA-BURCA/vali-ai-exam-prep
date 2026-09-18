<?php
// MockExamController.php - Handles saving mock exam results

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Material.php';
require_once __DIR__ . '/../models/Analytics.php';
require_once __DIR__ . '/../config/db.php';

class MockExamController {
    public static function handle($input = null) {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['status' => false, 'error' => 'Invalid request method'];
        }

        if ($input === null) {
            $input = json_decode(file_get_contents('php://input'), true);
        }

        if (!$input) {
            return ['status' => false, 'error' => 'Invalid JSON payload'];
        }

        $materialId      = $input['material_id'] ?? null;
        $answers         = $input['answers'] ?? null;
        $scorePercentage = $input['score_percentage'] ?? null;
        $correctCount    = $input['correct_count'] ?? null;
        $incorrectCount  = $input['incorrect_count'] ?? null;
        $passed          = $input['passed'] ?? null;
        $timeTaken       = $input['time_taken_seconds'] ?? null;

        if (!$materialId || !$answers) {
            return ['status' => false, 'error' => 'Missing required fields'];
        }

        $material = Material::findById($materialId);
        if (!$material) {
            return ['status' => false, 'error' => 'Material not found'];
        }

        if (!isset($_SESSION['user_id']) || ($material['user_id'] != $_SESSION['user_id'] && $_SESSION['user_role'] !== 'admin')) {
            return ['status' => false, 'error' => 'Unauthorized'];
        }

        $userId = $material['user_id'];
        $examId = $material['exam_id'];
        $db = DB::getConnection();

        $stmt = $db->prepare("
            UPDATE study_materials
            SET user_answers_json = ?,
                score_percentage = ?,
                correct_count = ?,
                incorrect_count = ?,
                passed = ?,
                time_taken_seconds = ?,
                completed_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            json_encode($answers),
            $scorePercentage,
            $correctCount,
            $incorrectCount,
            $passed ? 1 : 0,
            $timeTaken,
            $materialId
        ]);

        foreach ($answers as $index => $ans) {
            $stmt2 = $db->prepare("
                INSERT INTO mock_exam_answers (material_id, question_index, selected_option, correct_option, timed_out)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt2->execute([
                $materialId,
                $index,
                $ans['selected'],
                $ans['correct'],
                $ans['timed_out'] ? 1 : 0
            ]);
        }

        $stmt3 = $db->prepare("
            INSERT INTO mock_exam_results (user_id, exam_id, material_id, score_percentage, correct_count, incorrect_count, passed, time_taken_seconds)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt3->execute([
            $userId,
            $examId,
            $materialId,
            $scorePercentage,
            $correctCount,
            $incorrectCount,
            $passed ? 1 : 0,
            $timeTaken
        ]);

        Analytics::log($userId, $examId, 'mock_exam_completed');
        return ['status' => true, 'message' => 'Mock exam results saved'];
    }
}
