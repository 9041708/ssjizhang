<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class User
{
    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findByUsername(string $username): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(string $username, string $nickname, string $email, string $passwordHash, string $registerSource = 'pc'): int
    {
        if ($registerSource !== 'miniapp') {
            $registerSource = 'pc';
        }
        $pdo = Database::getConnection();

        // 向后兼容：老库可能没有 register_source 列
        $hasRegisterSource = false;
        try {
            $colStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'register_source'");
            $hasRegisterSource = (bool)$colStmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasRegisterSource = false;
        }

        if ($hasRegisterSource) {
            $stmt = $pdo->prepare('INSERT INTO users (username, nickname, email, register_source, password_hash) VALUES (:u,:n,:e,:src,:p)');
            $stmt->execute([
                ':u' => $username,
                ':n' => $nickname,
                ':e' => $email,
                ':src' => $registerSource,
                ':p' => $passwordHash,
            ]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO users (username, nickname, email, password_hash) VALUES (:u,:n,:e,:p)');
            $stmt->execute([
                ':u' => $username,
                ':n' => $nickname,
                ':e' => $email,
                ':p' => $passwordHash,
            ]);
        }
        return (int)$pdo->lastInsertId();
    }

    public static function updatePassword(int $userId, string $passwordHash): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET password_hash = :p WHERE id = :id');
        $stmt->execute([':p' => $passwordHash, ':id' => $userId]);
    }

    public static function markEmailVerified(int $userId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET email_verified = 1 WHERE id = :id');
        $stmt->execute([':id' => $userId]);
    }

    public static function updateProfile(int $userId, string $username, string $nickname): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET username = :u, nickname = :n WHERE id = :id');
        $stmt->execute([':u' => $username, ':n' => $nickname, ':id' => $userId]);
    }

    public static function updateUsername(int $userId, string $username): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET username = :u WHERE id = :id');
        $stmt->execute([':u' => $username, ':id' => $userId]);
    }

    public static function updateEmail(int $userId, string $email, bool $verified = true): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET email = :e, email_verified = :v WHERE id = :id');
        $stmt->execute([
            ':e' => $email,
            ':v' => $verified ? 1 : 0,
            ':id' => $userId,
        ]);
    }

    public static function listAll(): array
    {
        $pdo = Database::getConnection();
        // 兼容旧库：可能不存在 register_source 字段
        $hasRegisterSource = false;
        try {
            $colStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'register_source'");
            $hasRegisterSource = (bool)$colStmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasRegisterSource = false;
        }

        if ($hasRegisterSource) {
            $sql = 'SELECT u.id, u.username, u.nickname, u.email, u.avatar_path, u.register_source, u.role, u.status, u.email_verified, u.created_at,
                           COUNT(w.id) AS wechat_bind_count,
                           MAX(w.last_login_at) AS wechat_last_login_at
                    FROM users u
                    LEFT JOIN user_wechat_bindings w ON w.user_id = u.id
                GROUP BY u.id, u.username, u.nickname, u.email, u.avatar_path, u.register_source, u.role, u.status, u.email_verified, u.created_at
                    ORDER BY u.id';
        } else {
            // 若无 register_source 字段：根据绑定时间推断注册来源
            // 规则：若存在绑定记录，且最早绑定时间与注册时间相差不超过 5 分钟，则视为“小程序注册”，否则为“PC/网页注册”。
              $sql = "SELECT u.id, u.username, u.nickname, u.email, u.avatar_path,
                           CASE WHEN MIN(w.created_at) IS NOT NULL AND TIMESTAMPDIFF(MINUTE, u.created_at, MIN(w.created_at)) BETWEEN 0 AND 5
                                THEN 'miniapp' ELSE 'pc' END AS register_source,
                           u.role, u.status, u.email_verified, u.created_at,
                           COUNT(w.id) AS wechat_bind_count,
                           MAX(w.last_login_at) AS wechat_last_login_at
                    FROM users u
                    LEFT JOIN user_wechat_bindings w ON w.user_id = u.id
                GROUP BY u.id, u.username, u.nickname, u.email, u.avatar_path, u.role, u.status, u.email_verified, u.created_at
                    ORDER BY u.id";
        }

        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function updateThemeMode(int $userId, string $mode): void
    {
        $allowed = ['light', 'dark'];
        if (!in_array($mode, $allowed, true)) {
            $mode = 'light';
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET theme_mode = :m WHERE id = :id');
        $stmt->execute([':m' => $mode, ':id' => $userId]);
    }

    public static function updateStatus(int $userId, int $status): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET status = :s WHERE id = :id');
        $stmt->execute([':s' => $status, ':id' => $userId]);
    }

    public static function updateRole(int $userId, string $role): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET role = :r WHERE id = :id');
        $stmt->execute([':r' => $role, ':id' => $userId]);
    }

    public static function deleteForce(int $userId): void
    {
        $pdo = Database::getConnection();
        // 为了兼容旧库中可能缺失 ON DELETE CASCADE 约束的情况，
        // 这里手动清理与用户相关的业务数据，再删除用户本身。
        $pdo->beginTransaction();
        try {
            // 记账流水
            $stmt = $pdo->prepare('DELETE FROM transactions WHERE user_id = :id');
            $stmt->execute([':id' => $userId]);

            // 账户
            $stmt = $pdo->prepare('DELETE FROM accounts WHERE user_id = :id');
            $stmt->execute([':id' => $userId]);

            // 预算
            $stmt = $pdo->prepare('DELETE FROM budgets WHERE user_id = :id');
            $stmt->execute([':id' => $userId]);

            // 分类与项目
            $stmt = $pdo->prepare('DELETE FROM items WHERE user_id = :id');
            $stmt->execute([':id' => $userId]);
            $stmt = $pdo->prepare('DELETE FROM categories WHERE user_id = :id');
            $stmt->execute([':id' => $userId]);

            // 图标库
            $stmt = $pdo->prepare('DELETE FROM icon_library WHERE user_id = :id');
            $stmt->execute([':id' => $userId]);

            // API Token / 登录 Token / 邮件验证 Token
            $stmt = $pdo->prepare('DELETE FROM api_tokens WHERE user_id = :id');
            $stmt->execute([':id' => $userId]);
            $stmt = $pdo->prepare('DELETE FROM login_tokens WHERE user_id = :id');
            $stmt->execute([':id' => $userId]);
            $stmt = $pdo->prepare('DELETE FROM email_tokens WHERE user_id = :id');
            $stmt->execute([':id' => $userId]);

            // 微信绑定关系
            $stmt = $pdo->prepare('DELETE FROM user_wechat_bindings WHERE user_id = :id');
            $stmt->execute([':id' => $userId]);

            // 反馈记录
            $stmt = $pdo->prepare('DELETE FROM feedbacks WHERE user_id = :id');
            $stmt->execute([':id' => $userId]);

            // 最后删除用户本身
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
            $stmt->execute([':id' => $userId]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function updateLoginSecurity(int $userId, int $failedCount, ?int $lockUntilTimestamp): void
    {
        $pdo = Database::getConnection();
        $lockUntil = null;
        if ($lockUntilTimestamp !== null) {
            $lockUntil = date('Y-m-d H:i:s', $lockUntilTimestamp);
        }
        $stmt = $pdo->prepare('UPDATE users SET failed_login_count = :c, login_lock_until = :u WHERE id = :id');
        $stmt->execute([
            ':c' => $failedCount,
            ':u' => $lockUntil,
            ':id' => $userId,
        ]);
    }

    public static function updateBudgetReminder(int $userId, bool $enabled): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET budget_reminder_enabled = :b WHERE id = :id');
        $stmt->execute([
            ':b' => $enabled ? 1 : 0,
            ':id' => $userId,
        ]);
    }

    public static function updateAvatarPath(int $userId, ?string $avatarPath): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET avatar_path = :p WHERE id = :id');
        $stmt->execute([
            ':p' => $avatarPath,
            ':id' => $userId,
        ]);
    }
}
