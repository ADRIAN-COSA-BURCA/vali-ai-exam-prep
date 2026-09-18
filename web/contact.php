<?php
// contact.php - Contact Page
require_once __DIR__ . '/views/layout/header.php';
require_once __DIR__ . '/views/layout/navbar.php';
?>
<div class="container" style="max-width: 800px; margin-top: 3rem;">
    <div class="card">
        <h2 style="margin-bottom: 1rem; color: var(--accent);">Contact Us</h2>
        <p style="color: var(--text-muted); line-height: 1.7; margin-bottom: 2rem;">
            Have a question, found an issue, or want to share feedback about PrepAI?
            Use the form below and we’ll get back to you as soon as possible.
        </p>

        <form method="POST" action="#" onsubmit="alert('This form is a demo. Backend will be added later.'); return false;">
            <div class="form-group">
                <label for="name">Your Name</label>
                <input id="name" name="name" type="text" class="form-control" placeholder="John Doe" required>
            </div>

            <div class="form-group">
                <label for="email">Your Email Address</label>
                <input id="email" name="email" type="email" class="form-control" placeholder="name@domain.com" required>
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category" class="form-control" required>
                    <option value="">Select a category...</option>
                    <option value="general">General Inquiry</option>
                    <option value="technical">Technical Issue</option>
                    <option value="ai-problem">AI Content Problem</option>
                    <option value="feature">Feature Request</option>
                    <option value="account">Account Support</option>
                    <option value="feedback">Feedback</option>
                </select>
            </div>

            <div class="form-group">
                <label for="subject">Subject</label>
                <input id="subject" name="subject" type="text" class="form-control" placeholder="Short description..." required>
            </div>

            <div class="form-group">
                <label for="message">Message</label>
                <textarea id="message" name="message" class="form-control" rows="5" placeholder="Write your message here..." required></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">
                <i class="fa-solid fa-paper-plane"></i>&nbsp; Send Message
            </button>
        </form>

        <div style="margin-top: 2.5rem; color: var(--text-muted); font-size: 0.9rem; line-height: 1.6;">
            <strong style="color: var(--primary-hover);">Support Email:</strong> support@prepai.example<br>
            For urgent issues, please include your account email and exam name.
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/views/layout/footer.php'; ?>
