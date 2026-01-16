<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class EmailToken
{
    public static function create(int $userId, string $email, string $type, string $token, string $expiresAt): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO email_tokens (user_id, email, type, token, expires_at) VALUES (:uid,:email,:type,:token,:exp)');
        $stmt->execute([
            ':uid' => $userId,
            ':email' => $email,
            ':type' => $type,
            ':token' => $token,
            ':exp' => $expiresAt,
        ]);
    }

    public static function findValid(string $token, string $type): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM email_tokens WHERE token = :token AND type = :type AND used = 0 AND expires_at >= NOW() LIMIT 1');
        $stmt->execute([':token' => $token, ':type' => $type]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function markUsed(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE email_tokens SET used = 1 WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
