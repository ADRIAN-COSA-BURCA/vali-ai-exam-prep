<?php
// index.php - Main Landing Page
require_once __DIR__ . '/views/layout/header.php';
require_once __DIR__ . '/views/layout/navbar.php';
?>
<div class="container hero">
    <h1>Supercharge Your Revision with <span style="background: linear-gradient(135deg, var(--accent), var(--primary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">PrepAI</span></h1>
    <p>Automatically generate summaries, interactive flashcards, and mock tests customized to your exams. Powered by Azure AI and local persistence.</p>
    <div>
        <a href="/dashboard.php" class="btn btn-primary"><i class="fa-solid fa-bolt"></i> Start Preparing Now</a>
        <a href="/login.php" class="btn btn-secondary" style="margin-left: 1rem;"><i class="fa-solid fa-right-to-bracket"></i> Sign In</a>
    </div>
</div>


<!-- ============ NEW SECTION 2: 3-Step Process ============ -->
<div class="container" style="text-align:center;">
    <h2 style="font-size:2.4rem; margin-bottom:0.75rem;">
        Prepare With <span style="background: linear-gradient(135deg, var(--accent), var(--primary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">AI-Generated Practice</span>
    </h2>
    <p style="color:var(--text-muted); max-width:650px; margin:0 auto 3.5rem; font-size:1.05rem;">
        Generate a full mock exam tailored to your certification with realistic, AI-crafted questions. Test your knowledge and see if you're ready to pass.
    </p>
    <div style="position:relative; display:grid; grid-template-columns: repeat(3, 1fr); gap:2rem; margin-bottom:1rem;">
        <div style="position:absolute; top:34px; left:16.6%; right:16.6%; height:2px; background:linear-gradient(90deg, var(--accent), var(--primary)); z-index:0;"></div>
        <div style="position:relative; z-index:1;">
            <div style="width:68px; height:68px; border-radius:50%; margin:0 auto 1.25rem; background:linear-gradient(135deg, var(--accent), var(--primary)); display:flex; align-items:center; justify-content:center; box-shadow:0 4px 20px rgba(139,92,246,0.4);">
                <i class="fa-solid fa-file-arrow-up" style="color:#fff; font-size:1.4rem;"></i>
            </div>
            <h3 style="margin-bottom:0.5rem; font-size:1.15rem;">Select or Upload Content</h3>
            <p style="color:var(--text-muted); font-size:0.92rem; line-height:1.6;">Choose an exam from the library or upload your own study material to generate content from.</p>
        </div>
        <div style="position:relative; z-index:1;">
            <div style="width:68px; height:68px; border-radius:50%; margin:0 auto 1.25rem; background:linear-gradient(135deg, var(--accent), var(--primary)); display:flex; align-items:center; justify-content:center; box-shadow:0 4px 20px rgba(139,92,246,0.4);">
                <i class="fa-solid fa-clock" style="color:#fff; font-size:1.4rem;"></i>
            </div>
            <h3 style="margin-bottom:0.5rem; font-size:1.15rem;">Answer Real Exam-Style Questions</h3>
            <p style="color:var(--text-muted); font-size:0.92rem; line-height:1.6;">Practice with timed, multiple-choice questions matching the real exam's format and difficulty.</p>
        </div>
        <div style="position:relative; z-index:1;">
            <div style="width:68px; height:68px; border-radius:50%; margin:0 auto 1.25rem; background:linear-gradient(135deg, var(--accent), var(--primary)); display:flex; align-items:center; justify-content:center; box-shadow:0 4px 20px rgba(139,92,246,0.4);">
                <i class="fa-solid fa-square-check" style="color:#fff; font-size:1.4rem;"></i>
            </div>
            <h3 style="margin-bottom:0.5rem; font-size:1.15rem;">See Your Results Instantly</h3>
            <p style="color:var(--text-muted); font-size:0.92rem; line-height:1.6;">Get your score immediately with detailed analytics showing where to focus your revision.</p>
        </div>
    </div>
</div>

<!-- ============ NEW SECTION 1: Why Choose PrepAI ============ -->
<div class="container">
    <div style="display:grid; grid-template-columns: 1fr 1.1fr; gap:2.5rem; align-items:start;">
        <!-- Left: Title + Checklist card -->
        <div>
            <h2 style="font-size:2.4rem; margin-bottom:1.5rem; line-height:1.15;">
                Why Choose <span style="background: linear-gradient(135deg, var(--accent), var(--primary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">PrepAI</span>?
            </h2>
            <div class="card">
                <h3 style="margin-bottom:1.5rem; color: var(--text);">Everything You Need to Pass</h3>
                <ul style="list-style:none; display:flex; flex-direction:column; gap:1rem;">
                    <li style="display:flex; align-items:flex-start; gap:0.75rem;">
                        <span style="min-width:22px; height:22px; border-radius:50%; background:var(--accent); display:flex; align-items:center; justify-content:center; margin-top:2px;"><i class="fa-solid fa-check" style="font-size:0.7rem; color:#fff;"></i></span>
                        <span style="color:var(--text-muted);">Content generated fresh, tailored to your chosen exam and difficulty</span>
                    </li>
                    <li style="display:flex; align-items:flex-start; gap:0.75rem;">
                        <span style="min-width:22px; height:22px; border-radius:50%; background:var(--accent); display:flex; align-items:center; justify-content:center; margin-top:2px;"><i class="fa-solid fa-check" style="font-size:0.7rem; color:#fff;"></i></span>
                        <span style="color:var(--text-muted);">Detailed explanations for every mock exam question</span>
                    </li>
                    <li style="display:flex; align-items:flex-start; gap:0.75rem;">
                        <span style="min-width:22px; height:22px; border-radius:50%; background:var(--accent); display:flex; align-items:center; justify-content:center; margin-top:2px;"><i class="fa-solid fa-check" style="font-size:0.7rem; color:#fff;"></i></span>
                        <span style="color:var(--text-muted);">Realistic, timed mock exam experience</span>
                    </li>
                    <li style="display:flex; align-items:flex-start; gap:0.75rem;">
                        <span style="min-width:22px; height:22px; border-radius:50%; background:var(--accent); display:flex; align-items:center; justify-content:center; margin-top:2px;"><i class="fa-solid fa-check" style="font-size:0.7rem; color:#fff;"></i></span>
                        <span style="color:var(--text-muted);">Progress tracking and learning analytics dashboard</span>
                    </li>
                    <li style="display:flex; align-items:flex-start; gap:0.75rem;">
                        <span style="min-width:22px; height:22px; border-radius:50%; background:var(--accent); display:flex; align-items:center; justify-content:center; margin-top:2px;"><i class="fa-solid fa-check" style="font-size:0.7rem; color:#fff;"></i></span>
                        <span style="color:var(--text-muted);">Upload your own notes or rely on curated online sources</span>
                    </li>
                </ul>
            </div>
        </div>
        <!-- Right: Feature cards -->
        <div style="display:flex; flex-direction:column; gap:1.5rem;">
            <div class="card" style="display:flex; gap:1.25rem; align-items:flex-start; padding:1.5rem;">
                <div style="min-width:48px; height:48px; border-radius:50%; background:var(--primary); display:flex; align-items:center; justify-content:center;">
                    <i class="fa-solid fa-robot" style="color:#fff; font-size:1.1rem;"></i>
                </div>
                <div>
                    <h3 style="margin-bottom:0.4rem; font-size:1.1rem;">Trusted AI-Generated Content</h3>
                    <p style="color:var(--text-muted); font-size:0.95rem; line-height:1.6;">Every summary, flashcard, and question is generated on demand using GPT-4o-mini, ensuring fresh and unique study material every time.</p>
                </div>
            </div>
            <div class="card" style="display:flex; gap:1.25rem; align-items:flex-start; padding:1.5rem;">
                <div style="min-width:48px; height:48px; border-radius:50%; background:var(--accent); display:flex; align-items:center; justify-content:center;">
                    <i class="fa-solid fa-shield-halved" style="color:#fff; font-size:1.1rem;"></i>
                </div>
                <div>
                    <h3 style="margin-bottom:0.4rem; font-size:1.1rem;">Built for Real Certification Exams</h3>
                    <p style="color:var(--text-muted); font-size:0.95rem; line-height:1.6;">Starting with Microsoft Azure Fundamentals (AZ-900), our platform is designed to expand across multiple professional certifications.</p>
                </div>
            </div>
            <div class="card" style="display:flex; gap:1.25rem; align-items:flex-start; padding:1.5rem;">
                <div style="min-width:48px; height:48px; border-radius:50%; background:var(--success); display:flex; align-items:center; justify-content:center;">
                    <i class="fa-solid fa-chart-simple" style="color:#fff; font-size:1.1rem;"></i>
                </div>
                <div>
                    <h3 style="margin-bottom:0.4rem; font-size:1.1rem;">Learner-Focused Platform</h3>
                    <p style="color:var(--text-muted); font-size:0.95rem; line-height:1.6;">A fully containerized, secure, and reliable platform that keeps track of your revision progress and performance over time.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="grid">
        <div class="card">
            <h3 style="margin-bottom: 1rem; color: var(--accent);"><i class="fa-solid fa-file-arrow-up"></i> Upload Study Guides</h3>
            <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.6;">
                Upload PDF, DOCX, or TXT study resources. PrepAI parses the document and extracts core concepts to craft customized learning aids.
            </p>
        </div>
        <div class="card">
            <h3 style="margin-bottom: 1rem; color: var(--primary-hover);"><i class="fa-solid fa-globe"></i> Online Sources Only</h3>
            <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.6;">
                Don't have notes? No problem. Enter your exam title, and our hybrid Azure AI Search layer compiles authoritative curriculum points from trusted sources.
            </p>
        </div>
        <div class="card">
            <h3 style="margin-bottom: 1rem; color: var(--success);"><i class="fa-solid fa-chart-line"></i> Deep Learning Analytics</h3>
            <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.6;">
                Track your active progress across exam libraries, review generated material catalogs, and view charts detailing revision frequency and subjects.
            </p>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/views/layout/footer.php'; ?>