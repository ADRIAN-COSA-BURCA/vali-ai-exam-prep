<?php
// ExamController.php - Exam Management and File Uploads Controller
require_once __DIR__ . '/../models/Exam.php';

class ExamController {
    public static function listExams() {
        return Exam::getAll();
    }

    public static function getExamDetails($id) {
        return Exam::findById($id);
    }

    public static function handleUpload($file) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['status' => false, 'error' => 'Error during file upload.'];
        }

        $allowedExtensions = ['pdf', 'txt', 'docx'];
        $fileName = $file['name'];
        $fileSize = $file['size'];
        $tmpPath = $file['tmp_name'];

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions)) {
            return ['status' => false, 'error' => 'Only PDF, TXT, and DOCX files are allowed.'];
        }

        // Limit size to 5MB
        if ($fileSize > 5 * 1024 * 1024) {
            return ['status' => false, 'error' => 'File size exceeds 5MB limit.'];
        }

        $newFileName = uniqid('material_', true) . '.' . $ext;
        $destPath = UPLOAD_DIR . $newFileName;

        if (move_uploaded_file($tmpPath, $destPath)) {
            return ['status' => true, 'path' => $newFileName];
        }

        return ['status' => false, 'error' => 'Failed to move uploaded file.'];
    }
}
