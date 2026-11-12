<?php
// system-status.php - Administrator System Status Page
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/config/db.php';

// Enforce administrator authentication
AdminController::checkAdminAuth();

// 1. PHP Web Application Check
$phpStatus  = 'Online';
$phpDetails = 'Apache and PHP are running';
$phpOk      = true;

// 2. MySQL Database Connection Check
$dbStatus   = 'Failed';
$dbDetails  = 'Database connection failed';
$dbOk       = false;
$db         = null;

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 3,
    ];
    $db = new PDO($dsn, DB_USER, DB_PASS, $options);
    $stmt = $db->query("SELECT 1");
    if ($stmt && $stmt->fetchColumn() == 1) {
        $dbStatus  = 'Connected';
        $dbDetails = 'Database connection successful';
        $dbOk      = true;
    }
} catch (Throwable $e) {
    error_log("System Status: Database connection check failed: " . $e->getMessage());
    $dbStatus  = 'Failed';
    $dbDetails = 'Database connection failed';
    $dbOk      = false;
    $db        = null;
}

// 3. Required Database Tables Check
$tablesStatus  = 'Missing';
$tablesDetails = 'Required tables missing';
$tablesOk      = false;

if ($dbOk && $db !== null) {
    try {
        $requiredTables = [
            'users',
            'exams',
            'study_materials',
            'analytics_log',
            'mock_exam_results',
            'flashcard_stats',
            'mock_exam_answers'
        ];

        $stmt = $db->query("SHOW TABLES");
        $existingTables = [];
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $existingTables[] = strtolower($row[0]);
        }

        $missingTables = [];
        foreach ($requiredTables as $table) {
            if (!in_array(strtolower($table), $existingTables, true)) {
                $missingTables[] = $table;
            }
        }

        if (empty($missingTables)) {
            $tablesStatus  = 'Available';
            $tablesDetails = '7 required tables detected';
            $tablesOk      = true;
        } else {
            $foundCount = count($requiredTables) - count($missingTables);
            $tablesStatus  = 'Missing';
            $tablesDetails = $foundCount . ' of 7 required tables detected';
            $tablesOk      = false;
            error_log("System Status: Missing tables: " . implode(', ', $missingTables));
        }
    } catch (Throwable $e) {
        error_log("System Status: Table existence check failed: " . $e->getMessage());
        $tablesStatus  = 'Missing';
        $tablesDetails = 'Unable to verify tables';
        $tablesOk      = false;
    }
}

// 4. FastAPI AI Service Check
$fastApiStatus  = 'Unavailable';
$fastApiDetails = 'FastAPI service unavailable';
$fastApiOk      = false;

$backendUrl = defined('AI_BACKEND_URL') ? AI_BACKEND_URL : 'http://ai-backend:8000/generate';
$healthUrl  = preg_replace('#/generate/?$#', '/health', $backendUrl);
if ($healthUrl === $backendUrl) {
    $healthUrl = rtrim(dirname($backendUrl), '/') . '/health';
}

$ch = curl_init($healthUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 5,
    CURLOPT_CONNECTTIMEOUT => 3,
    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    error_log("System Status: FastAPI health check cURL error: " . $curlErr);
}

if ($httpCode === 200 && $response !== false) {
    $data = json_decode($response, true);
    if (is_array($data) && isset($data['status']) && $data['status'] === 'ok') {
        $fastApiStatus  = 'Online';
        $fastApiDetails = 'Service reachable';
        $fastApiOk      = true;
    }
}

// 5. OpenAI API Key Configuration Check
$openAiStatus  = 'Missing';
$openAiDetails = 'OpenAI API key missing';
$openAiOk      = false;

$openAiKey = getenv('OPENAI_API_KEY');
if (empty($openAiKey) && !empty($_ENV['OPENAI_API_KEY'])) {
    $openAiKey = $_ENV['OPENAI_API_KEY'];
}
if (empty($openAiKey) && !empty($_SERVER['OPENAI_API_KEY'])) {
    $openAiKey = $_SERVER['OPENAI_API_KEY'];
}
if (empty($openAiKey)) {
    $envCandidatePaths = [
        __DIR__ . '/.env',
        dirname(__DIR__) . '/.env'
    ];
    foreach ($envCandidatePaths as $envPath) {
        if (file_exists($envPath) && is_readable($envPath)) {
            $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines) {
                foreach ($lines as $line) {
                    $trimmed = trim($line);
                    if (strpos($trimmed, '#') === 0) continue;
                    if (strpos($trimmed, 'OPENAI_API_KEY=') === 0) {
                        $candidate = trim(substr($trimmed, 15), " \t\n\r\0\x0B\"'");
                        if (!empty($candidate)) {
                            $openAiKey = $candidate;
                            break 2;
                        }
                    }
                }
            }
        }
    }
}

if (!empty($openAiKey)) {
    $openAiStatus  = 'Configured';
    $openAiDetails = 'Environment variable present';
    $openAiOk      = true;
}

// 6. Upload Directory Check
$uploadStatus  = 'Not writable';
$uploadDetails = 'Upload directory not writable';
$uploadOk      = false;

$uploadDir = defined('UPLOAD_DIR') ? UPLOAD_DIR : (__DIR__ . '/uploads/');
if (is_dir($uploadDir) && is_writable($uploadDir)) {
    $testFile = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'status_test_' . uniqid('', true) . '.tmp';
    $written  = @file_put_contents($testFile, 'status_check');
    if ($written !== false) {
        @unlink($testFile);
        $uploadStatus  = 'Writable';
        $uploadDetails = 'File storage available';
        $uploadOk      = true;
    } else {
        error_log("System Status: Upload directory test file write failed");
    }
} else {
    error_log("System Status: Upload directory not found or not writable");
}

// 7. Overall Docker Service Status
$isDockerRunning = ($dbOk && $tablesOk && $fastApiOk && $openAiOk && $uploadOk);
$dockerStatus    = $isDockerRunning ? 'Running' : 'Degraded';
$dockerDetails   = $isDockerRunning ? 'Core services reachable' : 'One or more services unavailable';
$dockerOk        = $isDockerRunning;

$checkedAt = date('Y-m-d H:i:s T');

$components = [
    [
        'name'    => 'PHP web application',
        'status'  => $phpStatus,
        'details' => $phpDetails,
        'is_ok'   => $phpOk
    ],
    [
        'name'    => 'MySQL database',
        'status'  => $dbStatus,
        'details' => $dbDetails,
        'is_ok'   => $dbOk
    ],
    [
        'name'    => 'Required tables',
        'status'  => $tablesStatus,
        'details' => $tablesDetails,
        'is_ok'   => $tablesOk
    ],
    [
        'name'    => 'FastAPI AI service',
        'status'  => $fastApiStatus,
        'details' => $fastApiDetails,
        'is_ok'   => $fastApiOk
    ],
    [
        'name'    => 'OpenAI API key',
        'status'  => $openAiStatus,
        'details' => $openAiDetails,
        'is_ok'   => $openAiOk
    ],
    [
        'name'    => 'Upload directory',
        'status'  => $uploadStatus,
        'details' => $uploadDetails,
        'is_ok'   => $uploadOk
    ],
    [
        'name'    => 'Docker services',
        'status'  => $dockerStatus,
        'details' => $dockerDetails,
        'is_ok'   => $dockerOk
    ],
];

require_once __DIR__ . '/views/layout/header.php';
require_once __DIR__ . '/views/layout/navbar.php';
?>

<div class="container" style="max-width: 950px; margin-top: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2>System Status</h2>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin-top: 0.25rem;">Live status of the core application services.</p>
        </div>
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <a href="/admin-dashboard.php" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                <i class="fa-solid fa-users"></i> Users
            </a>
            <a href="/admin-exams.php" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                <i class="fa-solid fa-book"></i> Library
            </a>
            <a href="/admin-logs.php" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                <i class="fa-solid fa-list-check"></i> Logs
            </a>
            <a href="/system-status.php" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                <i class="fa-solid fa-heart-pulse"></i> Status
            </a>
        </div>
    </div>

    <div class="card" style="padding: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h3 style="margin-bottom: 0.25rem;">
                    <i class="fa-solid fa-server" style="color: var(--accent);"></i> Component Diagnostics
                </h3>
                <small style="color: var(--text-muted);">
                    <i class="fa-solid fa-clock"></i> Last checked: <?= htmlspecialchars($checkedAt) ?>
                </small>
            </div>
            <a href="/system-status.php" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.85rem;">
                <i class="fa-solid fa-arrows-rotate"></i> Refresh Status
            </a>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 32%;">Component</th>
                        <th style="width: 25%;">Status</th>
                        <th style="width: 43%;">Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($components as $c): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($c['name']) ?></strong>
                            </td>
                            <td>
                                <span class="badge <?= $c['is_ok'] ? 'badge-correct' : 'badge-wrong' ?>">
                                    <i class="fa-solid <?= $c['is_ok'] ? 'fa-check' : 'fa-xmark' ?>" style="margin-right: 0.35rem;"></i>
                                    <?= htmlspecialchars($c['status']) ?>
                                </span>
                            </td>
                            <td style="color: <?= $c['is_ok'] ? 'inherit' : 'var(--error)' ?>;">
                                <?= htmlspecialchars($c['details']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/layout/footer.php'; ?>
