<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class UserWechatBinding
{
    public static function findByUserId(int $userId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM user_wechat_bindings WHERE user_id = :u ORDER BY id DESC LIMIT 1');
        $stmt->execute([':u' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findByOpenid(string $openid): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM user_wechat_bindings WHERE openid = :o LIMIT 1');
        $stmt->execute([':o' => $openid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(int $userId, string $openid, ?string $unionid = null): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO user_wechat_bindings (user_id, openid, unionid) VALUES (:u,:o,:n)');
        $stmt->execute([
            ':u' => $userId,
            ':o' => $openid,
            ':n' => $unionid,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function updateLastLogin(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE user_wechat_bindings SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public static function deleteByUserId(int $userId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM user_wechat_bindings WHERE user_id = :u');
        $stmt->execute([':u' => $userId]);
    }
}
