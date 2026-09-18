<?php
// export-logs.php - Export security and audit logs as CSV

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/models/Analytics.php';

AdminController::checkAdminAuth();

$logs = Analytics::getRecentLogs();

$filename = 'prepai-audit-logs-' . date('Y-m-d-H-i-s') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

fputcsv($output, [
    'User',
    'Exam Context',
    'Action Event',
    'Timestamp'
]);

foreach ($logs as $log) {
    $userEmail = $log['user_email'] ?? 'System/Guest';

    $examContext = !empty($log['exam_code'])
        ? $log['exam_code']
        : 'System';

    $actionType = $log['action_type'] ?? 'unknown_event';
    $createdAt = $log['created_at'] ?? '';

    fputcsv($output, [
        $userEmail,
        $examContext,
        $actionType,
        $createdAt
    ]);
}

fclose($output);
exit;