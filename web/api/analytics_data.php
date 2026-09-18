<?php
require_once __DIR__ . '/../models/Analytics.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) { http_response_code(401); echo json_encode(null); exit; }

$data = [
  'flashcard_totals' => Analytics::getUserFlashcardStats($userId),
  'flashcard_timeseries' => Analytics::getUserFlashcardTimeSeries($userId, 30),
  'mock_exam_stats' => Analytics::getUserExamStats($userId),
  'study_stats' => Analytics::getUserStudyStats($userId)
];

header('Content-Type: application/json');
echo json_encode($data);
