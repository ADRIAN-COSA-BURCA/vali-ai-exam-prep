<?php
// AIController.php - AI Processing and Generation Controller
require_once __DIR__ . '/../models/Material.php';
require_once __DIR__ . '/../models/Analytics.php';
require_once __DIR__ . '/MockDataHelper.php';
require_once __DIR__ . '/../models/Exam.php';

class AIController
{
    public static function generate(
        $userId,
        $examId,
        $mode,
        $fileName,
        $contentType,
        $difficulty,
        $flashcardCount = null,
        $questionCount = null
    ) {
        // 1. Validate exam
        $exam = Exam::findById($examId);
        if (!$exam) {
            return ['status' => false, 'error' => 'Exam not found.'];
        }

        // 2. Validate content type
        $contentType = strtolower(trim($contentType));
        $allowedContentTypes = ['summary', 'flashcards', 'questions'];
        if (!in_array($contentType, $allowedContentTypes, true)) {
            return ['status' => false, 'error' => 'Invalid content type selected.'];
        }

        // 3. Validate difficulty
        $difficulty = ucfirst(strtolower(trim($difficulty)));
        $allowedDifficulties = ['Easy', 'Medium', 'Hard'];
        if (!in_array($difficulty, $allowedDifficulties, true)) {
            $difficulty = 'Medium';
        }

        // 4. Validate counts
        $flashcardCount = self::sanitizeCount($flashcardCount);
        $questionCount  = self::sanitizeCount($questionCount);

        if ($contentType === 'flashcards' && !$flashcardCount) {
            $flashcardCount = 10;
        }

        if ($contentType === 'questions' && !$questionCount) {
            $questionCount = 10;
        }

        if ($contentType === 'summary') {
            $flashcardCount = null;
            $questionCount  = null;
        }

        // 5. Handle file content (upload mode)
        $fileContentBase64 = null;
$fileType = null;
if ($mode === 'upload' && $fileName) {
    $fullPath = rtrim(UPLOAD_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;
    if (is_readable($fullPath)) {
        $rawContent = file_get_contents($fullPath);
        if ($rawContent !== false) {
            $fileContentBase64 = base64_encode($rawContent);
            $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        }
    }
}

        // 6. Build payload for AI backend
        $postData = [
    'mode'            => $mode,
    'exam_name'       => $exam['name'],
    'content_type'    => $contentType,
    'difficulty'      => $difficulty,
    'flashcard_count' => $flashcardCount,
    'question_count'  => $questionCount,
    'file_content'    => $fileContentBase64,
    'file_type'       => $fileType,
];

        // 7. ALWAYS call AI backend for online mode
        $resultJson = self::callAIBackend($postData);

        // If backend fails, fallback ONLY for upload mode
        if (!$resultJson && $mode === 'upload') {
            $resultJson = MockDataHelper::get($exam['name'], $contentType, $difficulty);
        }

        if (!$resultJson) {
            return ['status' => false, 'error' => 'AI backend failed.'];
        }

        // 8. Persist result
        $materialId = Material::create(
            $userId,
            $examId,
            $mode,
            $fileName,
            $contentType,
            $difficulty,
            $resultJson
        );

        if ($materialId) {
            Analytics::log($userId, $examId, 'generate_' . $contentType);
            return ['status' => true, 'id' => $materialId];
        }

        return ['status' => false, 'error' => 'Failed to save generated material.'];
    }

    /**
     * Call AI backend (Python FastAPI) with JSON payload.
     */
    private static function callAIBackend($data)
{
    $ch = curl_init(AI_BACKEND_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 90,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200 || $response === false) {
        error_log("AI backend HTTP failure: code=$httpCode, curl_error=$curlError");
        return null;
    }

    $decoded = json_decode($response, true);

    // Reject error payloads or empty/missing content arrays
    if (!$decoded || isset($decoded['type']) && $decoded['type'] === 'error') {
        error_log("AI backend returned error payload: " . $response);
        return null;
    }

    $contentType = $data['content_type'];
    if ($contentType === 'summary' && (empty($decoded['summary']) || !is_array($decoded['summary']))) {
        error_log("AI backend returned empty summary for exam: " . $data['exam_name']);
        return null;
    }
    if ($contentType === 'flashcards' && empty($decoded['flashcards'])) {
        error_log("AI backend returned empty flashcards.");
        return null;
    }
    if ($contentType === 'questions' && empty($decoded['questions'])) {
        error_log("AI backend returned empty questions.");
        return null;
    }

    return $response;
}

    private static function sanitizeCount($value)
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        $v = (int)$value;
        if ($v <= 0) {
            return null;
        }

        if ($v > 50) {
            $v = 50;
        }

        return $v;
    }
}
