<?php
// admin-logs.php - View System Security and Audit Logs

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/models/Analytics.php';

AdminController::checkAdminAuth();

$logs = Analytics::getRecentLogs();

require_once __DIR__ . '/views/layout/header.php';
require_once __DIR__ . '/views/layout/navbar.php';
?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2>Security &amp; Audit Log</h2>

        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="/admin-dashboard.php"
               class="btn btn-secondary"
               style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                <i class="fa-solid fa-users"></i> Users
            </a>

            <a href="/admin-exams.php"
               class="btn btn-secondary"
               style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                <i class="fa-solid fa-book"></i> Library
            </a>

            <a href="/admin-logs.php"
               class="btn btn-primary"
               style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                <i class="fa-solid fa-list-check"></i> Logs
            </a>

            <a href="/export-logs.php"
               class="btn btn-secondary"
               style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                <i class="fa-solid fa-download"></i> Export CSV
            </a>
        </div>
    </div>

    <div class="card" style="padding: 1.5rem;">
        <h3 style="margin-bottom: 1.5rem;">
            <i class="fa-solid fa-shield-halved"></i>
            Action Tracking
        </h3>

        <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Exam Context</th>
                        <th>Action Event</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="4"
                                style="text-align: center; color: var(--text-muted);">
                                No activity logged yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <?php
                            $userEmail = $log['user_email'] ?? 'System/Guest';

                            $examContext = !empty($log['exam_code'])
                                ? $log['exam_code']
                                : 'System';

                            $actionType = $log['action_type'] ?? 'unknown_event';
                            ?>

                            <tr>
                                <td>
                                    <?= htmlspecialchars(
                                        $userEmail,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $examContext,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <span class="badge"
                                          style="background: rgba(6, 182, 212, 0.1); color: var(--accent);">
                                        <?= htmlspecialchars(
                                            $actionType,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <small style="color: var(--text-muted);">
                                        <?= htmlspecialchars(
                                            $log['created_at'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/layout/footer.php'; ?>