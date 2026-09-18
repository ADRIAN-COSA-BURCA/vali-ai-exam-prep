<?php
// User.php - User Model

require_once __DIR__ . '/../config/db.php';

class User
{
    public static function create($email, $password)
    {
        $db = DB::getConnection();

        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $db->prepare(
            "INSERT INTO users
             (email, password_hash, role, status)
             VALUES (?, ?, 'user', 'active')"
        );

        return $stmt->execute([$email, $hash]);
    }

    public static function findByEmail($email)
    {
        $db = DB::getConnection();

        $stmt = $db->prepare(
            "SELECT *
             FROM users
             WHERE email = ?"
        );

        $stmt->execute([$email]);

        return $stmt->fetch();
    }

    public static function findById($id)
    {
        $db = DB::getConnection();

        $stmt = $db->prepare(
            "SELECT id, email, role, status, created_at
             FROM users
             WHERE id = ?"
        );

        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    public static function getAll()
    {
        $db = DB::getConnection();

        $stmt = $db->query(
            "SELECT id, email, role, status, created_at
             FROM users
             ORDER BY created_at DESC"
        );

        return $stmt->fetchAll();
    }

    public static function updateStatus($id, $status)
    {
        $db = DB::getConnection();

        $stmt = $db->prepare(
            "UPDATE users
             SET status = ?
             WHERE id = ?
             AND role != 'admin'"
        );

        return $stmt->execute([$status, $id]);
    }

    public static function createAdmin($email, $password, $role = 'user')
    {
        $db = DB::getConnection();

        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $db->prepare(
            "INSERT INTO users
             (email, password_hash, role, status)
             VALUES (?, ?, ?, 'active')"
        );

        return $stmt->execute([$email, $hash, $role]);
    }

    public static function delete($id)
    {
        $db = DB::getConnection();

        try {
            $db->beginTransaction();

            $stmt = $db->prepare(
                "DELETE FROM flashcard_stats
                 WHERE user_id = ?"
            );
            $stmt->execute([$id]);

            $stmt = $db->prepare(
                "DELETE FROM mock_exam_results
                 WHERE user_id = ?"
            );
            $stmt->execute([$id]);

            $stmt = $db->prepare(
                "DELETE FROM analytics_log
                 WHERE user_id = ?"
            );
            $stmt->execute([$id]);

            $stmt = $db->prepare(
                "DELETE FROM users
                 WHERE id = ?
                 AND role != 'admin'"
            );
            $result = $stmt->execute([$id]);

            $db->commit();

            return $result;
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            error_log(
                "User deletion failed for user ID {$id}: "
                . $e->getMessage()
            );

            return false;
        }
    }
}