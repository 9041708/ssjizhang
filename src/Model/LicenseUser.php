<?php
namespace App\Model;

use App\Service\Database;

class LicenseUser
{
    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM license_users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    public static function findByEmailAndCode(string $email, string $licenseCode): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM license_users WHERE email = :email AND license_code = :code ORDER BY id DESC LIMIT 1');
        $stmt->execute([
            ':email' => $email,
            ':code' => $licenseCode,
        ]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    public static function findByEmailAndDomain(string $email, string $domain): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM license_users WHERE email = :email AND domain = :domain ORDER BY id DESC LIMIT 1');
        $stmt->execute([
            ':email' => $email,
            ':domain' => $domain,
        ]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    public static function listLatest(int $limit = 50): array
    {
        $pdo = Database::getConnection();
        $limit = max(1, min($limit, 200));
        $stmt = $pdo->query('SELECT * FROM license_users ORDER BY id DESC LIMIT ' . $limit);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public static function search(string $keyword, int $limit = 200): array
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return self::listLatest($limit);
        }

        $pdo = Database::getConnection();
        $limit = max(1, min($limit, 500));
        $sql = 'SELECT * FROM license_users
                WHERE email LIKE :kw OR domain LIKE :kw
                ORDER BY id DESC
                LIMIT ' . $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':kw' => '%' . $keyword . '%']);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public static function updateStatus(int $id, string $status): void
    {
        $allowed = ['unused', 'normal', 'expired'];
        if (!in_array($status, $allowed, true)) {
            $status = 'normal';
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE license_users SET license_status = :status WHERE id = :id');
        $stmt->execute([
            ':status' => $status,
            ':id' => $id,
        ]);
    }

    public static function updateBasic(int $id, string $email, string $domain): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE license_users SET email = :email, domain = :domain WHERE id = :id');
        $stmt->execute([
            ':email' => $email,
            ':domain' => $domain,
            ':id' => $id,
        ]);
    }

    public static function stopLicense(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE license_users SET license_status = "expired" WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public static function deleteById(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM license_users WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public static function changeDomain(int $id, string $newDomain): void
    {
        $newDomain = trim($newDomain);
        if ($newDomain === '') {
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT domain_change_quota, domain_change_used FROM license_users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $info = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$info) {
            return;
        }

        $quota = (int)($info['domain_change_quota'] ?? 0);
        $used = (int)($info['domain_change_used'] ?? 0);

        // 如设置了配额且已用完，则不再更换（静默返回）
        if ($quota > 0 && $used >= $quota) {
            return;
        }

        $stmtUpd = $pdo->prepare('UPDATE license_users SET domain = :domain, domain_change_used = domain_change_used + 1 WHERE id = :id');
        $stmtUpd->execute([
            ':domain' => $newDomain,
            ':id' => $id,
        ]);
    }

    private static function randomCode(int $length = 18): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $max = strlen($chars) - 1;
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, $max)];
        }
        return $code;
    }

    public static function createFromRequest(int $requestId): void
    {
        $pdo = Database::getConnection();

        try {
            $stmt = $pdo->prepare('SELECT * FROM license_requests WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $requestId]);
            $req = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$req) {
                return;
            }

            $email = (string)$req['email'];
            $domain = (string)$req['domain'];
            $type = (string)$req['request_type'];
            $period = $req['period'] ?: null;

            $code = self::randomCode(18);

            $quota = 0;
            if ($period === 'year') {
                $quota = 1;
            } elseif ($period === 'lifetime') {
                $quota = 3;
            }

            // 先插入最基础字段，依赖表的默认值，兼容性最好
            $sqlInsert = 'INSERT INTO license_users (
                email, domain, license_code, license_status, created_at
            ) VALUES (
                :email, :domain, :code, :status, NOW()
            )';
            $stmtIns = $pdo->prepare($sqlInsert);
            $stmtIns->execute([
                ':email' => $email,
                ':domain' => $domain,
                ':code' => $code,
                ':status' => 'unused',
            ]);

            $newId = (int)$pdo->lastInsertId();

            // 再补充更新类型、周期和可更换次数（如字段存在则生效）
            if ($newId > 0) {
                try {
                    $sqlUpdate = 'UPDATE license_users
                        SET license_type = :license_type,
                            license_period = :license_period,
                            domain_change_quota = :quota
                        WHERE id = :id';
                    $stmtUpd = $pdo->prepare($sqlUpdate);
                    $stmtUpd->execute([
                        ':license_type' => $type === 'change' ? 'change' : 'first',
                        ':license_period' => $period,
                        ':quota' => $quota,
                        ':id' => $newId,
                    ]);
                } catch (\Throwable $e) {
                    // 忽略这里的异常，至少保证授权用户已经创建
                }
            }

            // 标记请求已处理
            $pdo->prepare('UPDATE license_requests SET status = "processed" WHERE id = :id')->execute([':id' => $requestId]);
        } catch (\Throwable $e) {
            // 防止异常导致后台 500，这里静默失败即可
            return;
        }
    }
}
