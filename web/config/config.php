<?php
// config.php - Application Configuration
session_start();

// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'db');
define('DB_USER', getenv('DB_USER') ?: 'examprep_user');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'examprep_db');

// Mock Mode Configuration (Offline demonstration)
define('MOCK_MODE', filter_var(getenv('MOCK_MODE') ?: 'true', FILTER_VALIDATE_BOOLEAN));

// AI Microservice Configuration (FastAPI)
define('AI_BACKEND_URL', getenv('AI_BACKEND_URL') ?: 'http://ai-backend:8000/generate');


// Base Paths
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_DIR', BASE_PATH . '/uploads/');

// Error Reporting
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

set_time_limit(120);
ini_set('max_execution_time', '120');

// Ensure upload directory exists
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}
