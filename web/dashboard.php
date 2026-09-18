<?php
// dashboard.php - User Dashboard (Learning Hub)
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/ExamController.php';
require_once __DIR__ . '/controllers/AIController.php';
require_once __DIR__ . '/models/Material.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$exams = ExamController::listExams();
$history = Material::getByUser($userId);
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $examId        = $_POST['exam_id'];
    $sourceMode    = $_POST['source_mode'];
    $contentType   = $_POST['content_type'];
    $difficulty    = $_POST['difficulty'] ?? 'Medium';
    $flashCount    = $_POST['flashcard_count'] ?? null;
    $questionCount = $_POST['question_count'] ?? null;
    $filePath      = null;


    if ($sourceMode === 'upload') {
        $uploadResult = ExamController::handleUpload($_FILES['study_doc']);
        if (!$uploadResult['status']) {
            $error = $uploadResult['error'];
        } else {
            $filePath = $uploadResult['path'];
        }
    }

    if ($error === null) {
        $res = AIController::generate(
    $userId,
    $examId,
    $sourceMode,
    $filePath,
    $contentType,
    $difficulty,
    $flashCount,
    $questionCount
);

        if ($res['status']) {
            header("Location: /results.php?id=" . $res['id']);
            exit;
        } else {
            $error = $res['error'];
        }
    }
}

require_once __DIR__ . '/views/layout/header.php';
require_once __DIR__ . '/views/layout/navbar.php';
?>

<div class="container">
    <div class="flex-header">
    <h2>Welcome, <?= htmlspecialchars($_SESSION['user_email']) ?></h2>

    <div style="display: flex; gap: 1rem; align-items: center;">
        <span class="badge badge-tech">Revision Hub</span>

        <!-- Analytics Button -->
        <a href="/analytics.php" class="btn btn-secondary" 
           style="padding: 0.6rem 1.2rem; font-size: 0.9rem;">
            <i class="fa-solid fa-chart-line" style="margin-right: 0.4rem;"></i>
            Analytics
        </a>
    </div>
</div>


    <?php if ($error): ?>
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid var(--error); color: #fca5a5; padding: 0.75rem; border-radius: 8px; margin-bottom: 2rem;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="grid" style="margin-top: 1rem;">
        <div class="card" style="grid-column: span 2;">
            <h3 style="margin-bottom: 1.5rem;"><i class="fa-solid fa-wand-magic-sparkles" style="color: var(--accent);"></i> Generate Revision Material</h3>
            <form method="POST" enctype="multipart/form-data">

    <!-- Select Exam -->
    <div class="form-group">
        <label for="exam_id">Select Exam</label>
        <select name="exam_id" id="exam_id" class="form-control" required>
            <?php 
            $currentCategory = '';
            foreach ($exams as $exam): 
                if ($currentCategory !== $exam['category']):
                    if ($currentCategory !== '') echo '</optgroup>';
                    $currentCategory = $exam['category'];
                    echo '<optgroup label="' . htmlspecialchars($currentCategory) . '">';
                endif;
            ?>
                <option value="<?= $exam['id'] ?>"><?= htmlspecialchars($exam['code'] . ' - ' . $exam['name']) ?></option>
            <?php endforeach; if ($currentCategory !== '') echo '</optgroup>'; ?>
        </select>
    </div>

    <!-- Source Mode -->
    <div class="form-group" style="display: flex; gap: 2rem; margin: 1.5rem 0;">
        <label style="display: inline-flex; align-items: center; cursor: pointer;">
            <input type="radio" name="source_mode" id="mode-online" value="online" checked style="margin-right: 0.5rem; accent-color: var(--accent);"> Use Online Sources
        </label>
        <label style="display: inline-flex; align-items: center; cursor: pointer;">
            <input type="radio" name="source_mode" id="mode-upload" value="upload" style="margin-right: 0.5rem; accent-color: var(--accent);"> Upload Study File
        </label>
    </div>

    <!-- Upload Container -->
    <div class="form-group" id="upload-container" style="display: none;">
        <label for="study_doc">Upload Document (PDF, TXT, DOCX - Max 5MB)</label>
        <input type="file" name="study_doc" id="study_doc" class="form-control">
    </div>

    <!-- Learning Method + Difficulty -->
    <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 0;">
        <div class="form-group">
            <label for="content_type">Learning Method</label>
            <select name="content_type" id="content_type" class="form-control" required>
                <option value="summary">Generate Summary Guide</option>
                <option value="flashcards">Generate Interactive Flashcards</option>
                <option value="questions">Generate Mock Test Questions</option>
            </select>
        </div>

        <!-- Difficulty (only for questions) -->
        <div class="form-group" id="difficulty-group" style="display:none;">
            <label for="difficulty">Difficulty Level</label>
            <select name="difficulty" id="difficulty" class="form-control">
                <option value="Easy">Easy</option>
                <option value="Medium" selected>Medium</option>
                <option value="Hard">Hard</option>
            </select>
        </div>
    </div>

    <!-- Flashcard Count -->
    <div class="form-group" id="flashcard-count-group" style="display:none; margin-top: 1rem;">
        <label for="flashcard_count">Number of Flashcards</label>
        <select id="flashcard_count" name="flashcard_count" class="form-control">
            <option value="5">5 Flashcards</option>
            <option value="10">10 Flashcards</option>
            <option value="15">15 Flashcards</option>
            <option value="20">20 Flashcards</option>
        </select>
    </div>

    <!-- Question Count -->
    <div class="form-group" id="question-count-group" style="display:none; margin-top: 1rem;">
        <label for="question_count">Number of Questions</label>
        <select id="question_count" name="question_count" class="form-control">
            <option value="5">5 Questions</option>
            <option value="10">10 Questions</option>
            <option value="15">15 Questions</option>
            <option value="20">20 Questions</option>
        </select>
    </div>

    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem;">
        <i class="fa-solid fa-gears"></i> Process Study Material
    </button>
</form>

        </div>

        <div class="card">
            <h3 style="margin-bottom: 1.5rem;"><i class="fa-solid fa-clock-rotate-left" style="color: var(--primary-hover);"></i> History</h3>
            <div style="max-height: 380px; overflow-y: auto; padding-right: 0.5rem;">
                <?php if (empty($history)): ?>
                    <p style="color: var(--text-muted); font-size: 0.9rem; text-align: center; margin-top: 3rem;">No materials yet.</p>
                <?php else: foreach ($history as $item): ?>
                    <div style="border-bottom: 1px solid var(--glass-border); padding: 0.8rem 0; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <a href="/results.php?id=<?= $item['id'] ?>" style="color: #fff; text-decoration: none; font-weight: 500; font-size: 0.95rem; display: block;">
                                <?= htmlspecialchars($item['exam_code']) ?> (<?= ucfirst($item['content_type']) ?>)
                            </a>
                            <small style="color: var(--text-muted); font-size: 0.8rem;"><?= htmlspecialchars($item['created_at']) ?></small>
                        </div>
                        <i class="fa-solid fa-chevron-right" style="font-size: 0.8rem; color: var(--text-muted);"></i>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="assets/js/app.js"></script>
<?php require_once __DIR__ . '/views/layout/footer.php'; ?>
