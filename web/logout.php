<?php
// logout.php - Session destruction redirect
require_once __DIR__ . '/controllers/AuthController.php';
AuthController::logout();
