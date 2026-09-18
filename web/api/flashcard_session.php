<?php
// /web/api/flashcard_session.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/FlashcardController.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents("php://input"), true);
$materialId = $input['material_id'] ?? null;

$result = FlashcardController::logSession($materialId, $_SESSION['user_id'] ?? null);
echo json_encode($result);
exit;
