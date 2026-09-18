<?php
require_once __DIR__ . '/../controllers/MockExamController.php';

header('Content-Type: application/json');
$result = MockExamController::handle();
echo json_encode($result);

