<?php
// results.php - View Revision Materials
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/models/Material.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$materialId = $_GET['id'] ?? null;
$material = Material::findById($materialId);

if (!$material || ($material['user_id'] != $_SESSION['user_id'] && $_SESSION['user_role'] !== 'admin')) {
    die("Unauthorized access or material not found.");
}

$data = json_decode($material['raw_results_json'], true);

require_once __DIR__ . '/views/layout/header.php';
require_once __DIR__ . '/views/layout/navbar.php';
?>
<div class="container">
    <div class="flex-header">
        <div>
            <h2><?= htmlspecialchars($material['exam_code'] . ' - ' . $material['exam_name']) ?></h2>
            <small style="color: var(--text-muted);">Mode: <?= ucfirst($material['source_mode']) ?> • Difficulty: <?= $material['difficulty'] ?></small>
        </div>
        <a href="/dashboard.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <div class="card">

    <!-- ========================= SUMMARY ========================= -->
<?php if ($material['content_type'] === 'summary'): ?>
    <h3 style="margin-bottom: 1.5rem;"><i class="fa-solid fa-list" style="color: var(--accent);"></i> Summary</h3>

    <?php if (isset($data['summary']) && is_array($data['summary']) && count($data['summary']) > 0): ?>
        <div class="summary-box">
            <?php foreach ($data['summary'] as $section): ?>
                <?php if (!isset($section['topic']) || !isset($section['points']) || !is_array($section['points'])) continue; ?>
                <div style="margin-bottom: 2rem;">
                    <h3 style="color: var(--primary-hover); font-size: 1.15rem; margin-bottom: 1rem; display:flex; align-items:center; gap:0.6rem;">
                        <i class="fa-solid fa-book-open" style="color: var(--accent); font-size:1rem;"></i>
                        <?= htmlspecialchars($section['topic']) ?>
                    </h3>
                    <?php foreach ($section['points'] as $index => $point): ?>
                        <div style="display:flex; align-items:flex-start; gap:1rem; padding:0.75rem 0; border-bottom: 1px solid var(--glass-border);">
                            <span style="min-width:28px; height:28px; border-radius:50%; background: linear-gradient(135deg, var(--accent), var(--primary)); display:flex; align-items:center; justify-content:center; font-weight:600; font-size:0.8rem; flex-shrink:0;">
                                <?= $index + 1 ?>
                            </span>
                            <p style="margin:0; line-height:1.7;"><?= htmlspecialchars($point) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="color: var(--text-muted);">No summary data available.</p>
    <?php endif; ?>


    <!-- ========================= FLASHCARDS ========================= -->
    <?php elseif ($material['content_type'] === 'flashcards'): ?>
        <h3 style="margin-bottom: 2rem; text-align: center;">
            <i class="fa-solid fa-clone" style="color: var(--accent);"></i> Interactive Flashcards
        </h3>

        <div class="grid">
            <?php if (isset($data['flashcards']) && is_array($data['flashcards'])): ?>
                <?php foreach ($data['flashcards'] as $index => $card): ?>
                    <div class="flashcard-wrapper">
                        <div class="flashcard">
                            <div class="flashcard-front">
                                <small style="color: var(--accent); margin-bottom: 1rem;">Question <?= $index + 1 ?></small>
                                <h3><?= htmlspecialchars($card['question']) ?></h3>
                            </div>
                            <div class="flashcard-back">
                                <small style="color: var(--primary-hover); margin-bottom: 1rem;">Answer</small>
                                <p><?= htmlspecialchars($card['answer']) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No flashcard data available.</p>
            <?php endif; ?>
        </div>

        <script>
            window.materialId = <?= (int)$materialId ?>;
        </script>
        <script src="/assets/js/flashcards.js"></script>

    <!-- ========================= QUESTIONS (INTERACTIVE MOCK EXAM) ========================= -->
    <?php elseif ($material['content_type'] === 'questions'): ?>
        <h3 style="margin-bottom: 2rem;">
            <i class="fa-solid fa-clipboard-question" style="color: var(--success);"></i> Mock Exam
        </h3>

        <script>
            const examQuestions = <?= json_encode($data['questions']); ?>;
            window.materialId = <?= (int)$materialId ?>;
        </script>

        <div id="mock-exam-container" class="mock-exam-container" style="max-width: 700px; margin-top: 3rem;">
            <div id="question-box" class="question-box"></div>
            <div class="exam-controls">
                <button id="prev-btn" class="btn btn-secondary">Previous</button>
                <button id="next-btn" class="btn btn-primary">Next</button>
                <button id="finish-btn" class="btn btn-success">Finish Exam</button>
            </div>
            <div id="timer-box" class="timer-box">
                <span id="timer-value">90</span> seconds remaining
            </div>
        </div>

        <script src="/assets/js/mock_exam.js"></script>
        <script src="/assets/js/flashcards.js"></script>

    <?php endif; ?>

</div>
</div>

<script src="/assets/js/app.js"></script>

<?php require_once __DIR__ . '/views/layout/footer.php'; ?>
