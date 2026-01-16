<?php
namespace App\Model;

use App\Service\Database;
use App\Service\Mailer;
use PDO;

class EmailPush
{
    public static function create(string $title, string $content, string $scope, string $scheduledAt): int
    {
        $scope = $scope === 'selected' ? 'selected' : 'all';
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO email_pushes (title, content, scope, scheduled_at, status) VALUES (:t,:c,:s,:time,'pending')");
        $stmt->execute([
            ':t' => $title,
            ':c' => $content,
            ':s' => $scope,
            ':time' => $scheduledAt,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM email_pushes WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function listAll(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM email_pushes ORDER BY created_at DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function delete(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM email_push_recipients WHERE push_id = :id');
        $stmt->execute([':id' => $id]);
        $stmt = $pdo->prepare('DELETE FROM email_pushes WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public static function seedRecipients(int $pushId, array $userIds): void
    {
        if (empty($userIds)) {
            return;
        }
        $pdo = Database::getConnection();
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $stmt = $pdo->prepare("SELECT id, email FROM users WHERE id IN ($placeholders) AND email <> '' AND email NOT LIKE '%@miniapp.local'");
        foreach ($userIds as $k => $uid) {
            $stmt->bindValue($k + 1, (int)$uid, PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if (!$rows) {
            return;
        }
        $insert = $pdo->prepare("INSERT INTO email_push_recipients (push_id, user_id, email, status) VALUES (:pid,:uid,:email,'pending')");
        foreach ($rows as $row) {
            $insert->execute([
                ':pid' => $pushId,
                ':uid' => (int)$row['id'],
                ':email' => (string)$row['email'],
            ]);
        }
    }

    public static function sendNow(int $pushId): array
    {
        $pdo = Database::getConnection();
        $push = self::findById($pushId);
        if (!$push) {
            return ['sent' => 0, 'failed' => 0];
        }

        $pdo->beginTransaction();
        try {
            $recipients = [];
            if ($push['scope'] === 'selected') {
                $stmt = $pdo->prepare('SELECT user_id, email FROM email_push_recipients WHERE push_id = :pid');
                $stmt->execute([':pid' => $pushId]);
                $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } else {
                $stmt = $pdo->query("SELECT id AS user_id, email FROM users WHERE status = 1 AND email <> '' AND email NOT LIKE '%@miniapp.local'");
                $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }

            $sent = 0;
            $failed = 0;
            $updateRecipient = $pdo->prepare('UPDATE email_push_recipients SET status = :st, error_message = :err, sent_at = :at WHERE push_id = :pid AND user_id = :uid');
            $insertRecipient = $pdo->prepare('INSERT INTO email_push_recipients (push_id, user_id, email, status, sent_at) VALUES (:pid,:uid,:email,:st,:at)');

            $now = date('Y-m-d H:i:s');
            foreach ($recipients as $row) {
                $uid = (int)$row['user_id'];
                $email = (string)$row['email'];
                if ($email === '') {
                    $failed++;
                    continue;
                }
                $ok = Mailer::send($email, '', $push['title'], $push['content']);
                $status = $ok ? 'sent' : 'failed';
                $err = $ok ? null : 'send_failed';
                if ($ok) {
                    $sent++;
                } else {
                    $failed++;
                }

                // 若已有记录则更新，否则插入
                $updated = false;
                if ($push['scope'] === 'selected') {
                    $updateRecipient->execute([
                        ':st' => $status,
                        ':err' => $err,
                        ':at' => $now,
                        ':pid' => $pushId,
                        ':uid' => $uid,
                    ]);
                    if ($updateRecipient->rowCount() > 0) {
                        $updated = true;
                    }
                }
                if (!$updated) {
                    $insertRecipient->execute([
                        ':pid' => $pushId,
                        ':uid' => $uid,
                        ':email' => $email,
                        ':st' => $status,
                        ':at' => $now,
                    ]);
                }
            }

            $status = $failed > 0 && $sent === 0 ? 'failed' : 'sent';
            $stmt = $pdo->prepare('UPDATE email_pushes SET status = :st, sent_at = :at WHERE id = :id');
            $stmt->execute([
                ':st' => $status,
                ':at' => date('Y-m-d H:i:s'),
                ':id' => $pushId,
            ]);

            $pdo->commit();
            return ['sent' => $sent, 'failed' => $failed];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $stmt = $pdo->prepare("UPDATE email_pushes SET status = 'failed' WHERE id = :id");
            $stmt->execute([':id' => $pushId]);
            return ['sent' => 0, 'failed' => 0];
        }
    }

    public static function processPending(int $limit = 5): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id FROM email_pushes WHERE status = 'pending' AND scheduled_at <= NOW() ORDER BY scheduled_at ASC, id ASC LIMIT :lim");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $count = 0;
        foreach ($rows as $row) {
            self::sendNow((int)$row['id']);
            $count++;
        }
        return $count;
    }
}
