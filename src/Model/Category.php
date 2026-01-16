<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class Category
{
    public static function findByUser(int $userId, int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = :id AND user_id = :uid LIMIT 1');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function allByUser(int $userId, ?string $type = null): array
    {
        $pdo = Database::getConnection();
        if ($type) {
            $stmt = $pdo->prepare('SELECT * FROM categories WHERE user_id = :uid AND type = :type ORDER BY sort_order, id');
            $stmt->execute([':uid' => $userId, ':type' => $type]);
        } else {
            $stmt = $pdo->prepare('SELECT * FROM categories WHERE user_id = :uid ORDER BY type, sort_order, id');
            $stmt->execute([':uid' => $userId]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function create(int $userId, string $type, string $name, int $sortOrder = 0, ?string $iconType = null, ?string $iconValue = null): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO categories (user_id, type, name, sort_order, icon_type, icon_value) VALUES (:uid,:type,:name,:sort,:icon_type,:icon_value)');
        $stmt->execute([
            ':uid' => $userId,
            ':type' => $type,
            ':name' => $name,
            ':sort' => $sortOrder,
            ':icon_type' => $iconType,
            ':icon_value' => $iconValue,
        ]);
    }

    public static function update(int $userId, int $id, string $name, int $sortOrder = 0, ?string $iconType = null, ?string $iconValue = null): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE categories SET name = :name, sort_order = :sort, icon_type = :icon_type, icon_value = :icon_value WHERE id = :id AND user_id = :uid');
        $stmt->execute([
            ':name' => $name,
            ':sort' => $sortOrder,
            ':icon_type' => $iconType,
            ':icon_value' => $iconValue,
            ':id' => $id,
            ':uid' => $userId,
        ]);
    }

    public static function delete(int $userId, int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE category_id = :id');
        $stmt->execute([':id' => $id]);
        if ($stmt->fetchColumn() > 0) {
            return false;
        }
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        return $stmt->rowCount() > 0;
    }
}
