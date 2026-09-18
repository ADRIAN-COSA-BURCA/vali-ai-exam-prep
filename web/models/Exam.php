<?php
// Exam.php - Exam Model
require_once __DIR__ . '/../config/db.php';

class Exam {
    public static function getAll() {
        $db = DB::getConnection();
        $stmt = $db->query("SELECT * FROM exams ORDER BY category, name ASC");
        return $stmt->fetchAll();
    }

    public static function findById($id) {
        $db = DB::getConnection();
        $stmt = $db->prepare("SELECT * FROM exams WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function getCategories() {
        $db = DB::getConnection();
        $stmt = $db->query("SELECT DISTINCT category FROM exams ORDER BY category ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function create($code, $name, $description, $category) {
        $db = DB::getConnection();
        $stmt = $db->prepare("INSERT INTO exams (code, name, description, category) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$code, $name, $description, $category]);
    }

    public static function delete($id) {
        $db = DB::getConnection();
        $stmt = $db->prepare("DELETE FROM exams WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
