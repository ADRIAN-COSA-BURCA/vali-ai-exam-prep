<?php
// MockExamController.php - Handles server-side validation and saving of mock exam results

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Material.php';
require_once __DIR__ . '/../models/Analytics.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/MockExamScorer.php';

class MockExamController {

    public static function handle($input = null) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return ['status' => false, 'error' => 'Invalid request method'];
        }

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            return ['status' => false, 'error' => 'Unauthorized'];
        }

        if ($input === null) {
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);
        }

        if (!is_array($input)) {
            http_response_code(400);
            return ['status' => false, 'error' => 'Invalid JSON payload'];
        }

        // Validate material_id
        if (!isset($input['material_id']) || !filter_var($input['material_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            http_response_code(400);
            return ['status' => false, 'error' => 'Valid material_id is required'];
        }
        $materialId = (int)$input['material_id'];

        // Validate answers array presence
        if (!isset($input['answers']) || !is_array($input['answers'])) {
            http_response_code(400);
            return ['status' => false, 'error' => 'Answers array is required'];
        }
        $submittedAnswers = $input['answers'];

        // Validate time_taken_seconds (compatible with client, integer non-negative)
        $timeTaken = $input['time_taken_seconds'] ?? 0;
        if (is_numeric($timeTaken) && (int)$timeTaken == $timeTaken && (int)$timeTaken >= 0 && !is_bool($timeTaken)) {
            $timeTaken = (int)$timeTaken;
        } else {
            http_response_code(400);
            return ['status' => false, 'error' => 'Invalid time_taken_seconds value'];
        }

        $db = DB::getConnection();
        $db->beginTransaction();

        try {
            // Lock material row for update
            $stmt = $db->prepare("
                SELECT id, user_id, exam_id, content_type, raw_results_json, completed_at
                FROM study_materials
                WHERE id = ?
                FOR UPDATE
            ");
            $stmt->execute([$materialId]);
            $material = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$material) {
                $db->rollBack();
                http_response_code(404);
                return ['status' => false, 'error' => 'Material not found'];
            }

            // Strict ownership check: only the material creator can submit exam answers
            if ((int)$material['user_id'] !== (int)$_SESSION['user_id']) {
                $db->rollBack();
                http_response_code(403);
                return ['status' => false, 'error' => 'Forbidden: You do not own this material'];
            }

            // Material type check
            if ($material['content_type'] !== 'questions') {
                $db->rollBack();
                http_response_code(400);
                return ['status' => false, 'error' => 'Material is not a mock examination'];
            }

            // Duplicate submission check: completed_at must be NULL
            if (!empty($material['completed_at'])) {
                $db->rollBack();
                http_response_code(409);
                return [
                    'status' => false,
                    'error' => 'Mock exam attempt has already been completed',
                    'material_id' => $materialId
                ];
            }

            // Inconsistent state check: check if results already exist despite null completed_at
            $checkResults = $db->prepare("SELECT COUNT(*) FROM mock_exam_results WHERE material_id = ?");
            $checkResults->execute([$materialId]);
            if ((int)$checkResults->fetchColumn() > 0) {
                $db->rollBack();
                error_log("Inconsistent state detected: material_id {$materialId} has mock_exam_results but completed_at is NULL.");
                http_response_code(409);
                return [
                    'status' => false,
                    'error' => 'Inconsistent state: mock exam results already exist for this attempt',
                    'material_id' => $materialId
                ];
            }

            // Validate stored questions structure
            $rawJson = json_decode($material['raw_results_json'], true);
            if (!is_array($rawJson) || !isset($rawJson['questions'])) {
                $db->rollBack();
                http_response_code(422);
                return ['status' => false, 'error' => 'Corrupt examination data in study material'];
            }

            // Server-side scoring and validation
            $scoring = MockExamScorer::validateAndScore($rawJson['questions'], $submittedAnswers);
            if (!$scoring['valid']) {
                $db->rollBack();
                http_response_code($scoring['http_code'] ?? 400);
                return ['status' => false, 'error' => $scoring['error']];
            }

            // 1. UPDATE study_materials with authoritative scores and canonical answers
            $updateSm = $db->prepare("
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
            $updateSm->execute([
                json_encode($scoring['canonical_answers']),
                $scoring['score_percentage'],
                $scoring['correct_count'],
                $scoring['incorrect_count'],
                $scoring['passed'] ? 1 : 0,
                $timeTaken,
                $materialId
            ]);

            // 2. INSERT INTO mock_exam_answers
            $insertAns = $db->prepare("
                INSERT INTO mock_exam_answers (material_id, question_index, selected_option, correct_option, timed_out)
                VALUES (?, ?, ?, ?, ?)
            ");
            foreach ($scoring['answer_rows'] as $row) {
                $insertAns->execute([
                    $materialId,
                    $row['question_index'],
                    $row['selected_option'],
                    $row['correct_option'],
                    $row['timed_out']
                ]);
            }

            // 3. INSERT INTO mock_exam_results
            $insertRes = $db->prepare("
                INSERT INTO mock_exam_results (user_id, exam_id, material_id, score_percentage, correct_count, incorrect_count, passed, time_taken_seconds)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insertRes->execute([
                $material['user_id'],
                $material['exam_id'],
                $materialId,
                $scoring['score_percentage'],
                $scoring['correct_count'],
                $scoring['incorrect_count'],
                $scoring['passed'] ? 1 : 0,
                $timeTaken
            ]);

            // Capture inserted result_id immediately before any subsequent queries
            $resultId = (int)$db->lastInsertId();

            // 4. Audit logging (participates in same transaction via DB::getConnection())
            Analytics::log((int)$material['user_id'], (int)$material['exam_id'], 'mock_exam_completed');

            // 5. Commit all writes atomically
            $db->commit();

            http_response_code(200);
            return [
                'status' => true,
                'message' => 'Mock exam completed successfully',
                'material_id' => $materialId,
                'result_id' => $resultId,
                'score_percentage' => $scoring['score_percentage'],
                'correct_count' => $scoring['correct_count'],
                'incorrect_count' => $scoring['incorrect_count'],
                'total_questions' => $scoring['total_questions'],
                'passed' => $scoring['passed']
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Mock exam submission failed: " . $e->getMessage());
            http_response_code(500);
            return ['status' => false, 'error' => 'An error occurred while saving mock exam results'];
        }
    }
}
