<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class ApiToken
{
    public static function createToken(int $userId, string $clientType, int $ttlSeconds): string
    {
        $token = bin2hex(random_bytes(32));
        $pdo = Database::getConnection();
        $expiresAt = date('Y-m-d H:i:s', time() + $ttlSeconds);

        $stmt = $pdo->prepare('INSERT INTO api_tokens (user_id, token, client_type, expires_at) VALUES (:u,:t,:c,:e)');
        $stmt->execute([
            ':u' => $userId,
            ':t' => $token,
            ':c' => $clientType,
            ':e' => $expiresAt,
        ]);

        return $token;
    }

    public static function findValidToken(string $token, ?string $clientType = null): ?array
    {
        $pdo = Database::getConnection();
        if ($clientType !== null) {
            $stmt = $pdo->prepare('SELECT * FROM api_tokens WHERE token = :t AND client_type = :c AND expires_at > NOW() LIMIT 1');
            $stmt->execute([':t' => $token, ':c' => $clientType]);
        } else {
            $stmt = $pdo->prepare('SELECT * FROM api_tokens WHERE token = :t AND expires_at > NOW() LIMIT 1');
            $stmt->execute([':t' => $token]);
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $update = $pdo->prepare('UPDATE api_tokens SET last_used_at = NOW() WHERE id = :id');
            $update->execute([':id' => $row['id']]);
        }
        return $row ?: null;
    }
}
