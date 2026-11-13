<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/models/Material.php';
require_once __DIR__ . '/models/Exam.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$materialId = $_GET['id'] ?? null;
$material = Material::findById($materialId);

if (!$material || ($material['user_id'] != $_SESSION['user_id'] && $_SESSION['user_role'] !== 'admin')) {
    die("Unauthorized access or material not found.");
}

$userAnswers = json_decode($material['user_answers_json'] ?? '[]', true) ?: [];
$rawDecoded = json_decode($material['raw_results_json'] ?? '[]', true);
$rawQuestions = (is_array($rawDecoded) && isset($rawDecoded['questions']) && is_array($rawDecoded['questions'])) ? $rawDecoded['questions'] : [];

require_once __DIR__ . '/views/layout/header.php';
require_once __DIR__ . '/views/layout/navbar.php';
?>

<div class="container results-container">

    <!-- HEADER -->
    <div class="flex-header">
        <div>
            <h2><?= htmlspecialchars($material['exam_code'] . ' - ' . $material['exam_name']) ?></h2>
            <small style="color: var(--text-muted);">
                Mock Exam Results • Difficulty: <?= $material['difficulty'] ?>
            </small>
        </div>
        <a href="/dashboard.php" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div class="card results-card">

        <!-- SCORE CHART -->
        <div class="score-chart-section">
            <canvas id="scoreChart" width="160" height="160"></canvas>
            <div class="score-label-center">
                <h2><?= $material['score_percentage'] ?>%</h2>
                <small><?= $material['passed'] ? 'Passed' : 'Failed' ?></small>
            </div>
        </div>

        <!-- ANALYTICS GRID -->
        <div class="analytics-grid">
            <div class="analytics-card">
                <i class="fa-solid fa-check-circle"></i>
                <h4><?= $material['correct_count'] ?></h4>
                <small>Correct Answers</small>
            </div>
            <div class="analytics-card">
                <i class="fa-solid fa-xmark-circle"></i>
                <h4><?= $material['incorrect_count'] ?></h4>
                <small>Incorrect Answers</small>
            </div>
            <div class="analytics-card">
                <i class="fa-solid fa-clock"></i>
                <h4><?= $material['time_taken_seconds'] ?>s</h4>
                <small>Time Taken</small>
            </div>
        </div>

        <hr style="margin: 2rem 0;">

        <!-- QUESTION BREAKDOWN -->
        <h3><i class="fa-solid fa-list-check" style="color: var(--accent);"></i> Question Breakdown</h3>

        <div class="question-grid">
            <?php foreach ($rawQuestions as $index => $q): ?>
                <?php
                    $ans = $userAnswers[$index] ?? null;
                    $timedOut = !empty($ans['timed_out']);
                    $selectedOpt = $ans['selected'] ?? null;
                    $expectedCorrect = $q['correct_index'] ?? ($ans['correct'] ?? null);
                    $isCorrect = ($ans !== null && !$timedOut && $selectedOpt !== null && $selectedOpt === $expectedCorrect);
                ?>
                <div class="question-card <?= $isCorrect ? 'correct' : 'incorrect' ?>">
                    <h4><?= $index + 1 ?>. <?= htmlspecialchars($q['question'] ?? '') ?></h4>
                    <p><strong>Your Answer:</strong>
                        <?php if ($timedOut): ?>
                            <span class="badge badge-timeout">Timed Out</span>
                        <?php elseif ($selectedOpt === null): ?>
                            <span class="badge badge-wrong">No Answer</span>
                        <?php else: ?>
                            <span class="badge <?= $isCorrect ? 'badge-correct' : 'badge-wrong' ?>">
                                <?= htmlspecialchars($q['options'][$selectedOpt] ?? ('Option ' . $selectedOpt)) ?>
                            </span>
                        <?php endif; ?>
                    </p>
                    <p><strong>Correct Answer:</strong>
                        <span class="badge badge-correct">
                            <?= htmlspecialchars($q['options'][$q['correct_index'] ?? 0] ?? 'N/A') ?>
                        </span>
                    </p>
                    <p class="explanation">
                        <strong>Explanation:</strong> <?= htmlspecialchars($q['explanation'] ?? '') ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/assets/js/results_chart.js"></script>

<?php require_once __DIR__ . '/views/layout/footer.php'; ?>
