<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class LoginToken
{
    public static function create(string $token, string $expiresAt): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO login_tokens (token, status, user_id, expires_at, created_at) VALUES (:token, 'pending', NULL, :expires, NOW())");
        $stmt->execute([
            ':token' => $token,
            ':expires' => $expiresAt,
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * 为绑定场景创建带用户ID的待确认令牌。
     */
    public static function createForBind(string $token, int $userId, string $expiresAt): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO login_tokens (token, status, user_id, expires_at, created_at) VALUES (:token, 'pending', :uid, :expires, NOW())");
        $stmt->execute([
            ':token' => $token,
            ':uid' => $userId,
            ':expires' => $expiresAt,
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function findByToken(string $token): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM login_tokens WHERE token = :token LIMIT 1');
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function confirm(string $token, int $userId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE login_tokens SET status = 'confirmed', user_id = :uid WHERE token = :token AND status = 'pending'");
        $stmt->execute([
            ':uid' => $userId,
            ':token' => $token,
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function expire(string $token): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE login_tokens SET status = 'expired' WHERE token = :token AND status = 'pending'");
        $stmt->execute([':token' => $token]);
        return $stmt->rowCount() > 0;
    }
}
