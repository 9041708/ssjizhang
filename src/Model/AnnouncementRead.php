<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class AnnouncementRead
{
    public static function markRead(int $announcementId, int $userId, string $client): void
    {
        $pdo = Database::getConnection();
        $sql = 'INSERT INTO announcement_reads (announcement_id, user_id, client) VALUES (:aid,:uid,:client)';
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':aid' => $announcementId,
                ':uid' => $userId,
                ':client' => $client,
            ]);
        } catch (\Throwable $e) {
            // 违反唯一约束时忽略，避免重复计数
        }
    }
}
