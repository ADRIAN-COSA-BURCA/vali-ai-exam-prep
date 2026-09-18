<?php
// db.php - Database Connection Manager
require_once __DIR__ . '/config.php';

class DB {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                self::ensureTablesAndAdmin(self::$instance);
            } catch (PDOException $e) {
                try {
                    $dsnNoDb = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
                    $conn = new PDO($dsnNoDb, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                    $conn->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`");
                    self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                    self::ensureTablesAndAdmin(self::$instance);
                } catch (PDOException $e2) {
                    die("Database connection failed: " . $e2->getMessage());
                }
            }
        }
        return self::$instance;
    }

    private static function ensureTablesAndAdmin($db) {
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT 'user',
            status VARCHAR(50) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");

        $db->exec("CREATE TABLE IF NOT EXISTS exams (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            category VARCHAR(100) NOT NULL
        ) ENGINE=InnoDB");

        $db->exec("CREATE TABLE IF NOT EXISTS study_materials (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            exam_id INT NOT NULL,
            source_mode VARCHAR(50) NOT NULL,
            file_path VARCHAR(255) DEFAULT NULL,
            content_type VARCHAR(50) NOT NULL,
            difficulty VARCHAR(50) NOT NULL,
            raw_results_json LONGTEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");

        $db->exec("CREATE TABLE IF NOT EXISTS analytics_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT NULL,
            exam_id INT DEFAULT NULL,
            action_type VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE SET NULL
        ) ENGINE=InnoDB");

        $db->exec("CREATE TABLE IF NOT EXISTS mock_exam_results (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            exam_id INT NOT NULL,
            material_id INT NOT NULL,
            score_percentage INT NOT NULL,
            correct_count INT NOT NULL,
            incorrect_count INT NOT NULL,
            passed TINYINT(1) NOT NULL,
            time_taken_seconds INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (exam_id) REFERENCES exams(id),
            FOREIGN KEY (material_id) REFERENCES study_materials(id)
        ) ENGINE=InnoDB");

        $db->exec("CREATE TABLE IF NOT EXISTS flashcard_stats (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            exam_id INT NOT NULL,
            correct INT DEFAULT 0,
            incorrect INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (exam_id) REFERENCES exams(id)
        ) ENGINE=InnoDB");

        $db->exec("CREATE TABLE IF NOT EXISTS mock_exam_answers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            material_id INT NOT NULL,
            question_index INT NOT NULL,
            selected_option VARCHAR(255),
            correct_option VARCHAR(255),
            timed_out TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (material_id) REFERENCES study_materials(id)
        ) ENGINE=InnoDB");

        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = 'admin@examprep.com'");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
    $adminPassword = getenv('ADMIN_PASSWORD');

    if (!$adminPassword) {
        throw new RuntimeException('ADMIN_PASSWORD is not configured.');
    }

    $hash = password_hash($adminPassword, PASSWORD_BCRYPT);

    $stmt = $db->prepare(
        "INSERT INTO users (email, password_hash, role, status)
         VALUES ('admin@examprep.com', ?, 'admin', 'active')"
    );
    $stmt->execute([$hash]);
} else {
    $adminPassword = getenv('ADMIN_PASSWORD');

    if (!$adminPassword) {
        throw new RuntimeException('ADMIN_PASSWORD is not configured.');
    }

    $hash = password_hash($adminPassword, PASSWORD_BCRYPT);

    $stmt = $db->prepare(
        "UPDATE users
         SET password_hash = ?
         WHERE email = 'admin@examprep.com'"
    );
    $stmt->execute([$hash]);
}
    }
}
