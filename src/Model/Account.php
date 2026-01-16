<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class Account
{
    public static function allByUser(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT a.*, ag.name AS group_name, ag.code AS group_code FROM accounts a JOIN account_groups ag ON a.group_id = ag.id WHERE a.user_id = :uid ORDER BY ag.id, a.id');
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findByUser(int $userId, int $accountId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = :id AND user_id = :uid LIMIT 1');
        $stmt->execute([':id' => $accountId, ':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(int $userId, int $groupId, string $name, ?string $accountNo, float $initialBalance, ?string $iconType = null, ?string $iconValue = null): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO accounts (user_id, group_id, name, account_no, initial_balance, current_balance, icon_type, icon_value) VALUES (:uid,:gid,:name,:no,:init,:curr,:icon_type,:icon_value)');
        $stmt->execute([
            ':uid' => $userId,
            ':gid' => $groupId,
            ':name' => $name,
            ':no' => $accountNo,
            ':init' => $initialBalance,
            ':curr' => $initialBalance,
            ':icon_type' => $iconType,
            ':icon_value' => $iconValue,
        ]);
    }

    public static function update(int $userId, int $accountId, int $groupId, string $name, ?string $accountNo, ?string $iconType = null, ?string $iconValue = null): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE accounts SET group_id = :gid, name = :name, account_no = :no, icon_type = :icon_type, icon_value = :icon_value WHERE id = :id AND user_id = :uid');
        $stmt->execute([
            ':gid' => $groupId,
            ':name' => $name,
            ':no' => $accountNo,
            ':icon_type' => $iconType,
            ':icon_value' => $iconValue,
            ':id' => $accountId,
            ':uid' => $userId,
        ]);
    }

    public static function delete(int $userId, int $accountId): bool
    {
        $pdo = Database::getConnection();
        // 检查是否有流水
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE from_account_id = :id OR to_account_id = :id');
        $stmt->execute([':id' => $accountId]);
        if ($stmt->fetchColumn() > 0) {
            return false;
        }
        $stmt = $pdo->prepare('DELETE FROM accounts WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $accountId, ':uid' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function adjustBalance(int $accountId, float $delta): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE accounts SET current_balance = current_balance + :delta WHERE id = :id');
        $stmt->execute([':delta' => $delta, ':id' => $accountId]);
    }
}
