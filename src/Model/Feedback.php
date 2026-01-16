<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class Feedback
{
    public const CATEGORY_SUGGEST = 'suggest';
    public const CATEGORY_BUG = 'bug';
    public const CATEGORY_OTHER = 'other';

    public static function categoryLabels(): array
    {
        return [
            self::CATEGORY_SUGGEST => '建议反馈',
            self::CATEGORY_BUG => '错误反馈',
            self::CATEGORY_OTHER => '其它反馈',
        ];
    }

    public static function normalizeCategory(string $category): string
    {
        $category = strtolower(trim($category));
        $allowed = [self::CATEGORY_SUGGEST, self::CATEGORY_BUG, self::CATEGORY_OTHER];
        if (!in_array($category, $allowed, true)) {
            return self::CATEGORY_SUGGEST;
        }
        return $category;
    }

    public static function create(int $userId, string $category, string $content, array $imagePaths = []): int
    {
        $pdo = Database::getConnection();
        $category = self::normalizeCategory($category);
        $imagesJson = $imagePaths ? json_encode(array_values($imagePaths), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;

        $stmt = $pdo->prepare('INSERT INTO feedbacks (user_id, category, content, images, status) VALUES (:uid,:cat,:content,:images,\'pending\')');
        $stmt->execute([
            ':uid' => $userId,
            ':cat' => $category,
            ':content' => $content,
            ':images' => $imagesJson,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function getUserId(int $id): ?int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT user_id FROM feedbacks WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        return isset($row['user_id']) ? (int)$row['user_id'] : null;
    }

    public static function listForFaq(int $limit = 200): array
    {
        $pdo = Database::getConnection();
        $sql = 'SELECT f.*, u.nickname, u.username FROM feedbacks f INNER JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC LIMIT :limit';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$row) {
            $row['images_array'] = [];
            if (!empty($row['images'])) {
                $decoded = json_decode((string)$row['images'], true);
                if (is_array($decoded)) {
                    $row['images_array'] = array_values(array_filter($decoded, static function ($v) {
                        return is_string($v) && $v !== '';
                    }));
                }
            }
        }
        unset($row);

        return $rows;
    }

    public static function updateReply(int $id, int $adminUserId, string $reply, string $status = 'resolved', array $newImagePaths = []): void
    {
        $pdo = Database::getConnection();
        $reply = trim($reply);
        if ($reply === '') {
            return;
        }
        $allowedStatus = ['pending', 'resolved', 'closed'];
        if (!in_array($status, $allowedStatus, true)) {
            $status = 'resolved';
        }

        // 合并已有图片与管理员新上传的图片，统一存放在 images JSON 中
        $imagesJson = null;
        if (!empty($newImagePaths)) {
            $stmt = $pdo->prepare('SELECT images FROM feedbacks WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $existing = [];
            if (!empty($row['images'])) {
                $decoded = json_decode((string)$row['images'], true);
                if (is_array($decoded)) {
                    $existing = array_values(array_filter($decoded, static function ($v) {
                        return is_string($v) && $v !== '';
                    }));
                }
            }
            $merged = array_merge($existing, array_values($newImagePaths));
            $imagesJson = $merged ? json_encode($merged, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;
        }
        if ($imagesJson !== null) {
            $stmt = $pdo->prepare('UPDATE feedbacks SET admin_reply = :reply, admin_user_id = :admin_id, status = :status, admin_reply_at = NOW(), images = :images WHERE id = :id');
            $stmt->execute([
                ':reply' => $reply,
                ':admin_id' => $adminUserId,
                ':status' => $status,
                ':images' => $imagesJson,
                ':id' => $id,
            ]);
        } else {
            $stmt = $pdo->prepare('UPDATE feedbacks SET admin_reply = :reply, admin_user_id = :admin_id, status = :status, admin_reply_at = NOW() WHERE id = :id');
            $stmt->execute([
                ':reply' => $reply,
                ':admin_id' => $adminUserId,
                ':status' => $status,
                ':id' => $id,
            ]);
        }
    }
}
