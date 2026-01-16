<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class Budget
{
    public static function listByUserMonth(int $userId, int $year, int $month): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT b.*, 
                c.name AS category_name,
                c.icon_type AS category_icon_type,
                c.icon_value AS category_icon_value,
                i.name AS item_name,
                i.icon_type AS item_icon_type,
                i.icon_value AS item_icon_value
            FROM budgets b
            LEFT JOIN categories c ON b.category_id = c.id
            LEFT JOIN items i ON b.item_id = i.id
            WHERE b.user_id = :uid AND b.year = :y AND b.month = :m
            ORDER BY b.type, b.category_id, b.item_id');
        $stmt->execute([':uid' => $userId, ':y' => $year, ':m' => $month]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function upsert(int $userId, int $year, int $month, string $type, ?int $categoryId, ?int $itemId, float $amount): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO budgets (user_id, year, month, type, category_id, item_id, amount)
            VALUES (:uid,:y,:m,:type,:cid,:iid,:amount)
            ON DUPLICATE KEY UPDATE amount = VALUES(amount)');
        $stmt->execute([
            ':uid' => $userId,
            ':y' => $year,
            ':m' => $month,
            ':type' => $type,
            ':cid' => $categoryId,
            ':iid' => $itemId,
            ':amount' => $amount,
        ]);
    }

    public static function delete(int $userId, int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM budgets WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
    }

    public static function findOneByUserYearMonthKey(int $userId, int $year, int $month, string $type, ?int $categoryId, ?int $itemId): ?array
    {
        $pdo = Database::getConnection();

        $sql = 'SELECT * FROM budgets WHERE user_id = :uid AND year = :y AND month = :m AND type = :type';
        $params = [
            ':uid' => $userId,
            ':y' => $year,
            ':m' => $month,
            ':type' => $type,
        ];

        if ($categoryId === null) {
            $sql .= ' AND category_id IS NULL';
        } else {
            $sql .= ' AND category_id = :cid';
            $params[':cid'] = $categoryId;
        }

        if ($itemId === null) {
            $sql .= ' AND item_id IS NULL';
        } else {
            $sql .= ' AND item_id = :iid';
            $params[':iid'] = $itemId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    public static function updateAmount(int $userId, int $id, float $amount): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE budgets SET amount = :amount WHERE id = :id AND user_id = :uid');
        $stmt->execute([
            ':amount' => $amount,
            ':id' => $id,
            ':uid' => $userId,
        ]);
    }
}
