<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class Announcement
{
    public static function create(string $title, string $content, string $scheduledAt): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO announcements (title, content, scheduled_at) VALUES (:t,:c,:s)');
        $stmt->execute([
            ':t' => $title,
            ':c' => $content,
            ':s' => $scheduledAt,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, string $title, string $content, string $scheduledAt): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE announcements SET title = :t, content = :c, scheduled_at = :s WHERE id = :id');
        $stmt->execute([
            ':t' => $title,
            ':c' => $content,
            ':s' => $scheduledAt,
            ':id' => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM announcements WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM announcements WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function listAllWithViewCount(): array
    {
        $pdo = Database::getConnection();
        $sql = 'SELECT a.*, COUNT(r.id) AS view_count
                FROM announcements a
                LEFT JOIN announcement_reads r ON r.announcement_id = a.id
                GROUP BY a.id
                ORDER BY a.scheduled_at DESC, a.id DESC';
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findLatestUnreadForUser(int $userId): ?array
    {
        $pdo = Database::getConnection();
                // 使用 PHP 生成的当前时间，避免 PHP 与数据库时区不一致导致 NOW() 判断错误
                $now = date('Y-m-d H:i:s');
        $sql = 'SELECT a.*
                FROM announcements a
                                WHERE a.scheduled_at <= :now
                  AND NOT EXISTS (
                    SELECT 1 FROM announcement_reads r
                    WHERE r.announcement_id = a.id AND r.user_id = :uid
                  )
                ORDER BY a.scheduled_at DESC, a.id DESC
                LIMIT 1';
        $stmt = $pdo->prepare($sql);
                $stmt->execute([
                        ':uid' => $userId,
                        ':now' => $now,
                ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
