<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class SystemSetting
{
    public static function get(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM system_settings WHERE id = 1 LIMIT 1');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $row = [
                'site_name' => 'SanS三石记账系统',
                'site_url' => null,
                'site_icon_svg' => null,
                'allow_register' => 1,
                // 默认空表情况下的会话超时时间（小时）
                'session_timeout_hours' => 24,
                // 绑定二维码默认有效期（分钟）
                'bind_qr_expires_minutes' => 10,
                // 绑定二维码下方提示文案
                'bind_qr_text' => '打开微信小程序“SanS三石记账”，进入绑定页面扫码完成绑定。',
            ];
        } else {
            // 兼容旧数据表中可能不存在的字段
            if (!array_key_exists('site_icon_svg', $row)) {
                $row['site_icon_svg'] = null;
            }
            if (!array_key_exists('session_timeout_hours', $row)) {
                $row['session_timeout_hours'] = 24;
            }
            if (!array_key_exists('bind_qr_expires_minutes', $row)) {
                $row['bind_qr_expires_minutes'] = 10;
            }
            if (!array_key_exists('bind_qr_text', $row)) {
                $row['bind_qr_text'] = '打开微信小程序“SanS三石记账”，进入绑定页面扫码完成绑定。';
            }
        }
        return $row;
    }

    public static function update(string $siteName, ?string $siteUrl, bool $allowRegister, ?string $siteIconSvg, ?int $sessionTimeoutHours = null, ?int $bindQrExpiresMinutes = null, ?string $bindQrText = null): void
    {
        $pdo = Database::getConnection();
        // 规范和限制会话超时时间（小时），范围 1~168 小时，默认 24 小时
        $timeout = $sessionTimeoutHours ?? 24;
        if ($timeout <= 0) {
            $timeout = 24;
        } elseif ($timeout > 168) {
            $timeout = 168;
        }

        // 绑定二维码有效期（分钟），1~1440 之间
        $bindMinutes = $bindQrExpiresMinutes ?? 10;
        if ($bindMinutes <= 0) {
            $bindMinutes = 10;
        } elseif ($bindMinutes > 1440) {
            $bindMinutes = 1440;
        }

        // 检查当前数据库中是否已存在相关字段，避免旧库报错
        $hasTimeoutColumn = false;
        $hasBindMinutesColumn = false;
        $hasBindTextColumn = false;
        try {
            $colStmt = $pdo->query("SHOW COLUMNS FROM system_settings LIKE 'session_timeout_hours'");
            $hasTimeoutColumn = (bool)$colStmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasTimeoutColumn = false;
        }

        try {
            $colStmt2 = $pdo->query("SHOW COLUMNS FROM system_settings LIKE 'bind_qr_expires_minutes'");
            $hasBindMinutesColumn = (bool)$colStmt2->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasBindMinutesColumn = false;
        }

        try {
            $colStmt3 = $pdo->query("SHOW COLUMNS FROM system_settings LIKE 'bind_qr_text'");
            $hasBindTextColumn = (bool)$colStmt3->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasBindTextColumn = false;
        }

        // 构造动态 SQL，仅在对应字段存在时才更新，避免旧库报错
        $setParts = ['site_name = :name', 'site_url = :url', 'site_icon_svg = :icon', 'allow_register = :ar'];
        $params = [
            ':name' => $siteName,
            ':url' => $siteUrl,
            ':icon' => $siteIconSvg,
            ':ar' => $allowRegister ? 1 : 0,
        ];

        if ($hasTimeoutColumn) {
            $setParts[] = 'session_timeout_hours = :timeout';
            $params[':timeout'] = $timeout;
        }
        if ($hasBindMinutesColumn) {
            $setParts[] = 'bind_qr_expires_minutes = :bind_minutes';
            $params[':bind_minutes'] = $bindMinutes;
        }
        if ($hasBindTextColumn) {
            $setParts[] = 'bind_qr_text = :bind_text';
            $params[':bind_text'] = $bindQrText;
        }

        $sql = 'UPDATE system_settings SET ' . implode(', ', $setParts) . ' WHERE id = 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * 更新本地授权相关配置与最近一次联机校验结果。
     * 为兼容旧库，所有字段均在确认存在后才更新。
     */
    public static function updateLicense(?string $email, ?string $code, ?string $status, ?string $lastCheckAt): void
    {
        $pdo = Database::getConnection();

        $hasEmail = false;
        $hasCode = false;
        $hasStatus = false;
        $hasLastCheck = false;
        try {
            $c1 = $pdo->query("SHOW COLUMNS FROM system_settings LIKE 'license_email'");
            $hasEmail = (bool)$c1->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasEmail = false;
        }
        try {
            $c2 = $pdo->query("SHOW COLUMNS FROM system_settings LIKE 'license_code'");
            $hasCode = (bool)$c2->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasCode = false;
        }
        try {
            $c3 = $pdo->query("SHOW COLUMNS FROM system_settings LIKE 'license_status'");
            $hasStatus = (bool)$c3->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasStatus = false;
        }
        try {
            $c4 = $pdo->query("SHOW COLUMNS FROM system_settings LIKE 'license_last_check_at'");
            $hasLastCheck = (bool)$c4->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasLastCheck = false;
        }

        $setParts = [];
        $params = [];

        if ($hasEmail) {
            $setParts[] = 'license_email = :license_email';
            $params[':license_email'] = $email;
        }
        if ($hasCode) {
            $setParts[] = 'license_code = :license_code';
            $params[':license_code'] = $code;
        }
        if ($hasStatus) {
            $setParts[] = 'license_status = :license_status';
            $params[':license_status'] = $status;
        }
        if ($hasLastCheck) {
            $setParts[] = 'license_last_check_at = :license_last_check_at';
            $params[':license_last_check_at'] = $lastCheckAt;
        }

        if (empty($setParts)) {
            return;
        }

        $sql = 'UPDATE system_settings SET ' . implode(', ', $setParts) . ' WHERE id = 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }
}
