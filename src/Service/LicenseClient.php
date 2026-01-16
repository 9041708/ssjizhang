<?php
namespace App\Service;

use App\Model\SystemSetting;

class LicenseClient
{
    public static function checkNow(bool $force = false): array
    {
        $enabled = Config::get('license.client_enabled', false);
        if (!$enabled) {
            return ['ok' => true, 'status' => 'disabled', 'message' => '授权客户端未启用'];
        }

        $settings = SystemSetting::get();
        $email = trim((string)($settings['license_email'] ?? ''));
        $code = trim((string)($settings['license_code'] ?? ''));
        $currentStatus = (string)($settings['license_status'] ?? '');
        $lastCheckAt = $settings['license_last_check_at'] ?? null;

        $fixedCode = trim((string)Config::get('license.fixed_code', ''));
        if ($fixedCode !== '') {
            if ($code !== $fixedCode) {
                // 配置中写死的授权码与当前数据库不一致时，
                // 认为是更换了授权码：同步新授权码并清空本地状态与上次联机时间，
                // 让下一次校验使用新授权码重新联机。
                SystemSetting::updateLicense($email ?: null, $fixedCode, null, null);
                $currentStatus = '';
                $lastCheckAt = null;
            }
            $code = $fixedCode;
        }

        if ($code === '') {
            return ['ok' => false, 'status' => 'missing', 'message' => '请先在系统参数中填写授权码。'];
        }

        $intervalHours = (int)Config::get('license.check_interval_hours', 24);
        if ($intervalHours <= 0) {
            $intervalHours = 24;
        }

        if (!$force && !empty($lastCheckAt)) {
            $ts = strtotime((string)$lastCheckAt);
            if ($ts) {
                $delta = time() - $ts;
                if ($delta < $intervalHours * 3600) {
                    return ['ok' => true, 'status' => $currentStatus ?: 'cached', 'message' => '授权状态已缓存，尚未到下次联机时间。'];
                }
            }
        }

        $serverBase = rtrim((string)Config::get('license.server_url', ''), '/');
        if ($serverBase === '') {
            return ['ok' => false, 'status' => 'no_server', 'message' => '未配置授权服务器地址，请联系管理员。'];
        }

        $url = $serverBase . '/public/api.php?route=license/check';
        $payload = [
            'email' => $email,
            'license_code' => $code,
            'domain' => self::detectDomain(),
            'version' => (string)Config::get('app.version', ''),
        ];

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json; charset=utf-8\r\n",
                'content' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'timeout' => 5,
            ],
        ]);

        $now = date('Y-m-d H:i:s');

        try {
            $resp = @file_get_contents($url, false, $context);
            if ($resp === false || $resp === '') {
                return ['ok' => true, 'status' => 'network_error', 'message' => '暂时无法连接授权服务器，将在下次访问时重试。'];
            }
            $data = json_decode($resp, true);
            if (!is_array($data)) {
                return ['ok' => false, 'status' => 'bad_response', 'message' => '授权服务器返回数据格式异常。'];
            }

            $success = !empty($data['success']);
            $status = (string)($data['status'] ?? '');
            $message = (string)($data['message'] ?? '');

            SystemSetting::updateLicense($email, $code, $status, $now);

            if ($success && $status === 'normal') {
                return ['ok' => true, 'status' => 'normal', 'message' => $message ?: '授权有效'];
            }

            return ['ok' => false, 'status' => $status ?: 'invalid', 'message' => $message ?: '授权校验失败'];
        } catch (\Throwable $e) {
            return ['ok' => true, 'status' => 'network_error', 'message' => '授权校验请求异常，将在下次访问时重试。'];
        }
    }

    public static function enforce(): void
    {
        $enabled = Config::get('license.client_enabled', false);
        if (!$enabled) {
            return;
        }

        $settings = SystemSetting::get();
        $email = trim((string)($settings['license_email'] ?? ''));
        $code = trim((string)($settings['license_code'] ?? ''));
        $status = (string)($settings['license_status'] ?? '');
        $lastCheckAt = $settings['license_last_check_at'] ?? null;

        $fixedCode = trim((string)Config::get('license.fixed_code', ''));
        $hasFixedCode = $fixedCode !== '';
        if ($hasFixedCode) {
            if ($code !== $fixedCode) {
                // 配置中写死的授权码发生了变化，重置本地状态，
                // 避免沿用旧授权码的 expired 等状态。
                SystemSetting::updateLicense($email ?: null, $fixedCode, null, null);
                $status = '';
                $lastCheckAt = null;
            }
            $code = $fixedCode;
        }

        if ($code === '') {
            self::denyIfNotSettings('系统未配置授权信息，请登录管理员账号在“系统参数”中填写授权码。');
            return;
        }

        // 仅当已完成一次正常联机授权（status=normal）时才允许进入系统。
        // 如果配置中写死了授权码，但本地状态不是 normal，先强制联机一次再决定是否放行。
        if ($status !== 'normal') {
            if ($hasFixedCode) {
                // 使用当前配置的授权码强制联机校验一次
                $result = self::checkNow(true);

                $settings = SystemSetting::get();
                $status = (string)($settings['license_status'] ?? '');
                $lastCheckAt = $settings['license_last_check_at'] ?? null;

                if ($status === 'normal') {
                    // 联机后变为正常授权，允许继续执行离线天数等检查
                } else {
                    $msg = $result['message'] ?? '';
                    if (in_array($status, ['expired', 'domain_mismatch', 'not_found'], true)) {
                        self::denyIfNotSettings('授权已失效（' . $status . '），' . ($msg !== '' ? $msg : '请联系管理员处理或重新获取授权码。'));
                    } else {
                        self::denyIfNotSettings($msg !== '' ? $msg : '系统尚未完成有效授权，请登录管理员账号在“系统设置 > 系统参数”中检查授权配置。');
                    }
                    return;
                }
            } else {
                if (in_array($status, ['expired', 'domain_mismatch', 'not_found'], true)) {
                    self::denyIfNotSettings('授权已失效（' . $status . '），请联系管理员处理或重新获取授权码。');
                } else {
                    self::denyIfNotSettings('系统尚未完成有效授权，请登录管理员账号在“系统设置 > 系统参数”中填写授权码并点击“保存并立即校验”。');
                }
                return;
            }
        }

        $offlineDays = (int)Config::get('license.offline_max_days', 7);
        if ($offlineDays <= 0) {
            $offlineDays = 7;
        }

        if (!empty($lastCheckAt)) {
            $ts = strtotime((string)$lastCheckAt);
            if ($ts) {
                $deltaDays = (time() - $ts) / 86400;
                if ($deltaDays > $offlineDays) {
                    self::denyIfNotSettings('系统已连续离线超过 ' . $offlineDays . ' 天，为保证授权安全，已暂停使用，请连接网络并联系管理员重试。');
                    return;
                }
            }
        }

        self::checkNow(false);
    }

    private static function detectDomain(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($host !== '') {
            return $host;
        }
        $url = (string)Config::get('app.site_url', '');
        return $url;
    }

    private static function denyIfNotSettings(string $message): void
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $script = $_SERVER['SCRIPT_NAME'] ?? '';

        $isSettingsRoute = false;
        if (strpos($uri, 'route=settings') !== false) {
            $isSettingsRoute = true;
        }
        if (strpos($uri, 'route=login') !== false || strpos($uri, 'route=logout') !== false) {
            $isSettingsRoute = true;
        }

        if (strpos($script, '/public/api.php') !== false || strpos($script, '/public/captcha.php') !== false) {
            return;
        }

        if ($isSettingsRoute) {
            return;
        }

        // 如果当前已登录且为管理员，自动跳转到系统参数页填写授权码
        $currentUserId = (int)($_SESSION['user_id'] ?? 0);
        $currentUserRole = (string)($_SESSION['user_role'] ?? '');
        if ($currentUserId > 0 && $currentUserRole === 'admin') {
            header('Location: /public/index.php?route=settings&from_license=1');
            exit;
        }

        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><title>授权已失效</title><meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<link href="/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">';
        echo '</head><body class="bg-light"><div class="container py-5"><div class="row justify-content-center"><div class="col-md-8">';
        echo '<div class="card shadow-sm border-0"><div class="card-body p-4">';
        echo '<h1 class="h5 mb-3">系统授权异常</h1>';
        echo '<p class="text-danger small mb-3">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
        echo '<p class="small mb-2">如已完成续费或更换授权，请联系系统管理员在有网络的环境下登录后台，进入「系统设置 &gt; 系统参数」检查授权配置并等待系统重新联机校验。</p>';
        echo '<a href="/public/index.php?route=login" class="btn btn-sm btn-primary">返回登录</a>';
        echo '</div></div></div></div></div></body></html>';
        exit;
    }
}
