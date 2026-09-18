<?php
// analytics.php - Analytics Dashboard (Tabbed)
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/models/Analytics.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Global/system stats (used in System Overview panel)
$stats        = Analytics::getGlobalStats();
$topExams     = Analytics::getTopExams();
$contentTypes = Analytics::getContentTypeStats();

// Per-user summary tables (fallback for users without JS or for quick table view)
$examStats      = Analytics::getUserExamStats($userId);
$flashcardStats = Analytics::getUserFlashcardStats($userId);
$studyStats     = Analytics::getUserStudyStats($userId);

require_once __DIR__ . '/views/layout/header.php';
require_once __DIR__ . '/views/layout/navbar.php';
?>

<div class="container results-container">
    <div class="flex-header">
        <div>
            <h2>Learning & System Analytics</h2>
            <small style="color: var(--text-muted);">
                Overview of your activity and platform usage
            </small>
        </div>
        <span class="badge badge-tech">Real-time Stats</span>
    </div>

    <div class="card results-card">
        <!-- Tabs -->
        <div class="analytics-tabs">
            <button class="tab-btn active" data-tab="tab-exams">📘 Mock Exams</button>
            <button class="tab-btn" data-tab="tab-flashcards">🧠 Flashcards</button>
            <button class="tab-btn" data-tab="tab-materials">📚 Study Materials</button>
            <button class="tab-btn" data-tab="tab-system">⚙️ System Overview</button>
        </div>

        <!-- TAB PANELS (JS will populate charts into these containers) -->
        <div id="tab-exams" class="tab-panel active" aria-live="polite">
            <!-- JS will render large, readable mock exam charts here.
                 A server-side table fallback is provided below for non-JS users. -->
            <div id="tab-exams-charts"></div>

            <?php if (!empty($examStats)): ?>
                <div style="margin-top: 1.5rem;">
                    <h4 style="margin-bottom: 0.75rem;">Mock Exam Attempts (table)</h4>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Exam</th>
                                    <th>Attempts</th>
                                    <th>Avg. Score (%)</th>
                                    <th>Passed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($examStats as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['exam_name']) ?></td>
                                        <td><?= (int)$row['attempts'] ?></td>
                                        <td><?= (float)$row['avg_score'] ?></td>
                                        <td><?= (int)$row['passed_count'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <p class="summary-box" style="margin-top:1rem;">
                    No mock exam activity yet. Complete a mock exam to see your performance analytics here.
                </p>
            <?php endif; ?>
        </div>

        <div id="tab-flashcards" class="tab-panel" aria-live="polite">
            <!-- JS will render flashcard totals and time-series charts here -->
            <div id="tab-flashcards-charts"></div>

            <?php if (!empty($flashcardStats)): ?>
                <?php
                $totalSessions  = array_sum(array_column($flashcardStats, 'sessions'));
                $totalCorrect   = array_sum(array_column($flashcardStats, 'correct'));
                $totalIncorrect = array_sum(array_column($flashcardStats, 'incorrect'));
                $totalAnswers   = max(1, $totalCorrect + $totalIncorrect);
                $accuracy       = round(($totalCorrect / $totalAnswers) * 100, 1);
                ?>
                <div class="analytics-grid" style="margin-top: 1.25rem;">
                    <div class="analytics-card">
                        <i class="fa-solid fa-bolt"></i>
                        <h4>Total Sessions</h4>
                        <p><?= (int)$totalSessions ?></p>
                    </div>
                    <div class="analytics-card">
                        <i class="fa-solid fa-check-circle"></i>
                        <h4>Accuracy</h4>
                        <p><?= $accuracy ?>%</p>
                    </div>
                    <div class="analytics-card">
                        <i class="fa-solid fa-brain"></i>
                        <h4>Total Answers</h4>
                        <p><?= (int)$totalAnswers ?></p>
                    </div>
                </div>

                <div style="margin-top: 1.5rem;">
                    <h4 style="margin-bottom: 0.75rem;">Flashcard Summary (table)</h4>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Exam</th>
                                    <th>Sessions</th>
                                    <th>Correct</th>
                                    <th>Incorrect</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($flashcardStats as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['exam_name']) ?></td>
                                        <td><?= (int)$row['sessions'] ?></td>
                                        <td><?= (int)$row['correct'] ?></td>
                                        <td><?= (int)$row['incorrect'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <p class="summary-box" style="margin-top:1rem;">
                    No flashcard activity yet. Flip at least one flashcard session to see your analytics here.
                </p>
            <?php endif; ?>
        </div>

        <div id="tab-materials" class="tab-panel" aria-live="polite">
            <!-- JS will render study material charts here -->
            <div id="tab-materials-charts"></div>

            <?php if (!empty($studyStats)): ?>
                <div style="margin-top: 1rem;">
                    <h4 style="margin-bottom: 0.75rem;">Study Materials (table)</h4>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Exam</th>
                                    <th>Content Type</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($studyStats as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['exam_name']) ?></td>
                                        <td><?= htmlspecialchars(ucfirst($row['content_type'])) ?></td>
                                        <td><?= (int)$row['count'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <p class="summary-box" style="margin-top:1rem;">
                    No study materials generated yet. Create materials to see your study focus and activity here.
                </p>
            <?php endif; ?>
        </div>

        <div id="tab-system" class="tab-panel" aria-live="polite">
            <h3 style="margin-bottom: 1rem;">System & Method Overview</h3>

            <div class="grid" style="margin-top: 1rem;">
                <div class="card" style="padding: 1.5rem; text-align: center;">
                    <h4 style="color: var(--text-muted);">Total Materials</h4>
                    <h1 style="font-size: 2.5rem; color: var(--accent); margin-top: 0.5rem;">
                        <?= (int)$stats['total_materials'] ?>
                    </h1>
                </div>
                <div class="card" style="padding: 1.5rem; text-align: center;">
                    <h4 style="color: var(--text-muted);">Flashcards Active</h4>
                    <h1 style="font-size: 2.5rem; color: var(--primary-hover); margin-top: 0.5rem;">
                        <?= (int)$stats['total_flashcards'] ?>
                    </h1>
                </div>
                <div class="card" style="padding: 1.5rem; text-align: center;">
                    <h4 style="color: var(--text-muted);">Mock Tests Taken</h4>
                    <h1 style="font-size: 2.5rem; color: var(--success); margin-top: 0.5rem;">
                        <?= (int)$stats['total_mock_tests'] ?>
                    </h1>
                </div>
            </div>

            <div style="margin-top: 1.5rem;">
                <h4 style="margin-bottom: 0.75rem;">Top Exams (by generated materials)</h4>
                <?php if (!empty($topExams)): ?>
                    <ul style="list-style:none; padding-left:0;">
                        <?php foreach ($topExams as $e): ?>
                            <li style="padding:0.5rem 0; border-bottom:1px solid var(--glass-border);">
                                <?= htmlspecialchars($e['name']) ?> — <strong><?= (int)$e['count'] ?></strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="summary-box">No exam generation activity yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js and analytics script (loaded after DOM content) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/assets/js/analytics_charts.js"></script>

<?php require_once __DIR__ . '/views/layout/footer.php'; ?>
