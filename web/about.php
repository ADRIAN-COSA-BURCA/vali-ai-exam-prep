<?php
// about.php - About PrepAI
require_once __DIR__ . '/views/layout/header.php';
require_once __DIR__ . '/views/layout/navbar.php';
?>
<div class="container" style="max-width: 900px; margin-top: 3rem;">
    <div class="card">
        <h2 style="margin-bottom: 1rem; color: var(--accent);">About PrepAI</h2>
        <p style="color: var(--text-muted); line-height: 1.7; margin-bottom: 1.5rem;">
            PrepAI is a modern exam preparation platform designed to help students revise smarter.
            It automatically generates study summaries, interactive flashcards, and mock tests
            using a microservice architecture powered by OpenAI models and local persistence.
        </p>

        <h3 style="margin: 1.5rem 0 0.75rem; color: var(--primary-hover);">Our Mission</h3>
        <p style="color: var(--text-muted); line-height: 1.7;">
            The goal of PrepAI is to simplify revision by transforming study materials into
            structured, easy-to-understand learning aids. Whether students upload their own notes
            or rely on online exam titles, PrepAI adapts to their needs.
        </p>

        <h3 style="margin: 1.5rem 0 0.75rem; color: var(--success);">Technology Behind the Platform</h3>
        <p style="color: var(--text-muted); line-height: 1.7;">
            PrepAI is built using a modular PHP architecture, MySQL database, and a Dockerized
            environment for consistent deployment. AI content generation is handled by a Python
            FastAPI microservice integrated with the OpenAI API, with a local mock fallback available for offline testing.
        </p>

        <h3 style="margin: 1.5rem 0 0.75rem; color: var(--accent);">Academic Context</h3>
        <p style="color: var(--text-muted); line-height: 1.7;">
            This platform is developed as part of a final-year computing project, demonstrating
            practical application of cloud services, web development, database design, and
            artificial intelligence integration.
        </p>
    </div>
</div>
<?php require_once __DIR__ . '/views/layout/footer.php'; ?>
