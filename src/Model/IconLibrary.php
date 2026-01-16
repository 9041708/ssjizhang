<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class IconLibrary
{
    public static function allByUser(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM icon_library WHERE user_id = :uid ORDER BY id');
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findByUser(int $userId, int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM icon_library WHERE id = :id AND user_id = :uid LIMIT 1');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(int $userId, string $name, string $filePath): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO icon_library (user_id, name, file_path) VALUES (:uid,:name,:path)');
        $stmt->execute([
            ':uid' => $userId,
            ':name' => $name,
            ':path' => $filePath,
        ]);
        return (int)$pdo->lastInsertId();
    }

    /**
     * 如果当前用户下不存在同一路径的图标，则以给定名称创建一条记录。
     */
    public static function ensureExists(int $userId, string $filePath, string $defaultName): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM icon_library WHERE user_id = :uid AND file_path = :path LIMIT 1');
        $stmt->execute([
            ':uid' => $userId,
            ':path' => $filePath,
        ]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            return;
        }

        // 使用默认名称创建记录
        self::create($userId, $defaultName, $filePath);
    }

    public static function updateName(int $userId, int $id, string $name): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE icon_library SET name = :name WHERE id = :id AND user_id = :uid');
        $stmt->execute([
            ':name' => $name,
            ':id' => $id,
            ':uid' => $userId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function updateFile(int $userId, int $id, string $filePath, ?string $name = null): bool
    {
        $pdo = Database::getConnection();
        if ($name !== null && $name !== '') {
            $stmt = $pdo->prepare('UPDATE icon_library SET file_path = :path, name = :name WHERE id = :id AND user_id = :uid');
            $stmt->execute([
                ':path' => $filePath,
                ':name' => $name,
                ':id' => $id,
                ':uid' => $userId,
            ]);
        } else {
            $stmt = $pdo->prepare('UPDATE icon_library SET file_path = :path WHERE id = :id AND user_id = :uid');
            $stmt->execute([
                ':path' => $filePath,
                ':id' => $id,
                ':uid' => $userId,
            ]);
        }
        return $stmt->rowCount() > 0;
    }

    public static function delete(int $userId, int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM icon_library WHERE id = :id AND user_id = :uid');
        $stmt->execute([
            ':id' => $id,
            ':uid' => $userId,
        ]);
        return $stmt->rowCount() > 0;
    }
}
