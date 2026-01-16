<?php
namespace App\Model;

use App\Service\Database;
use PDO;
use PDOException;

class Item
{
    public static function findByUser(int $userId, int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM items WHERE id = :id AND user_id = :uid LIMIT 1');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function allByUser(int $userId, ?int $categoryId = null): array
    {
        $pdo = Database::getConnection();
        if ($categoryId) {
            $stmt = $pdo->prepare('SELECT * FROM items WHERE user_id = :uid AND category_id = :cid ORDER BY sort_order, id');
            $stmt->execute([':uid' => $userId, ':cid' => $categoryId]);
        } else {
            $stmt = $pdo->prepare('SELECT * FROM items WHERE user_id = :uid ORDER BY category_id, sort_order, id');
            $stmt->execute([':uid' => $userId]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function create(int $userId, int $categoryId, string $name, int $sortOrder = 0, ?string $iconType = null, ?string $iconValue = null): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO items (user_id, category_id, name, sort_order, icon_type, icon_value) VALUES (:uid,:cid,:name,:sort,:icon_type,:icon_value)');
        try {
            $stmt->execute([
                ':uid' => $userId,
                ':cid' => $categoryId,
                ':name' => $name,
                ':sort' => $sortOrder,
                ':icon_type' => $iconType,
                ':icon_value' => $iconValue,
            ]);
        } catch (PDOException $e) {
            // 唯一索引 uk_user_cat_name 冲突时，抛出业务级异常，供控制器捕获并提示
            if ($e->getCode() === '23000') {
                throw new \RuntimeException('duplicate_item');
            }
            throw $e;
        }
    }

    public static function update(int $userId, int $id, int $categoryId, string $name, int $sortOrder = 0, ?string $iconType = null, ?string $iconValue = null): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE items SET category_id = :cid, name = :name, sort_order = :sort, icon_type = :icon_type, icon_value = :icon_value WHERE id = :id AND user_id = :uid');
        try {
            $stmt->execute([
                ':cid' => $categoryId,
                ':name' => $name,
                ':sort' => $sortOrder,
                ':icon_type' => $iconType,
                ':icon_value' => $iconValue,
                ':id' => $id,
                ':uid' => $userId,
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new \RuntimeException('duplicate_item');
            }
            throw $e;
        }
    }

    public static function delete(int $userId, int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE item_id = :id');
        $stmt->execute([':id' => $id]);
        if ($stmt->fetchColumn() > 0) {
            return false;
        }
        $stmt = $pdo->prepare('DELETE FROM items WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        return $stmt->rowCount() > 0;
    }
}
