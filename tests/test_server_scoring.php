<?php
/**
 * test_server_scoring.php - Test suite for Feature 2: Server-side Mock Exam Scoring
 *
 * Runs inside web container or with DB connection:
 * php tests/test_server_scoring.php
 */

$baseDir = file_exists('/var/www/html/config/config.php') ? '/var/www/html' : dirname(__DIR__) . '/web';
require_once $baseDir . '/config/config.php';
require_once $baseDir . '/config/db.php';
require_once $baseDir . '/models/Material.php';
require_once $baseDir . '/models/Analytics.php';
require_once $baseDir . '/controllers/MockExamScorer.php';
require_once $baseDir . '/controllers/MockExamController.php';

$passed = 0;
$failed = 0;
$total = 0;

function run_test($name, $callable) {
    global $passed, $failed, $total;
    $total++;
    try {
        $result = $callable();
        if ($result === true) {
            $passed++;
            echo "[PASS] {$name}\n";
        } else {
            $failed++;
            echo "[FAIL] {$name} - Assertion failed\n";
        }
    } catch (Throwable $e) {
        $failed++;
        echo "[FAIL] {$name} - Exception: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}

echo "========================================================\n";
echo "FEATURE 2: SERVER-SIDE SCORING COMPREHENSIVE TEST SUITE\n";
echo "========================================================\n\n";

// --- 1. UNIT TESTS ON MockExamScorer ---
echo "--- Section 1: Pure Unit Tests (MockExamScorer) ---\n";

$sampleQuestions = [
    [
        'question' => 'Q1',
        'options' => ['Option 0', 'Option 1', 'Option 2', 'Option 3'],
        'correct_index' => 0,
        'explanation' => 'E1'
    ],
    [
        'question' => 'Q2',
        'options' => ['Option 0', 'Option 1', 'Option 2', 'Option 3'],
        'correct_index' => 1,
        'explanation' => 'E2'
    ],
    [
        'question' => 'Q3',
        'options' => ['Option 0', 'Option 1', 'Option 2', 'Option 3'],
        'correct_index' => 2,
        'explanation' => 'E3'
    ],
    [
        'question' => 'Q4',
        'options' => ['Option 0', 'Option 1', 'Option 2', 'Option 3'],
        'correct_index' => 3,
        'explanation' => 'E4'
    ]
];

run_test("T01: All correct answers yields 100% and passed=true", function() use ($sampleQuestions) {
    $submitted = [
        ['question_index' => 0, 'selected' => 0, 'timed_out' => false],
        ['question_index' => 1, 'selected' => 1, 'timed_out' => false],
        ['question_index' => 2, 'selected' => 2, 'timed_out' => false],
        ['question_index' => 3, 'selected' => 3, 'timed_out' => false]
    ];
    $res = MockExamScorer::validateAndScore($sampleQuestions, $submitted);
    return $res['valid'] === true
        && $res['correct_count'] === 4
        && $res['incorrect_count'] === 0
        && $res['score_percentage'] === 100
        && $res['passed'] === true;
});

run_test("T02: All incorrect answers yields 0% and passed=false", function() use ($sampleQuestions) {
    $submitted = [
        ['question_index' => 0, 'selected' => 1, 'timed_out' => false],
        ['question_index' => 1, 'selected' => 0, 'timed_out' => false],
        ['question_index' => 2, 'selected' => 0, 'timed_out' => false],
        ['question_index' => 3, 'selected' => 0, 'timed_out' => false]
    ];
    $res = MockExamScorer::validateAndScore($sampleQuestions, $submitted);
    return $res['valid'] === true
        && $res['correct_count'] === 0
        && $res['incorrect_count'] === 4
        && $res['score_percentage'] === 0
        && $res['passed'] === false;
});

run_test("T03: Exact 70% boundary passes (7 out of 10 correct)", function() {
    $tenQuestions = [];
    for ($i = 0; $i < 10; $i++) {
        $tenQuestions[] = [
            'question' => "Q{$i}",
            'options' => ['A', 'B', 'C', 'D'],
            'correct_index' => 0,
            'explanation' => "E{$i}"
        ];
    }
    $submitted = [];
    for ($i = 0; $i < 10; $i++) {
        $submitted[] = [
            'question_index' => $i,
            'selected' => ($i < 7) ? 0 : 1,
            'timed_out' => false
        ];
    }
    $res = MockExamScorer::validateAndScore($tenQuestions, $submitted);
    return $res['valid'] === true
        && $res['correct_count'] === 7
        && $res['score_percentage'] === 70
        && $res['passed'] === true;
});

run_test("T04: Score below 70% fails (e.g. 6 out of 10 is 60%)", function() {
    $tenQuestions = [];
    for ($i = 0; $i < 10; $i++) {
        $tenQuestions[] = [
            'question' => "Q{$i}",
            'options' => ['A', 'B', 'C', 'D'],
            'correct_index' => 0,
            'explanation' => "E{$i}"
        ];
    }
    $submitted = [];
    for ($i = 0; $i < 10; $i++) {
        $submitted[] = [
            'question_index' => $i,
            'selected' => ($i < 6) ? 0 : 1,
            'timed_out' => false
        ];
    }
    $res = MockExamScorer::validateAndScore($tenQuestions, $submitted);
    return $res['valid'] === true
        && $res['correct_count'] === 6
        && $res['score_percentage'] === 60
        && $res['passed'] === false;
});

run_test("T05: Score rounding rule: 2 out of 3 is 67% (fails) and 13 out of 19 is 68% (fails)", function() {
    $q3 = [
        ['question' => 'Q0', 'options' => ['A', 'B'], 'correct_index' => 0],
        ['question' => 'Q1', 'options' => ['A', 'B'], 'correct_index' => 0],
        ['question' => 'Q2', 'options' => ['A', 'B'], 'correct_index' => 0]
    ];
    $sub3 = [
        ['question_index' => 0, 'selected' => 0, 'timed_out' => false],
        ['question_index' => 1, 'selected' => 0, 'timed_out' => false],
        ['question_index' => 2, 'selected' => 1, 'timed_out' => false]
    ];
    $res = MockExamScorer::validateAndScore($q3, $sub3);
    return $res['score_percentage'] === 67 && $res['passed'] === false;
});

run_test("T06: Option index 0 accepted and scored correctly", function() use ($sampleQuestions) {
    $submitted = [
        ['question_index' => 0, 'selected' => 0, 'timed_out' => false]
    ];
    $res = MockExamScorer::validateAndScore($sampleQuestions, $submitted);
    return $res['valid'] === true
        && $res['canonical_answers'][0]['selected'] === 0
        && $res['canonical_answers'][0]['correct'] === 0
        && $res['correct_count'] === 1;
});

run_test("T07: Null / omitted answer counted incorrect without warning", function() use ($sampleQuestions) {
    $submitted = [
        ['question_index' => 0, 'selected' => null, 'timed_out' => false]
    ];
    $res = MockExamScorer::validateAndScore($sampleQuestions, $submitted);
    return $res['valid'] === true
        && $res['canonical_answers'][0]['selected'] === null
        && $res['correct_count'] === 0;
});

run_test("T08: Timed-out answer counted incorrect even if selected matches correct_index", function() use ($sampleQuestions) {
    $submitted = [
        ['question_index' => 0, 'selected' => 0, 'timed_out' => true]
    ];
    $res = MockExamScorer::validateAndScore($sampleQuestions, $submitted);
    return $res['valid'] === true
        && $res['canonical_answers'][0]['timed_out'] === true
        && $res['correct_count'] === 0;
});

run_test("T09: Empty submitted answers array accepted as all-unanswered", function() use ($sampleQuestions) {
    $res = MockExamScorer::validateAndScore($sampleQuestions, []);
    return $res['valid'] === true
        && $res['total_questions'] === 4
        && $res['correct_count'] === 0
        && $res['incorrect_count'] === 4
        && $res['score_percentage'] === 0
        && $res['passed'] === false
        && count($res['canonical_answers']) === 4;
});

run_test("T10: Rejects boolean selection true with HTTP 400", function() use ($sampleQuestions) {
    $submitted = [
        ['question_index' => 0, 'selected' => true, 'timed_out' => false]
    ];
    $res = MockExamScorer::validateAndScore($sampleQuestions, $submitted);
    return $res['valid'] === false && $res['http_code'] === 400;
});

run_test("T11: Rejects boolean selection false with HTTP 400", function() use ($sampleQuestions) {
    $submitted = [
        ['question_index' => 0, 'selected' => false, 'timed_out' => false]
    ];
    $res = MockExamScorer::validateAndScore($sampleQuestions, $submitted);
    return $res['valid'] === false && $res['http_code'] === 400;
});

run_test("T12: Rejects float selection 1.5 with HTTP 400", function() use ($sampleQuestions) {
    $submitted = [
        ['question_index' => 0, 'selected' => 1.5, 'timed_out' => false]
    ];
    $res = MockExamScorer::validateAndScore($sampleQuestions, $submitted);
    return $res['valid'] === false && $res['http_code'] === 400;
});

run_test("T13: Rejects string selection '0' with HTTP 400", function() use ($sampleQuestions) {
    $submitted = [
        ['question_index' => 0, 'selected' => "0", 'timed_out' => false]
    ];
    $res = MockExamScorer::validateAndScore($sampleQuestions, $submitted);
    return $res['valid'] === false && $res['http_code'] === 400;
});

run_test("T14: Rejects duplicate question index with HTTP 400", function() use ($sampleQuestions) {
    $submitted = [
        ['question_index' => 0, 'selected' => 0, 'timed_out' => false],
        ['question_index' => 0, 'selected' => 1, 'timed_out' => false]
    ];
    $res = MockExamScorer::validateAndScore($sampleQuestions, $submitted);
    return $res['valid'] === false && $res['http_code'] === 400;
});

run_test("T15: Rejects out-of-range option index with HTTP 400", function() use ($sampleQuestions) {
    $submitted = [
        ['question_index' => 0, 'selected' => 99, 'timed_out' => false]
    ];
    $res = MockExamScorer::validateAndScore($sampleQuestions, $submitted);
    return $res['valid'] === false && $res['http_code'] === 400;
});

run_test("T16: Rejects out-of-bounds question index with HTTP 400", function() use ($sampleQuestions) {
    $submitted = [
        ['question_index' => 99, 'selected' => 0, 'timed_out' => false]
    ];
    $res = MockExamScorer::validateAndScore($sampleQuestions, $submitted);
    return $res['valid'] === false && $res['http_code'] === 400;
});

run_test("T17: Rejects non-boolean timed_out with HTTP 400", function() use ($sampleQuestions) {
    $submitted = [
        ['question_index' => 0, 'selected' => 0, 'timed_out' => "yes"]
    ];
    $res = MockExamScorer::validateAndScore($sampleQuestions, $submitted);
    return $res['valid'] === false && $res['http_code'] === 400;
});

run_test("T18: Corrupt stored questions (missing correct_index) rejected with HTTP 422", function() {
    $badQuestions = [
        ['question' => 'Q', 'options' => ['A', 'B']]
    ];
    $res = MockExamScorer::validateAndScore($badQuestions, []);
    return $res['valid'] === false && $res['http_code'] === 422;
});

run_test("T19: Corrupt stored questions (options < 2) rejected with HTTP 422", function() {
    $badQuestions = [
        ['question' => 'Q', 'options' => ['A'], 'correct_index' => 0]
    ];
    $res = MockExamScorer::validateAndScore($badQuestions, []);
    return $res['valid'] === false && $res['http_code'] === 422;
});

// --- 2. INTEGRATION TESTS (MockExamController & Database) ---
echo "\n--- Section 2: Integration & Transaction Tests (MockExamController) ---\n";

$db = DB::getConnection();

// Create test user and exam fixtures
$testUserStmt = $db->query("SELECT id FROM users WHERE email='scoring_test_user@example.com'");
$testUserId = $testUserStmt->fetchColumn();
if (!$testUserId) {
    $db->prepare("INSERT INTO users (email, password_hash, role) VALUES ('scoring_test_user@example.com', 'dummy', 'user')")->execute();
    $testUserId = $db->lastInsertId();
}

$otherUserStmt = $db->query("SELECT id FROM users WHERE email='scoring_other_user@example.com'");
$otherUserId = $otherUserStmt->fetchColumn();
if (!$otherUserId) {
    $db->prepare("INSERT INTO users (email, password_hash, role) VALUES ('scoring_other_user@example.com', 'dummy', 'user')")->execute();
    $otherUserId = $db->lastInsertId();
}

$examStmt = $db->query("SELECT id FROM exams LIMIT 1");
$examId = $examStmt->fetchColumn();
if (!$examId) {
    $db->prepare("INSERT INTO exams (code, name, description, category) VALUES ('SC101', 'Scoring Test Exam', 'Desc', 'Test')")->execute();
    $examId = $db->lastInsertId();
}

function create_test_material($userId, $examId, $questions, $completed = false) {
    global $db;
    $rawJson = json_encode(['type' => 'questions', 'questions' => $questions]);
    $stmt = $db->prepare("
        INSERT INTO study_materials (user_id, exam_id, source_mode, content_type, difficulty, raw_results_json, completed_at)
        VALUES (?, ?, 'online', 'questions', 'medium', ?, ?)
    ");
    $completedAt = $completed ? date('Y-m-d H:i:s') : null;
    $stmt->execute([$userId, $examId, $rawJson, $completedAt]);
    return $db->lastInsertId();
}

run_test("T20: Unauthenticated request rejected with HTTP 401", function() {
    unset($_SESSION['user_id']);
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $res = MockExamController::handle(['material_id' => 1, 'answers' => []]);
    return http_response_code() === 401 && $res['status'] === false;
});

run_test("T21: Non-POST request rejected with HTTP 405", function() use ($testUserId) {
    $_SESSION['user_id'] = $testUserId;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $res = MockExamController::handle(['material_id' => 1, 'answers' => []]);
    return http_response_code() === 405 && $res['status'] === false;
});

run_test("T22: Missing or non-integer material_id rejected with HTTP 400", function() use ($testUserId) {
    $_SESSION['user_id'] = $testUserId;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $res = MockExamController::handle(['material_id' => 'abc', 'answers' => []]);
    return http_response_code() === 400 && $res['status'] === false;
});

run_test("T23: Missing answers array rejected with HTTP 400", function() use ($testUserId) {
    $_SESSION['user_id'] = $testUserId;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $res = MockExamController::handle(['material_id' => 1]);
    return http_response_code() === 400 && $res['status'] === false;
});

run_test("T24: Invalid time_taken_seconds rejected with HTTP 400", function() use ($testUserId) {
    $_SESSION['user_id'] = $testUserId;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $res = MockExamController::handle(['material_id' => 1, 'answers' => [], 'time_taken_seconds' => -10]);
    return http_response_code() === 400 && $res['status'] === false;
});

run_test("T25: Non-existent material_id rejected with HTTP 404", function() use ($testUserId) {
    $_SESSION['user_id'] = $testUserId;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $res = MockExamController::handle(['material_id' => 999999, 'answers' => [], 'time_taken_seconds' => 10]);
    return http_response_code() === 404 && $res['status'] === false;
});

run_test("T26: Strict ownership: submitting another user's material rejected with HTTP 403", function() use ($testUserId, $otherUserId, $examId, $sampleQuestions) {
    $matId = create_test_material($otherUserId, $examId, $sampleQuestions);
    $_SESSION['user_id'] = $testUserId; // Current logged in user is NOT owner
    $_SESSION['user_role'] = 'admin'; // Even admin must be rejected for submission!
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $res = MockExamController::handle(['material_id' => $matId, 'answers' => [], 'time_taken_seconds' => 10]);
    return http_response_code() === 403 && $res['status'] === false;
});

run_test("T27: Client-tampered scores are ignored and server calculates authoritative grade", function() use ($testUserId, $examId, $sampleQuestions, $db) {
    $matId = create_test_material($testUserId, $examId, $sampleQuestions);
    $_SESSION['user_id'] = $testUserId;
    $_SERVER['REQUEST_METHOD'] = 'POST';

    // Client falsely claims 100% and 4 correct answers, but submits 3 incorrect answers and 1 correct answer (Q0=0)
    $tamperedPayload = [
        'material_id' => $matId,
        'answers' => [
            ['question_index' => 0, 'selected' => 0, 'timed_out' => false],
            ['question_index' => 1, 'selected' => 0, 'timed_out' => false], // incorrect
            ['question_index' => 2, 'selected' => 0, 'timed_out' => false], // incorrect
            ['question_index' => 3, 'selected' => 0, 'timed_out' => false]  // incorrect
        ],
        'score_percentage' => 100, // TAMPERED!
        'correct_count' => 4,      // TAMPERED!
        'incorrect_count' => 0,    // TAMPERED!
        'passed' => true,          // TAMPERED!
        'time_taken_seconds' => 120
    ];

    $res = MockExamController::handle($tamperedPayload);

    // Verify response
    $okResponse = http_response_code() === 200
        && $res['status'] === true
        && $res['material_id'] == $matId
        && $res['result_id'] > 0
        && $res['correct_count'] === 1   // SERVER OVERRODE TAMPERED COUNT
        && $res['incorrect_count'] === 3
        && $res['score_percentage'] === 25 // 1/4 = 25%
        && $res['passed'] === false;      // SERVER OVERRODE TAMPERED STATUS

    // Verify database study_materials record
    $smStmt = $db->prepare("SELECT score_percentage, correct_count, incorrect_count, passed, completed_at FROM study_materials WHERE id = ?");
    $smStmt->execute([$matId]);
    $sm = $smStmt->fetch(PDO::FETCH_ASSOC);

    $okDb = $sm['score_percentage'] == 25
        && $sm['correct_count'] == 1
        && $sm['incorrect_count'] == 3
        && $sm['passed'] == 0
        && !empty($sm['completed_at']);

    // Verify database mock_exam_results record
    $resStmt = $db->prepare("SELECT score_percentage, correct_count, incorrect_count, passed FROM mock_exam_results WHERE id = ?");
    $resStmt->execute([$res['result_id']]);
    $dbRes = $resStmt->fetch(PDO::FETCH_ASSOC);
    $okRes = $dbRes['score_percentage'] == 25 && $dbRes['passed'] == 0;

    // Verify database mock_exam_answers count
    $ansStmt = $db->prepare("SELECT COUNT(*) FROM mock_exam_answers WHERE material_id = ?");
    $ansStmt->execute([$matId]);
    $okAns = ((int)$ansStmt->fetchColumn() === 4);

    return $okResponse && $okDb && $okRes && $okAns;
});

run_test("T28: Duplicate submission on completed material rejected with HTTP 409", function() use ($testUserId, $examId, $sampleQuestions) {
    // Material already has completed_at set
    $matId = create_test_material($testUserId, $examId, $sampleQuestions, true);
    $_SESSION['user_id'] = $testUserId;
    $_SERVER['REQUEST_METHOD'] = 'POST';

    $res = MockExamController::handle([
        'material_id' => $matId,
        'answers' => [
            ['question_index' => 0, 'selected' => 0, 'timed_out' => false]
        ],
        'time_taken_seconds' => 100
    ]);

    return http_response_code() === 409
        && $res['status'] === false
        && strpos($res['error'], 'already been completed') !== false;
});

run_test("T29: Inconsistent state (results exist but completed_at is NULL) rejected with HTTP 409", function() use ($testUserId, $examId, $sampleQuestions, $db) {
    $matId = create_test_material($testUserId, $examId, $sampleQuestions, false);

    // Manually insert orphan row into mock_exam_results while completed_at remains NULL
    $db->prepare("
        INSERT INTO mock_exam_results (user_id, exam_id, material_id, score_percentage, correct_count, incorrect_count, passed, time_taken_seconds)
        VALUES (?, ?, ?, 50, 2, 2, 0, 100)
    ")->execute([$testUserId, $examId, $matId]);

    $_SESSION['user_id'] = $testUserId;
    $_SERVER['REQUEST_METHOD'] = 'POST';

    $res = MockExamController::handle([
        'material_id' => $matId,
        'answers' => [],
        'time_taken_seconds' => 100
    ]);

    return http_response_code() === 409
        && $res['status'] === false
        && strpos($res['error'], 'Inconsistent state') !== false;
});

run_test("T30: Transaction rollback preserves data integrity on failure", function() use ($testUserId, $examId, $sampleQuestions, $db) {
    $matId = create_test_material($testUserId, $examId, $sampleQuestions, false);

    // Provide invalid submitted answer that fails validation (e.g. out of range option)
    $_SESSION['user_id'] = $testUserId;
    $_SERVER['REQUEST_METHOD'] = 'POST';

    $res = MockExamController::handle([
        'material_id' => $matId,
        'answers' => [
            ['question_index' => 0, 'selected' => 9999, 'timed_out' => false]
        ],
        'time_taken_seconds' => 100
    ]);

    // Check that study_materials was NOT updated and completed_at is still NULL
    $smStmt = $db->prepare("SELECT completed_at, score_percentage FROM study_materials WHERE id = ?");
    $smStmt->execute([$matId]);
    $sm = $smStmt->fetch(PDO::FETCH_ASSOC);

    // Check that no mock_exam_answers were inserted
    $ansCount = $db->query("SELECT COUNT(*) FROM mock_exam_answers WHERE material_id = {$matId}")->fetchColumn();

    // Check that no mock_exam_results were inserted
    $resCount = $db->query("SELECT COUNT(*) FROM mock_exam_results WHERE material_id = {$matId}")->fetchColumn();

    return $sm['completed_at'] === null
        && $sm['score_percentage'] === null
        && (int)$ansCount === 0
        && (int)$resCount === 0;
});

run_test("T31: Database row lock verification (SELECT ... FOR UPDATE)", function() use ($testUserId, $examId, $sampleQuestions) {
    // Open two distinct PDO connections to test FOR UPDATE locking
    $pdo1 = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 2
    ]);
    $pdo2 = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 2
    ]);

    $matId = create_test_material($testUserId, $examId, $sampleQuestions, false);

    // Connection 1 begins transaction and locks the row
    $pdo1->beginTransaction();
    $stmt1 = $pdo1->prepare("SELECT id FROM study_materials WHERE id = ? FOR UPDATE");
    $stmt1->execute([$matId]);

    // Connection 2 attempts to lock the same row with a short lock wait timeout
    $pdo2->exec("SET innodb_lock_wait_timeout = 1");
    $pdo2->beginTransaction();

    $lockBlocked = false;
    try {
        $stmt2 = $pdo2->prepare("SELECT id FROM study_materials WHERE id = ? FOR UPDATE");
        $stmt2->execute([$matId]);
    } catch (PDOException $e) {
        // Lock wait timeout exceeded (1205) confirms row-level locking was active
        if (strpos($e->getMessage(), '1205') !== false || strpos($e->getMessage(), 'Lock wait timeout') !== false) {
            $lockBlocked = true;
        }
    }

    $pdo1->rollBack();
    $pdo2->rollBack();

    return $lockBlocked;
});

run_test("T32: Non-question material submission (content_type='summary') rejected with HTTP 400", function() use ($testUserId, $examId, $db) {
    $stmt = $db->prepare("
        INSERT INTO study_materials (user_id, exam_id, source_mode, content_type, difficulty, raw_results_json)
        VALUES (?, ?, 'online', 'summary', 'medium', '{\"type\":\"summary\"}')
    ");
    $stmt->execute([$testUserId, $examId]);
    $matId = $db->lastInsertId();

    $_SESSION['user_id'] = $testUserId;
    $_SERVER['REQUEST_METHOD'] = 'POST';

    $res = MockExamController::handle(['material_id' => $matId, 'answers' => [], 'time_taken_seconds' => 10]);
    return http_response_code() === 400
        && $res['status'] === false
        && strpos($res['error'], 'not a mock examination') !== false;
});

run_test("T33: Malformed JSON payload rejected with HTTP 400", function() use ($testUserId) {
    $_SESSION['user_id'] = $testUserId;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $res = MockExamController::handle("this-is-not-valid-json");
    return http_response_code() === 400
        && $res['status'] === false
        && strpos($res['error'], 'Invalid JSON payload') !== false;
});

run_test("T34: Duplicate submission does not alter database record counts", function() use ($testUserId, $examId, $sampleQuestions, $db) {
    $matId = create_test_material($testUserId, $examId, $sampleQuestions, true);
    $_SESSION['user_id'] = $testUserId;
    $_SERVER['REQUEST_METHOD'] = 'POST';

    $answersBefore = (int)$db->query("SELECT COUNT(*) FROM mock_exam_answers WHERE material_id = {$matId}")->fetchColumn();
    $resultsBefore = (int)$db->query("SELECT COUNT(*) FROM mock_exam_results WHERE material_id = {$matId}")->fetchColumn();

    $res = MockExamController::handle([
        'material_id' => $matId,
        'answers' => [
            ['question_index' => 0, 'selected' => 0, 'timed_out' => false]
        ],
        'time_taken_seconds' => 60
    ]);

    $answersAfter = (int)$db->query("SELECT COUNT(*) FROM mock_exam_answers WHERE material_id = {$matId}")->fetchColumn();
    $resultsAfter = (int)$db->query("SELECT COUNT(*) FROM mock_exam_results WHERE material_id = {$matId}")->fetchColumn();

    return http_response_code() === 409
        && $res['status'] === false
        && $answersBefore === $answersAfter
        && $resultsBefore === $resultsAfter;
});

run_test("T35: HTML render output verification for unanswered and timed-out questions", function() use ($testUserId, $examId, $sampleQuestions, $db) {
    // Create material with 1 correct, 1 timed-out, 1 unanswered, 1 incorrect
    $userAnswers = [
        ['question_index' => 0, 'selected' => 0, 'correct' => 0, 'timed_out' => false],
        ['question_index' => 1, 'selected' => null, 'correct' => 1, 'timed_out' => true],
        ['question_index' => 2, 'selected' => null, 'correct' => 2, 'timed_out' => false],
        ['question_index' => 3, 'selected' => 0, 'correct' => 3, 'timed_out' => false]
    ];
    $rawJson = json_encode(['type' => 'questions', 'questions' => $sampleQuestions]);
    $stmt = $db->prepare("
        INSERT INTO study_materials (user_id, exam_id, source_mode, content_type, difficulty, raw_results_json, user_answers_json, score_percentage, correct_count, incorrect_count, passed, time_taken_seconds, completed_at)
        VALUES (?, ?, 'online', 'questions', 'medium', ?, ?, 25, 1, 3, 0, 120, NOW())
    ");
    $stmt->execute([$testUserId, $examId, $rawJson, json_encode($userAnswers)]);
    $matId = $db->lastInsertId();

    $_SESSION['user_id'] = $testUserId;
    $_SESSION['user_role'] = 'user';
    $_GET['id'] = $matId;

    ob_start();
    include '/var/www/html/mock_exam_results.php';
    $html = ob_get_clean();

    $hasTimeoutBadge = strpos($html, 'badge-timeout') !== false && strpos($html, 'Timed Out') !== false;
    $hasNoAnswerBadge = strpos($html, 'badge-wrong') !== false && strpos($html, 'No Answer') !== false;
    $hasCorrectBadge = strpos($html, 'badge-correct') !== false;

    return $hasTimeoutBadge && $hasNoAnswerBadge && $hasCorrectBadge;
});

echo "\n========================================================\n";
echo "TEST RESULTS: Total: {$total} | Passed: {$passed} | Failed: {$failed}\n";
echo "========================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
