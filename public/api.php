<?php
// Simple JSON API front controller for mini program / mobile clients

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Model\User;
use App\Model\ApiToken;
use App\Model\UserWechatBinding;
use App\Model\Account;
use App\Model\Category;
use App\Model\Item;
use App\Model\Budget;
use App\Model\Transaction;
use App\Model\Feedback;
use App\Model\IconLibrary;
use App\Model\LoginToken;
use App\Model\Announcement;
use App\Model\AnnouncementRead;
use App\Service\Config;
use App\Service\WeChatMiniApp;
use App\Service\Database;
use App\Service\Upload;
use App\Service\Seeder;
// PDO 在全局命名空间下直接使用 PDO:: 即可，这里无需 use

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (session_status() === PHP_SESSION_NONE) {
    session_name('SSJIZHANGSESSID');
    session_start();
}

$route = isset($_GET['route']) ? trim((string)$_GET['route'], '/') : '';

function json_response(int $status, $data): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function parse_json_body(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function get_auth_token(): ?string {
    $headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
    $auth = '';
    foreach ($headers as $k => $v) {
        if (strcasecmp($k, 'Authorization') === 0) {
            $auth = trim((string)$v);
            break;
        }
    }
    if ($auth !== '') {
        if (stripos($auth, 'Bearer ') === 0) {
            return trim(substr($auth, 7));
        }
        return $auth;
    }

    if (!empty($_GET['token'])) {
        return (string)$_GET['token'];
    }

    return null;
}

function require_auth_user(): array {
    $token = get_auth_token();
    if (!$token) {
        json_response(401, ['success' => false, 'error' => '缺少令牌']);
    }
    $tokenRow = ApiToken::findValidToken($token, 'miniapp');
    if (!$tokenRow) {
        json_response(401, ['success' => false, 'error' => '令牌无效或已过期']);
    }
    $user = User::findById((int)$tokenRow['user_id']);
    if (!$user) {
        json_response(401, ['success' => false, 'error' => '用户不存在']);
    }
    if ((int)$user['status'] !== 1) {
        json_response(403, ['success' => false, 'error' => '账号已被禁用']);
    }
    // 小程序端放宽邮箱验证限制：允许未验证邮箱的用户使用
    // PC 端不走此 API，不受影响
    return $user;
}

function build_file_url(?string $relativePath): ?string {
    if ($relativePath === null || $relativePath === '') {
        return null;
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $relativePath = ltrim($relativePath, '/');
    return $scheme . '://' . $host . '/uploads/' . $relativePath;
}

function build_user_payload(array $user): array {
    return [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'nickname' => $user['nickname'],
        'email' => $user['email'],
        'role' => $user['role'],
        'theme_mode' => $user['theme_mode'] ?? 'light',
        'budget_reminder_enabled' => isset($user['budget_reminder_enabled']) ? (int)$user['budget_reminder_enabled'] === 1 : true,
        'avatar_url' => build_file_url($user['avatar_path'] ?? null),
    ];
}

/**
 * 兼容数据库编码（如仅 utf8 而非 utf8mb4），对微信昵称做一次简单清洗：
 * - 去掉 4 字节及以上的 Unicode 字符（大部分表情符号），避免插入时出现 "Incorrect string value"。
 * - 保留常规中文、英文与常用符号。
 */
function sanitize_wechat_nickname(string $nickname): string {
    // 移除 4 字节以上的 Unicode 字符
    $clean = @preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $nickname);
    if ($clean === null) {
        $clean = $nickname; // 正则失败时退回原值
    }
    $clean = trim($clean);
    return $clean;
}

function normalize_trans_time(?string $input): string {
    $input = trim((string)$input);
    if ($input === '') {
        return date('Y-m-d H:i:s');
    }
    if (strpos($input, 'T') !== false) {
        $input = str_replace('T', ' ', $input);
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $input)) {
        $input .= ':00';
    }
    $dt = date_create($input);
    if ($dt === false) {
        return date('Y-m-d H:i:s');
    }
    return $dt->format('Y-m-d H:i:s');
}

function apply_balance_change(string $type, int $fromAccountId, int $toAccountId, float $amount, int $direction): void {
    if ($amount <= 0) {
        return;
    }
    $delta = $amount * $direction;
    if ($type === 'expense') {
        if ($fromAccountId) {
            Account::adjustBalance($fromAccountId, -$delta);
        }
    } else {
        if ($toAccountId) {
            Account::adjustBalance($toAccountId, $delta);
        }
    }
}

function summarize_budget_by_month(int $userId, int $year, int $month): array {
    $budgets = Budget::listByUserMonth($userId, $year, $month);
    if (empty($budgets)) {
        return [0.0, 0.0];
    }

    $pdo = Database::getConnection();
    $totalBudgetExpense = 0.0;
    $totalUsedExpense = 0.0;

    foreach ($budgets as $b) {
        $sql = 'SELECT COALESCE(SUM(amount),0) AS used_amount FROM transactions WHERE user_id = :uid AND type = :type AND YEAR(trans_time) = :y AND MONTH(trans_time) = :m';
        $params = [
            ':uid' => $userId,
            ':type' => $b['type'],
            ':y' => $year,
            ':m' => $month,
        ];
        if (!empty($b['category_id'])) {
            $sql .= ' AND category_id = :cid';
            $params[':cid'] = $b['category_id'];
        }
        if (!empty($b['item_id'])) {
            $sql .= ' AND item_id = :iid';
            $params[':iid'] = $b['item_id'];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['used_amount' => 0];
        $usedAmount = (float)$row['used_amount'];

        if ($b['type'] === 'expense') {
            $totalBudgetExpense += (float)$b['amount'];
            $totalUsedExpense += $usedAmount;
        }
    }

    return [$totalBudgetExpense, $totalUsedExpense];
}

function build_signed_path(string $path, int $ts, string $nonce, string $signature): string {
    if (strpos($path, '?') === false) {
        return $path . '?ts=' . $ts . '&nonce=' . $nonce . '&sig=' . $signature;
    }
    return $path . '&ts=' . $ts . '&nonce=' . $nonce . '&sig=' . $signature;
}

switch ($route) {
    case 'share/sign': {
        // 为小程序分享生成签名，前端在 onLoad 等时机调用
        $body = parse_json_body();
        $path = trim((string)($body['path'] ?? ''));
        if ($path === '') {
            json_response(400, ['success' => false, 'error' => '缺少 path']);
        }

        $secret = Config::get('wechat.share_secret', '');
        if ($secret === '') {
            json_response(500, ['success' => false, 'error' => '未配置分享签名密钥']);
        }

        $ts = time();
        $nonce = bin2hex(random_bytes(8));
        $payload = $path . '|' . $ts . '|' . $nonce;
        $signature = hash_hmac('sha256', $payload, $secret);
        $signedPath = build_signed_path($path, $ts, $nonce, $signature);

        json_response(200, [
            'success' => true,
            'path' => $path,
            'ts' => $ts,
            'nonce' => $nonce,
            'signature' => $signature,
            'signed_path' => $signedPath,
        ]);
        break;
    }
    case 'wechat/bind-by-token': {
        $body = parse_json_body();
        $code = trim((string)($body['code'] ?? ''));
        $bindToken = trim((string)($body['token'] ?? ''));
        if ($code === '' || $bindToken === '') {
            json_response(400, ['success' => false, 'error' => '缺少参数']);
        }

        $res = WeChatMiniApp::code2Session($code);
        if (!$res['success'] || !$res['openid']) {
            json_response(400, ['success' => false, 'error' => $res['error'] ?? '微信登录失败']);
        }
        $openid = (string)$res['openid'];
        $unionid = $res['unionid'] ?? null;

        $row = LoginToken::findByToken($bindToken);
        if (!$row) {
            json_response(400, ['success' => false, 'error' => '二维码无效或已过期']);
        }
        if (($row['status'] ?? '') !== 'pending') {
            json_response(400, ['success' => false, 'error' => '二维码已使用或失效']);
        }
        $expiresAt = strtotime((string)$row['expires_at'] ?? '') ?: 0;
        if ($expiresAt > 0 && $expiresAt < time()) {
            json_response(400, ['success' => false, 'error' => '二维码已过期']);
        }
        $userId = (int)($row['user_id'] ?? 0);
        if ($userId <= 0) {
            json_response(400, ['success' => false, 'error' => '二维码无效']);
        }

        if (UserWechatBinding::findByOpenid($openid)) {
            json_response(400, ['success' => false, 'error' => '该微信已绑定过账号']);
        }

        $bindingId = UserWechatBinding::create($userId, $openid, $unionid ? (string)$unionid : null);
        UserWechatBinding::updateLastLogin($bindingId);

        // 标记该绑定二维码已用
        LoginToken::confirm($bindToken, $userId);

        $token = ApiToken::createToken($userId, 'miniapp', 30 * 24 * 60 * 60);
        $user = User::findById($userId);

        json_response(200, [
            'success' => true,
            'token' => $token,
            'user' => build_user_payload($user),
        ]);
        break;
    }
    case 'wechat/bind-by-password': {
        // 小程序端通过账号密码直接绑定，无需扫码
        $body = json_body();
        $code = trim($body['code'] ?? '');
        $account = trim($body['account'] ?? ''); // 用户名或邮箱
        $password = (string)($body['password'] ?? '');

        if ($code === '' || $account === '' || $password === '') {
            json_response(400, ['success' => false, 'error' => '参数不完整']);
        }

        try {
            $wx = new \App\Service\WeChatMiniApp();
            $session = $wx->code2Session($code);
            $openid = $session['openid'] ?? null;
            $unionid = $session['unionid'] ?? null;
            if (!$openid) {
                json_response(400, ['success' => false, 'error' => '获取 openid 失败']);
            }

            // 账号查找（用户名或邮箱）
            $user = filter_var($account, FILTER_VALIDATE_EMAIL)
                ? \App\Model\User::findByEmail($account)
                : \App\Model\User::findByUsername($account);
            if (!$user || !password_verify($password, $user['password_hash'])) {
                json_response(401, ['success' => false, 'error' => '账号或密码错误']);
            }

            $userId = (int)$user['id'];

            // 确保 openid 未绑定其他账号
            $exists = \App\Model\UserWechatBinding::findByOpenid($openid);
            if ($exists && (int)$exists['user_id'] !== $userId) {
                json_response(409, ['success' => false, 'error' => '该微信已绑定其他账号']);
            }

            if ($exists) {
                \App\Model\UserWechatBinding::updateLastLogin((int)$exists['id']);
            } else {
                \App\Model\UserWechatBinding::create($userId, $openid, $unionid);
            }

            $apiToken = \App\Model\ApiToken::createToken($userId, 'miniapp', 30 * 24 * 60 * 60);
            json_response(200, [
                'success' => true,
                'token' => $apiToken,
                'user' => build_user_payload($user),
            ]);
        } catch (\Throwable $e) {
            json_response(500, ['success' => false, 'error' => '绑定失败：' . $e->getMessage()]);
        }
        break;
    }
    case 'wechat/login-or-check-bind': {
        $body = parse_json_body();
        $code = trim((string)($body['code'] ?? ''));
        if ($code === '') {
            json_response(400, ['success' => false, 'error' => '缺少 code']);
        }

        $res = WeChatMiniApp::code2Session($code);
        if (!$res['success'] || !$res['openid']) {
            json_response(400, ['success' => false, 'error' => $res['error'] ?? '微信登录失败']);
        }
        $openid = (string)$res['openid'];
        $unionid = $res['unionid'] ?? null;

        $binding = UserWechatBinding::findByOpenid($openid);
        if ($binding) {
            $user = User::findById((int)$binding['user_id']);
            if (!$user) {
                json_response(400, ['success' => false, 'error' => '绑定的用户不存在']);
            }
            if ((int)$user['status'] !== 1) {
                json_response(403, ['success' => false, 'error' => '账号已被禁用']);
            }
            if ((int)$user['email_verified'] !== 1) {
                json_response(403, ['success' => false, 'error' => '邮箱尚未验证']);
            }

            UserWechatBinding::updateLastLogin((int)$binding['id']);
            $token = ApiToken::createToken((int)$user['id'], 'miniapp', 30 * 24 * 60 * 60);

            json_response(200, [
                'success' => true,
                'need_bind' => false,
                'token' => $token,
                'user' => build_user_payload($user),
            ]);
        } else {
            json_response(200, [
                'success' => true,
                'need_bind' => true,
            ]);
        }
        break;
    }

    // 新增：小程序一键登录（已绑定→直接登录；未绑定→自动注册+绑定→登录）
    case 'wechat/auto-login': {
        try {
            $body = parse_json_body();
            $code = trim((string)($body['code'] ?? ''));
            $nickname = trim((string)($body['nickname'] ?? ''));
            $avatarUrl = trim((string)($body['avatar_url'] ?? ''));
            if ($code === '') {
                json_response(400, ['success' => false, 'error' => '缺少 code']);
            }

            $res = WeChatMiniApp::code2Session($code);
            if (!$res['success'] || !$res['openid']) {
                json_response(400, ['success' => false, 'error' => $res['error'] ?? '微信登录失败']);
            }
            $openid = (string)$res['openid'];
            $unionid = $res['unionid'] ?? null;

            $binding = UserWechatBinding::findByOpenid($openid);
            if ($binding) {
                $user = User::findById((int)$binding['user_id']);
                if (!$user) {
                    json_response(400, ['success' => false, 'error' => '绑定的用户不存在']);
                }
                if ((int)$user['status'] !== 1) {
                    json_response(403, ['success' => false, 'error' => '账号已被禁用']);
                }

                // 若当前用户昵称仍然是占位的“微信用户”，且本次登录携带了真实昵称，则自动刷新为微信昵称
                if ($nickname !== '' && (string)($user['nickname'] ?? '') === '微信用户') {
                    $nicknameClean = sanitize_wechat_nickname($nickname);
                    if ($nicknameClean !== '') {
                        if (mb_strlen($nicknameClean, 'UTF-8') > 50) {
                            $nicknameClean = mb_substr($nicknameClean, 0, 50, 'UTF-8');
                        }
                        User::updateProfile((int)$user['id'], (string)$user['username'], $nicknameClean);
                        $user = User::findById((int)$user['id']);
                    }
                }

                // 若提供了头像 URL，则尝试更新本地头像
                if ($avatarUrl !== '') {
                    $oldAvatar = $user['avatar_path'] ?? null;
                    $newAvatar = Upload::saveAvatarFromUrl((int)$user['id'], $avatarUrl);
                    if ($newAvatar !== null) {
                        \App\Model\User::updateAvatarPath((int)$user['id'], $newAvatar);
                        if ($oldAvatar && $oldAvatar !== $newAvatar) {
                            Upload::deleteByRelativePath($oldAvatar);
                        }
                        $user = User::findById((int)$user['id']);
                    }
                }

                UserWechatBinding::updateLastLogin((int)$binding['id']);
                $token = ApiToken::createToken((int)$user['id'], 'miniapp', 30 * 24 * 60 * 60);
                json_response(200, [
                    'success' => true,
                    'token' => $token,
                    'user' => build_user_payload($user),
                ]);
            } else {
                // 自动注册：用户名用 openid，昵称优先客户端传入，否则设为“微信用户”
                $username = $openid;
                if ($nickname !== '') {
                    $nickname = sanitize_wechat_nickname($nickname);
                }
                $nickname = $nickname !== '' ? $nickname : '微信用户';
                $avatarUrl = trim((string)($body['avatar_url'] ?? ''));

                // 为避免 email 唯一约束冲突，为每个小程序用户生成一个占位邮箱
                // 同一 openid 只会走一次自动注册分支，因此该邮箱也天然唯一
                $emailLocal = 'wx_' . substr(hash('sha256', $openid), 0, 16);
                $email = $emailLocal . '@miniapp.local';

                // 生成一个随机密码哈希，占位用；用户如需在网页端登录，可在引导页设置密码
                $randomPlain = bin2hex(random_bytes(8));
                $passwordHash = password_hash($randomPlain, PASSWORD_DEFAULT);

                // 若用户名已存在（极少见），追加短随机后缀
                $tryName = $username;
                $i = 0;
                while (User::findByUsername($tryName)) {
                    $i++;
                    $tryName = $username . '_' . substr(bin2hex(random_bytes(2)), 0, 3);
                    if ($i > 5) { break; }
                }
                $username = $tryName;

                $userId = User::create($username, $nickname, $email, $passwordHash, 'miniapp');

                // 绑定 openid
                $bindingId = UserWechatBinding::create($userId, $openid, $unionid ? (string)$unionid : null);
                UserWechatBinding::updateLastLogin($bindingId);

                // 若有微信头像 URL，尝试同步为本地头像
                if ($avatarUrl !== '') {
                    $newAvatar = Upload::saveAvatarFromUrl($userId, $avatarUrl);
                    if ($newAvatar !== null) {
                        User::updateAvatarPath($userId, $newAvatar);
                    }
                }

                // 新用户注入默认数据（分类/项目/账户）
                Seeder::seedIfEmpty($userId);

                $token = ApiToken::createToken($userId, 'miniapp', 30 * 24 * 60 * 60);
                $user = User::findById($userId);
                json_response(200, [
                    'success' => true,
                    'token' => $token,
                    'user' => build_user_payload($user),
                ]);
            }
        } catch (\Throwable $e) {
            json_response(500, ['success' => false, 'error' => '自动登录失败：' . $e->getMessage()]);
        }
        break;
    }

    case 'wechat/bind-existing': {
        $body = parse_json_body();
        $code = trim((string)($body['code'] ?? ''));
        $account = trim((string)($body['account'] ?? ''));
        $password = (string)($body['password'] ?? '');
        if ($code === '' || $account === '' || $password === '') {
            json_response(400, ['success' => false, 'error' => '参数不完整']);
        }

        $res = WeChatMiniApp::code2Session($code);
        if (!$res['success'] || !$res['openid']) {
            json_response(400, ['success' => false, 'error' => $res['error'] ?? '微信登录失败']);
        }
        $openid = (string)$res['openid'];
        $unionid = $res['unionid'] ?? null;

        if (UserWechatBinding::findByOpenid($openid)) {
            json_response(400, ['success' => false, 'error' => '该微信已绑定过账号']);
        }

        $user = null;
        if (filter_var($account, FILTER_VALIDATE_EMAIL)) {
            $user = User::findByEmail($account);
        } else {
            $user = User::findByUsername($account);
        }
        if (!$user || !password_verify($password, $user['password_hash'])) {
            json_response(401, ['success' => false, 'error' => '账号或密码错误']);
        }
        if ((int)$user['status'] !== 1) {
            json_response(403, ['success' => false, 'error' => '账号已被禁用']);
        }

        $bindingId = UserWechatBinding::create((int)$user['id'], $openid, $unionid ? (string)$unionid : null);
        UserWechatBinding::updateLastLogin($bindingId);

        $token = ApiToken::createToken((int)$user['id'], 'miniapp', 30 * 24 * 60 * 60);
        json_response(200, [
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'nickname' => $user['nickname'],
                'email' => $user['email'],
                'role' => $user['role'],
                'theme_mode' => $user['theme_mode'] ?? 'light',
            ],
        ]);
        break;
    }

    case 'wechat/register-bind': {
        $body = parse_json_body();
        $code = trim((string)($body['code'] ?? ''));
        $username = trim((string)($body['username'] ?? ''));
        $nickname = trim((string)($body['nickname'] ?? ''));
        $email = trim((string)($body['email'] ?? ''));
        $password = (string)($body['password'] ?? '');

        if ($code === '' || $username === '' || $nickname === '' || $email === '' || $password === '') {
            json_response(400, ['success' => false, 'error' => '请完整填写所有必填信息']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(400, ['success' => false, 'error' => '邮箱格式不正确']);
        }
        if (User::findByUsername($username)) {
            json_response(400, ['success' => false, 'error' => '用户名已存在']);
        }
        if (User::findByEmail($email)) {
            json_response(400, ['success' => false, 'error' => '邮箱已被使用']);
        }

        $res = WeChatMiniApp::code2Session($code);
        if (!$res['success'] || !$res['openid']) {
            json_response(400, ['success' => false, 'error' => $res['error'] ?? '微信登录失败']);
        }
        $openid = (string)$res['openid'];
        $unionid = $res['unionid'] ?? null;

        if (UserWechatBinding::findByOpenid($openid)) {
            json_response(400, ['success' => false, 'error' => '该微信已绑定过账号']);
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $userId = User::create($username, $nickname, $email, $passwordHash, 'miniapp');
        // 小程序注册默认视为已完成邮箱验证，方便直接使用
        User::markEmailVerified($userId);

        $bindingId = UserWechatBinding::create($userId, $openid, $unionid ? (string)$unionid : null);
        UserWechatBinding::updateLastLogin($bindingId);

        // 同步微信头像（如传入 avatar_url）
        $avatarUrl = trim((string)($body['avatar_url'] ?? ''));
        if ($avatarUrl !== '') {
            $newAvatar = Upload::saveAvatarFromUrl($userId, $avatarUrl);
            if ($newAvatar !== null) {
                User::updateAvatarPath($userId, $newAvatar);
            }
        }

        $token = ApiToken::createToken($userId, 'miniapp', 30 * 24 * 60 * 60);
        $user = User::findById($userId);

        json_response(200, [
            'success' => true,
            'token' => $token,
            'user' => build_user_payload($user),
        ]);
        break;
    }

    case 'auth/profile': {
        $user = require_auth_user();
        json_response(200, [
            'success' => true,
            'user' => build_user_payload($user),
        ]);
        break;
    }

    case 'home/overview': {
        $user = require_auth_user();
        $userId = (int)$user['id'];
        $pdo = Database::getConnection();

        // 账户资产概览
        $stmt = $pdo->prepare('SELECT ag.code, SUM(a.current_balance) AS total
            FROM accounts a
            JOIN account_groups ag ON a.group_id = ag.id
            WHERE a.user_id = :uid
            GROUP BY ag.code');
        $stmt->execute([':uid' => $userId]);
        $balances = [
            'financial' => 0.0,
            'saving' => 0.0,
            'receivable' => 0.0,
            'debt' => 0.0,
            'other' => 0.0,
        ];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $code = $row['code'] ?? '';
            if (array_key_exists($code, $balances)) {
                $balances[$code] = (float)$row['total'];
            }
        }
        $totalAssets = $balances['financial'] + $balances['saving'] + $balances['receivable'] + $balances['other'];
        $totalDebt = $balances['debt'];
        $netAssets = $totalAssets + $totalDebt; // 负债为负数

        // 本月收支
        $year = (int)date('Y');
        $month = (int)date('n');

        $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS total FROM transactions WHERE user_id = :uid AND type = "expense" AND YEAR(trans_time) = :y AND MONTH(trans_time) = :m');
        $stmt->execute([':uid' => $userId, ':y' => $year, ':m' => $month]);
        $monthExpense = (float)($stmt->fetchColumn() ?: 0);

        $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS total FROM transactions WHERE user_id = :uid AND type = "income" AND YEAR(trans_time) = :y AND MONTH(trans_time) = :m');
        $stmt->execute([':uid' => $userId, ':y' => $year, ':m' => $month]);
        $monthIncome = (float)($stmt->fetchColumn() ?: 0);
        $monthNet = $monthIncome - $monthExpense;

        // 今日收支
        $today = date('Y-m-d');
        $stmt = $pdo->prepare('SELECT type, COALESCE(SUM(amount),0) AS total FROM transactions WHERE user_id = :uid AND DATE(trans_time) = :d AND type IN ("income","expense") GROUP BY type');
        $stmt->execute([':uid' => $userId, ':d' => $today]);
        $todayIncome = 0.0;
        $todayExpense = 0.0;
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($row['type'] === 'income') {
                $todayIncome = (float)$row['total'];
            } elseif ($row['type'] === 'expense') {
                $todayExpense = (float)$row['total'];
            }
        }
        $todayNet = $todayIncome - $todayExpense;

        // 当月预算汇总（支出）
        [$monthBudgetTotal, $monthBudgetUsed] = summarize_budget_by_month($userId, $year, $month);
        $monthBudgetRemain = max(0.0, $monthBudgetTotal - $monthBudgetUsed);
        $monthBudgetRate = $monthBudgetTotal > 0 ? ($monthBudgetUsed / $monthBudgetTotal) : 0.0;
        $monthBudgetOver = $monthBudgetTotal > 0 && $monthBudgetUsed > $monthBudgetTotal;

        // 最近 5 条流水
        $stmt = $pdo->prepare('SELECT t.*, c.name AS category_name, i.name AS item_name,
                fa.name AS from_account_name, ta.name AS to_account_name
            FROM transactions t
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN items i ON t.item_id = i.id
            LEFT JOIN accounts fa ON t.from_account_id = fa.id
            LEFT JOIN accounts ta ON t.to_account_id = ta.id
            WHERE t.user_id = :uid
            ORDER BY t.trans_time DESC, t.id DESC
            LIMIT 5');
        $stmt->execute([':uid' => $userId]);
        $recentRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $recent = [];
        foreach ($recentRows as $t) {
            $recent[] = [
                'id' => (int)$t['id'],
                'type' => $t['type'],
                'amount' => (float)$t['amount'],
                'category_name' => $t['category_name'] ?? null,
                'item_name' => $t['item_name'] ?? null,
                'from_account_name' => $t['from_account_name'] ?? null,
                'to_account_name' => $t['to_account_name'] ?? null,
                'trans_time' => $t['trans_time'],
                'remark' => $t['remark'] ?? null,
                'attachment_url' => $t['attachment_path'] ? build_file_url($t['attachment_path']) : null,
            ];
        }

        $budgetReminderEnabled = isset($user['budget_reminder_enabled']) ? (int)$user['budget_reminder_enabled'] === 1 : true;

            // 最近一条未读公告（用于小程序首页弹窗）
            $latestAnnouncement = null;
            try {
                $row = Announcement::findLatestUnreadForUser($userId);
                if ($row) {
                    $latestAnnouncement = [
                        'id' => (int)$row['id'],
                        'title' => (string)($row['title'] ?? ''),
                        'content' => (string)($row['content'] ?? ''),
                        'scheduled_at' => (string)($row['scheduled_at'] ?? ''),
                    ];
                }
            } catch (\Throwable $e) {
                $latestAnnouncement = null;
            }

        json_response(200, [
            'success' => true,
            'assets' => [
                'financial' => $balances['financial'],
                'saving' => $balances['saving'],
                'receivable' => $balances['receivable'],
                'debt' => $balances['debt'],
                'other' => $balances['other'],
                'total_assets' => $totalAssets,
                'total_debt' => $totalDebt,
                'net_assets' => $netAssets,
            ],
            'today' => [
                'income' => $todayIncome,
                'expense' => $todayExpense,
                'net' => $todayNet,
            ],
            'month' => [
                'year' => $year,
                'month' => $month,
                'income' => $monthIncome,
                'expense' => $monthExpense,
                'net' => $monthNet,
                'budget_total' => $monthBudgetTotal,
                'budget_used' => $monthBudgetUsed,
                'budget_remain' => $monthBudgetRemain,
                'budget_rate' => $monthBudgetRate,
                'budget_over' => $monthBudgetOver,
            ],
            'budget_reminder_enabled' => $budgetReminderEnabled,
            'recent_transactions' => $recent,
            'announcement' => $latestAnnouncement,
        ]);
        break;
    }

    case 'announcement/mark-read': {
        $user = require_auth_user();
        $body = parse_json_body();
        $announcementId = (int)($body['announcement_id'] ?? 0);
        if ($announcementId > 0) {
            try {
                AnnouncementRead::markRead($announcementId, (int)$user['id'], 'miniapp');
            } catch (\Throwable $e) {
                // 忽略计数错误，不影响前端体验
            }
        }
        json_response(200, ['success' => true]);
        break;
    }

    case 'settings/update-budget-reminder': {
        $user = require_auth_user();
        $body = parse_json_body();
        $enabled = !empty($body['enabled']);

        User::updateBudgetReminder((int)$user['id'], $enabled);
        $user = User::findById((int)$user['id']);

        json_response(200, [
            'success' => true,
            'budget_reminder_enabled' => isset($user['budget_reminder_enabled']) ? (int)$user['budget_reminder_enabled'] === 1 : true,
        ]);
        break;
    }

    // 修改用户名（需唯一）
    case 'settings/update-username': {
        $user = require_auth_user();
        $body = parse_json_body();
        $new = trim((string)($body['username'] ?? ''));
        if ($new === '') {
            json_response(400, ['success' => false, 'error' => '用户名不能为空']);
        }
        $exists = User::findByUsername($new);
        if ($exists && (int)$exists['id'] !== (int)$user['id']) {
            json_response(409, ['success' => false, 'error' => '用户名已被占用']);
        }
        User::updateUsername((int)$user['id'], $new);
        $u = User::findById((int)$user['id']);
        json_response(200, [
            'success' => true,
            'user' => build_user_payload($u),
        ]);
        break;
    }

    // 从微信资料同步昵称和头像，或仅手动更新昵称
    case 'settings/update-nickname-from-wechat': {
        $user = require_auth_user();
        $body = parse_json_body();
        $nickname = trim((string)($body['nickname'] ?? ''));
        $avatarUrl = trim((string)($body['avatar_url'] ?? ''));
        if ($nickname === '') {
            json_response(400, ['success' => false, 'error' => '昵称不能为空']);
        }
        // 清洗微信昵称中的 emoji，避免数据库编码不兼容
        $nicknameClean = sanitize_wechat_nickname($nickname);
        if ($nicknameClean === '') {
            json_response(400, ['success' => false, 'error' => '昵称暂不支持只包含表情，请输入部分文字']);
        }
        if (mb_strlen($nicknameClean, 'UTF-8') > 50) {
            $nicknameClean = mb_substr($nicknameClean, 0, 50, 'UTF-8');
        }

        User::updateProfile((int)$user['id'], (string)$user['username'], $nicknameClean);
        $u = User::findById((int)$user['id']);

        if ($avatarUrl !== '') {
            $oldAvatar = $u['avatar_path'] ?? null;
            $newAvatar = Upload::saveAvatarFromUrl((int)$u['id'], $avatarUrl);
            if ($newAvatar !== null) {
                User::updateAvatarPath((int)$u['id'], $newAvatar);
                if ($oldAvatar && $oldAvatar !== $newAvatar) {
                    Upload::deleteByRelativePath($oldAvatar);
                }
                $u = User::findById((int)$u['id']);
            }
        }

        json_response(200, [
            'success' => true,
            'user' => build_user_payload($u),
        ]);
        break;
    }

    // 小程序上传头像文件（手动选择图片）
    case 'settings/upload-avatar': {
        $user = require_auth_user();
        if (empty($_FILES['avatar']) || !is_array($_FILES['avatar'])) {
            json_response(400, ['success' => false, 'error' => '未接收到头像文件']);
        }
        $oldAvatar = $user['avatar_path'] ?? null;
        $newAvatar = Upload::saveAvatar((int)$user['id'], $_FILES['avatar']);
        if ($newAvatar === null) {
            json_response(400, ['success' => false, 'error' => '头像上传失败，请确认大小不超过 5MB 且为图片格式']);
        }
        User::updateAvatarPath((int)$user['id'], $newAvatar);
        if ($oldAvatar && $oldAvatar !== $newAvatar) {
            Upload::deleteByRelativePath($oldAvatar);
        }
        $u = User::findById((int)$user['id']);
        json_response(200, [
            'success' => true,
            'user' => build_user_payload($u),
        ]);
        break;
    }

    // 首次设置邮箱（无验证），二次更换需验证
    case 'settings/set-email': {
        $user = require_auth_user();
        $body = parse_json_body();
        $email = trim((string)($body['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(400, ['success' => false, 'error' => '邮箱格式不正确']);
        }
        if (!empty($user['email'])) {
            json_response(409, ['success' => false, 'error' => '已设置邮箱，请使用更换邮箱流程']);
        }
        User::updateEmail((int)$user['id'], $email, true);
        $u = User::findById((int)$user['id']);
        json_response(200, [
            'success' => true,
            'email' => $u['email'],
        ]);
        break;
    }

    // 直接更换邮箱（唯一性校验，不走邮件验证）
    case 'settings/change-email': {
        $user = require_auth_user();
        $body = parse_json_body();
        $email = trim((string)($body['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(400, ['success' => false, 'error' => '邮箱格式不正确']);
        }
        if ($email === (string)$user['email']) {
            json_response(400, ['success' => false, 'error' => '新邮箱不能与原邮箱相同']);
        }
        $exists = User::findByEmail($email);
        if ($exists && (int)$exists['id'] !== (int)$user['id']) {
            json_response(409, ['success' => false, 'error' => '该邮箱已被使用']);
        }
        User::updateEmail((int)$user['id'], $email, true);
        $u = User::findById((int)$user['id']);
        json_response(200, [
            'success' => true,
            'email' => $u['email'],
        ]);
        break;
    }

    // 申请更换邮箱（发送验证邮件）
    case 'settings/request-change-email': {
        $user = require_auth_user();
        $body = parse_json_body();
        $email = trim((string)($body['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(400, ['success' => false, 'error' => '邮箱格式不正确']);
        }
        if ($email === (string)$user['email']) {
            json_response(400, ['success' => false, 'error' => '新邮箱不能与原邮箱相同']);
        }
        $token = bin2hex(random_bytes(16));
        $expiresAt = date('Y-m-d H:i:s', time() + 24 * 60 * 60);
        \App\Model\EmailToken::create((int)$user['id'], $email, 'change_email', $token, $expiresAt);

        $base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $confirmUrl = $base . '/public/api.php?route=auth/confirm-email&token=' . urlencode($token);
        $sent = \App\Service\Mailer::send($email, $user['nickname'] ?? $user['username'] ?? '', '邮箱更换验证',
            '<p>请点击以下链接完成邮箱更换：</p><p><a href="' . htmlspecialchars($confirmUrl, ENT_QUOTES) . '">' . htmlspecialchars($confirmUrl, ENT_QUOTES) . '</a></p><p>若非本人操作，请忽略本邮件。</p>');

        if (!$sent) {
            json_response(500, ['success' => false, 'error' => '发送验证邮件失败']);
        }
        json_response(200, ['success' => true, 'message' => '验证邮件已发送至新邮箱']);
        break;
    }

    // 邮箱更换确认（邮件链接访问，返回简单 HTML）
    case 'auth/confirm-email': {
        $token = isset($_GET['token']) ? (string)$_GET['token'] : '';
        if ($token === '') {
            header('Content-Type: text/html; charset=utf-8');
            echo '<h3>链接无效</h3>';
            exit;
        }
        $row = \App\Model\EmailToken::findValid($token, 'change_email');
        if (!$row) {
            header('Content-Type: text/html; charset=utf-8');
            echo '<h3>链接无效或已过期</h3>';
            exit;
        }
        User::updateEmail((int)$row['user_id'], (string)$row['email'], true);
        \App\Model\EmailToken::markUsed((int)$row['id']);
        header('Content-Type: text/html; charset=utf-8');
        echo '<h3>邮箱更换成功</h3><p>已完成验证，可返回小程序使用。</p>';
        exit;
    }

    // 设置/修改登录密码（小程序用户默认无密码）
    case 'settings/set-password': {
        $user = require_auth_user();
        $body = parse_json_body();
        $password = (string)($body['password'] ?? '');
        $confirm = (string)($body['confirm'] ?? '');
        if ($password === '' || $confirm === '') {
            json_response(400, ['success' => false, 'error' => '请输入密码']);
        }
        if ($password !== $confirm) {
            json_response(400, ['success' => false, 'error' => '两次输入不一致']);
        }
        if (strlen($password) < 6) {
            json_response(400, ['success' => false, 'error' => '密码至少 6 位']);
        }
        $hash = password_hash($password, PASSWORD_BCRYPT);
        User::updatePassword((int)$user['id'], $hash);
        json_response(200, ['success' => true]);
        break;
    }

    case 'feedback/create': {
        $user = require_auth_user();

        $raw = file_get_contents('php://input');
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (stripos($contentType, 'application/json') !== false) {
            $body = json_decode($raw ?: '[]', true) ?: [];
            $category = (string)($body['category'] ?? Feedback::CATEGORY_SUGGEST);
            $content = trim((string)($body['content'] ?? ''));
            $images = isset($body['images']) && is_array($body['images']) ? $body['images'] : [];
            $imagePaths = [];
            foreach ($images as $img) {
                if (is_string($img) && $img !== '') {
                    $imagePaths[] = $img;
                }
            }
        } else {
            $post = $_POST;
            $category = (string)($post['category'] ?? Feedback::CATEGORY_SUGGEST);
            $content = trim((string)($post['content'] ?? ''));
            $imagePaths = [];

            if (isset($_FILES['images']) && is_array($_FILES['images']['name'] ?? null)) {
                $fileCount = count($_FILES['images']['name']);
                for ($i = 0; $i < $fileCount; $i++) {
                    $file = [
                        'name' => $_FILES['images']['name'][$i] ?? null,
                        'type' => $_FILES['images']['type'][$i] ?? null,
                        'tmp_name' => $_FILES['images']['tmp_name'][$i] ?? null,
                        'error' => $_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                        'size' => $_FILES['images']['size'][$i] ?? 0,
                    ];
                    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    $saved = Upload::saveAttachment((int)$user['id'], $file);
                    if ($saved !== null) {
                        $imagePaths[] = $saved;
                    }
                }
            }
        }

        if ($content === '') {
            json_response(400, ['success' => false, 'error' => '请填写问题描述']);
        }

        $category = Feedback::normalizeCategory($category);
        $id = Feedback::create((int)$user['id'], $category, $content, $imagePaths);

        json_response(200, [
            'success' => true,
            'id' => $id,
        ]);
        break;
    }

    case 'feedback/list': {
        $user = require_auth_user();
        $limit = isset($_GET['limit']) ? max(1, min(300, (int)$_GET['limit'])) : 200;
        $rows = Feedback::listForFaq($limit);

        $list = [];
        foreach ($rows as $row) {
            $images = [];
            if (!empty($row['images_array']) && is_array($row['images_array'])) {
                foreach ($row['images_array'] as $img) {
                    if (!is_string($img) || $img === '') {
                        continue;
                    }
                    $images[] = [
                        'path' => $img,
                        'url' => build_file_url($img),
                    ];
                }
            }

            $list[] = [
                'id' => (int)$row['id'],
                'category' => $row['category'],
                'content' => $row['content'],
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'user_nickname' => $row['nickname'] ?? ($row['username'] ?? ''),
                'admin_reply' => $row['admin_reply'] ?? null,
                'admin_reply_at' => $row['admin_reply_at'] ?? null,
                'images' => $images,
            ];
        }

        json_response(200, [
            'success' => true,
            'feedbacks' => $list,
        ]);
        break;
    }

    case 'changelog/list': {
        // 为小程序等客户端提供简要更新日志列表
        $version = Config::get('app.version', 'v1.13.2');

        $entries = [
            [
                'version' => 'v1.13.2',
                'title' => 'v1.13.2（当前版本）',
                'items' => [
                    'PC 端记账明细页面图片凭证大图预览交互优化：移除预览弹窗右上角关闭按钮，保留点击空白区域关闭，修复少数环境下点击关闭按钮后页面卡住的问题。',
                ],
            ],
            [
                'version' => 'v1.13.1',
                'title' => 'v1.13.1',
                'items' => [
                    '小程序接口环境调整：统一将微信小程序的接口地址切换到新的 HTTPS 服务环境，提升访问稳定性与国内网络访问速度，更新日志中不再展示具体域名。',
                    '安全与隐私：调整小程序更新日志页底部联系方式文案，避免直接暴露具体邮箱/域名，引导通过内置「问题反馈」功能联系开发者。',
                ],
            ],
            [
                'version' => 'v1.12.1',
                'title' => 'v1.12.1',
                'items' => [
                    '小程序昵称改为应用内自定义：设置中心「昵称」支持直接修改并保存到后台，移除「同步微信昵称」按钮，避免微信统一返回「微信用户」导致昵称与预期不符。',
                    '自动登录昵称回写优化：小程序一键登录时，如检测到账号昵称仍为占位的「微信用户」且本次登录携带了昵称，将自动用本次昵称覆盖，保证后端与前端展示一致。',
                    '细节与体验：首页在返回时会重新读取最新的用户信息，确保在设置中心修改昵称后，首页欢迎语能立即展示最新昵称。',
                ],
            ],
            [
                'version' => 'v1.12.0',
                'title' => 'v1.12.0',
                'items' => [
                    '小程序一键登录体验优化：未注册用户支持自动注册 + 绑定 + 登录，并为自动注册账号生成占位邮箱与随机密码哈希，兼容 PC 端账号体系，避免数据库约束报错。',
                    '微信昵称同步：小程序设置中心新增「昵称 / 同步微信昵称」入口，授权后可一键同步当前微信昵称；后台用户管理中的昵称会自动更新为微信昵称。',
                    '稳定性与日志：为小程序自动登录接口增加异常捕获与明确错误提示，修复部分环境下 SQLSTATE[23000] 报错导致的一键登录失败问题。',
                ],
            ],
            [
                'version' => 'v1.11.0',
                'title' => 'v1.11.0',
                'items' => [
                    '小程序全局分享与签名：主要页面统一接入分享签名接口，支持「发送给朋友 / 分享到朋友圈」，分享路径自动附带防篡改签名参数。',
                    '首页与设置中心：概览页与设置中心接入统一分享；设置中心「检查更新」配合全局更新管理器，在检测到新版本时提示并支持一键重启应用更新。',
                    '页面与稳定性优化：预算管理、报表分析、图标库管理、问题反馈、扫码登录以及注册/绑定等页面全部接入统一分享逻辑，并修复多处 JSON 配置错误导致的小程序模拟器启动异常。',
                    '数据展示改进：小程序项目管理页的「所属分类」按「[类型] 分类名」展示，统一与 PC 端的显示风格，避免只看到分类 ID。',
                    '绑定与资料体验：PC 设置中心的微信绑定信息更清晰，支持查看绑定状态与解绑；小程序请求默认携带登录 Token，修复设置中心用户名 / 邮箱偶尔显示为「未登录 / 未设置」的问题。',
                    '更新日志一致性：PC 与小程序的更新日志页均增加「以 PC 端为准」提示，说明小程序发布存在平台审核延迟，避免误解为小程序一定是最新版本。',
                ],
            ],
            [
                'version' => 'v1.10.0',
                'title' => 'v1.10.0',
                'items' => [
                    '小程序一键登录：进入即自动注册/绑定并登录，首次即用；新用户自动注入默认分类/项目/账户（含图标）。',
                    '设置中心完善：支持修改用户名、首次绑定邮箱、直接更换邮箱（即时唯一性校验、无需邮件验证码）、设置/修改密码。',
                    'PC 扫码注册：保留表单注册；新增「扫码注册」页（静态小程序码），先在小程序完成注册，再用「设置→扫码登录PC」登录电脑端。',
                    '扫码登录引导：扫码登录后如缺少邮箱/密码，跳转到「完善资料」页，可选填后进入系统。',
                    '授权与兼容：放宽小程序/扫码登录的邮箱验证限制；修复 OpenID 绑定查询命名与小程序 Token 有效期问题；兼容缺少 register_source 的旧库。',
                    'UI/体验：设置页操作改为迷你按钮并优化布局；「扫码注册」页采用静态小程序码与清晰步骤；资源继续本地优先、CDN 兜底。',
                ],
            ],
            [
                'version' => 'v1.9.2',
                'title' => 'v1.9.2',
                'items' => [
                    '性能与稳定性：第三方库改为本地优先，CDN兜底（Bootstrap/Choices.js/Chart.js/qrcode.js），显著提升加载速度。',
                    '注册页优化：修复结构导致的空白问题；注册成功卡片展示小程序码与绑定二维码，并提供「暂不绑定/去登录」按钮。',
                    '系统设置增强：支持配置绑定二维码有效期与文案；用户管理新增生成绑定二维码与注入默认数据入口，并在同页展示二维码。',
                    '小程序接口优化：统一接口与展示链接的地址配置，提升国内网络访问速度与稳定性。',
                    '兼容性修复：旧库缺少 register_source 列的环境下，注册与系统设置用户列表均可正常使用。',
                ],
            ],
            [
                'version' => 'v1.9.1',
                'title' => 'v1.9.1',
                'items' => [
                    '访问入口优化：支持通过站点地址直接访问登录页，简化用户路径。',
                    '小程序接口优化：统一接口地址配置并清理旧配置残留，稳定性提升。',
                    '小程序入口优化：首页右上角新增「小程序扫码」按钮，弹窗展示根目录图片 xiaochengxu.png。',
                    '登录页增强：新增小程序码展示，便于先进入小程序启用扫码再在PC端登录。',
                    '缺陷修复：修复弹窗绑定位置与多余标签导致「小程序扫码」弹窗不显示的问题。',
                ],
            ],
            [
                'version' => 'v1.8.1',
                'title' => 'v1.8.1',
                'items' => [
                    '小程序首页资产总览卡片支持点击跳转到账户列表，底部快捷入口按钮宽度与间距优化。',
                    '记一笔与编辑记账页面的备注输入框支持自动行高，默认高度更紧凑，输入多行时自动展开。',
                    '流水明细页筛选区域默认收起，分类/项目/账户筛选合并为一行，并优化搜索与金额输入框的高度与占位提示。',
                    '流水明细列表上方新增操作提示文案，说明「点击条目可编辑，长按可删除」。',
                    '账户列表页新增提示说明账户编辑与删除需在 PC 端操作，避免误解。',
                    '预算列表为每条预算增加彩色进度条：使用率达 70% 显示黄色、80% 橙色、90% 红色，预算压力一目了然。',
                    '报表分析页分类金额按收入红色、支出绿色区分，收支占比对比更直观。',
                    '设置中心与更新日志页 UI 微调：新增「更新日志 / 问题反馈」并排按钮，并在更新日志底部展示开发者信息。',
                ],
            ],
            [
                'version' => 'v1.8.0',
                'title' => 'v1.8.0',
                'items' => [
                    '小程序新增「问题反馈 & FAQ」页面，支持搜索历史反馈并查看常见问答。',
                    '小程序反馈支持在「我要反馈」弹窗内上传截图，方便描述问题。',
                    '小程序反馈列表支持点击进入详情弹窗，查看完整问题、系统回复和相关图片。',
                    'PC 端问题反馈 / FAQ 页面支持管理员在回复时上传图片，图片会与用户截图统一展示。',
                    '系统设置 - 用户管理新增「微信绑定」列，展示账号是否已绑定微信小程序及最近登录时间。',
                ],
            ],
            [
                'version' => 'v1.7.0',
                'title' => 'v1.7.0',
                'items' => [
                    '新增微信小程序端，支持扫码登录后与 PC 端共用同一账户数据，实现随时随地记账。',
                    '小程序首页新增本月收支与预算卡片，并提供快捷入口「记一笔 / 明细 / 预算 / 设置」。',
                    '小程序记一笔与编辑记账页面支持账户、分类、项目图标展示，采用底部弹层方式选择，更贴近移动端交互。',
                    '小程序流水列表支持按照类型、分类、项目、账户、时间、金额区间和备注关键字多条件筛选，并展示分类 / 项目 / 账户图标。',
                    '后端注册流程调整：填写邮箱后无需验证即可完成注册；找回密码仍通过邮箱验证链接处理。',
                    '修复 PC 端明细页面单条删除在部分浏览器下无效的问题，统一复用批量删除逻辑，确保账户余额同步回滚。',
                ],
            ],
            [
                'version' => 'v1.6.2',
                'title' => 'v1.6.2',
                'items' => [
                    '记账页面与「今日记账明细」编辑弹窗中的下拉恢复按支出/收入类型联动，并在选项中展示图标。',
                    '优化新增/编辑记账金额输入框的高度和字号，提升触控输入体验。',
                    '为明细列表、账户/分类/项目管理等表格行增加浅绿色 hover 高亮，鼠标悬停时整行高亮。',
                    '为全局样式 app.css 增加版本号参数，解决浏览器长期缓存旧样式的问题。',
                ],
            ],
            [
                'version' => 'v1.6.1',
                'title' => 'v1.6.1',
                'items' => [
                    '修复会话自动退出逻辑始终按 24 小时计算的问题，统一从系统参数中读取自动退出时间。',
                    '统一首页、记账页、明细页、系统设置等路由的未操作超时行为，与手机桌面快捷方式保持一致。',
                ],
            ],
            [
                'version' => 'v1.6.0',
                'title' => 'v1.6.0',
                'items' => [
                    '登录安全加固：登录失败次数与临时锁定改为按账号全局统计，并区分密码错误与验证码错误。',
                    '移除「转账」记账类型，统一保留「支出 / 收入」，简化记账模型。',
                    '统计报表重构：新增按年度 / 季度 / 月度 / 今日 / 昨日 / 自定义等时间模式，并融合预算汇总信息。',
                ],
            ],
        ];

        json_response(200, [
            'success' => true,
            'app_version' => $version,
            'entries' => $entries,
        ]);
        break;
    }

    case 'accounts/list': {
        $user = require_auth_user();
        $accounts = Account::allByUser((int)$user['id']);
        $result = [];
        foreach ($accounts as $a) {
            $result[] = [
                'id' => (int)$a['id'],
                'group_id' => (int)$a['group_id'],
                'group_name' => $a['group_name'] ?? '',
                'group_code' => $a['group_code'] ?? '',
                'name' => $a['name'],
                'account_no' => $a['account_no'],
                'initial_balance' => (float)$a['initial_balance'],
                'current_balance' => (float)$a['current_balance'],
                'is_default' => (int)$a['is_default'],
                'icon_type' => $a['icon_type'],
                'icon_value' => $a['icon_value'],
                'icon_url' => $a['icon_type'] === 'file' ? build_file_url($a['icon_value']) : null,
            ];
        }
        json_response(200, ['success' => true, 'accounts' => $result]);
        break;
    }

    case 'accounts/create': {
        $user = require_auth_user();
        $body = parse_json_body();
        $groupId = (int)($body['group_id'] ?? 0);
        $name = trim((string)($body['name'] ?? ''));
        $accountNo = trim((string)($body['account_no'] ?? ''));
        $initial = isset($body['initial_balance']) ? (float)$body['initial_balance'] : 0.0;

        if ($groupId <= 0 || $name === '') {
            json_response(400, ['success' => false, 'error' => '请选择账户大类并填写账户名称']);
        }

        $iconType = null;
        $iconValue = null;
        $iconLibId = (int)($body['icon_library_id'] ?? 0);
        if ($iconLibId > 0) {
            $icon = IconLibrary::findByUser((int)$user['id'], $iconLibId);
            if ($icon) {
                $iconType = 'file';
                $iconValue = $icon['file_path'] ?? null;
            }
        }

        Account::create((int)$user['id'], $groupId, $name, $accountNo ?: null, $initial, $iconType, $iconValue);
        json_response(200, ['success' => true]);
        break;
    }

    case 'accounts/update': {
        $user = require_auth_user();
        $body = parse_json_body();
        $id = (int)($body['id'] ?? 0);
        $groupId = (int)($body['group_id'] ?? 0);
        $name = trim((string)($body['name'] ?? ''));
        $accountNo = trim((string)($body['account_no'] ?? ''));

        if ($id <= 0 || $groupId <= 0 || $name === '') {
            json_response(400, ['success' => false, 'error' => '参数不完整']);
        }
        $current = Account::findByUser((int)$user['id'], $id);
        if (!$current) {
            json_response(404, ['success' => false, 'error' => '账户不存在']);
        }

        $iconType = $current['icon_type'] ?? null;
        $iconValue = $current['icon_value'] ?? null;
        $iconLibId = (int)($body['icon_library_id'] ?? 0);
        $iconClear = !empty($body['icon_clear']);
        if ($iconClear) {
            $iconType = null;
            $iconValue = null;
        } elseif ($iconLibId > 0) {
            $icon = IconLibrary::findByUser((int)$user['id'], $iconLibId);
            if ($icon) {
                $iconType = 'file';
                $iconValue = $icon['file_path'] ?? null;
            }
        }
        Account::update((int)$user['id'], $id, $groupId, $name, $accountNo ?: null, $iconType, $iconValue);
        json_response(200, ['success' => true]);
        break;
    }

    case 'accounts/delete': {
        $user = require_auth_user();
        $body = parse_json_body();
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) {
            json_response(400, ['success' => false, 'error' => '参数不完整']);
        }
        $ok = Account::delete((int)$user['id'], $id);
        if (!$ok) {
            json_response(400, ['success' => false, 'error' => '该账户已有记账数据，无法删除']);
        }
        json_response(200, ['success' => true]);
        break;
    }

    case 'categories/list': {
        $user = require_auth_user();
        $type = isset($_GET['type']) && $_GET['type'] !== '' ? (string)$_GET['type'] : null;
        $categories = Category::allByUser((int)$user['id'], $type);
        $result = [];
        foreach ($categories as $c) {
            $result[] = [
                'id' => (int)$c['id'],
                'type' => $c['type'],
                'name' => $c['name'],
                'sort_order' => (int)$c['sort_order'],
                'icon_type' => $c['icon_type'],
                'icon_value' => $c['icon_value'],
                'icon_url' => $c['icon_type'] === 'file' ? build_file_url($c['icon_value']) : null,
            ];
        }
        json_response(200, ['success' => true, 'categories' => $result]);
        break;
    }

    case 'categories/create': {
        $user = require_auth_user();
        $body = parse_json_body();
        $type = trim((string)($body['type'] ?? ''));
        $name = trim((string)($body['name'] ?? ''));
        $sortOrder = isset($body['sort_order']) ? (int)$body['sort_order'] : 0;

        if ($type === '' || $name === '') {
            json_response(400, ['success' => false, 'error' => '请填写分类名称并选择类型']);
        }
        if (!in_array($type, ['expense', 'income'], true)) {
            json_response(400, ['success' => false, 'error' => '分类类型不正确']);
        }

        $iconType = null;
        $iconValue = null;
        $iconLibId = (int)($body['icon_library_id'] ?? 0);
        if ($iconLibId > 0) {
            $icon = IconLibrary::findByUser((int)$user['id'], $iconLibId);
            if ($icon) {
                $iconType = 'file';
                $iconValue = $icon['file_path'] ?? null;
            }
        }

        Category::create((int)$user['id'], $type, $name, $sortOrder, $iconType, $iconValue);
        json_response(200, ['success' => true]);
        break;
    }

    case 'categories/update': {
        $user = require_auth_user();
        $body = parse_json_body();
        $id = (int)($body['id'] ?? 0);
        $type = trim((string)($body['type'] ?? ''));
        $name = trim((string)($body['name'] ?? ''));
        $sortOrder = isset($body['sort_order']) ? (int)$body['sort_order'] : 0;

        if ($id <= 0 || $type === '' || $name === '') {
            json_response(400, ['success' => false, 'error' => '参数不完整']);
        }
        if (!in_array($type, ['expense', 'income'], true)) {
            json_response(400, ['success' => false, 'error' => '分类类型不正确']);
        }

        $current = Category::findByUser((int)$user['id'], $id);
        if (!$current) {
            json_response(404, ['success' => false, 'error' => '分类不存在']);
        }
        $iconType = $current['icon_type'] ?? null;
        $iconValue = $current['icon_value'] ?? null;
        $iconLibId = (int)($body['icon_library_id'] ?? 0);
        $iconClear = !empty($body['icon_clear']);
        if ($iconClear) {
            $iconType = null;
            $iconValue = null;
        } elseif ($iconLibId > 0) {
            $icon = IconLibrary::findByUser((int)$user['id'], $iconLibId);
            if ($icon) {
                $iconType = 'file';
                $iconValue = $icon['file_path'] ?? null;
            }
        }

        Category::update((int)$user['id'], $id, $name, $sortOrder, $iconType, $iconValue);
        json_response(200, ['success' => true]);
        break;
    }

    case 'categories/delete': {
        $user = require_auth_user();
        $body = parse_json_body();
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) {
            json_response(400, ['success' => false, 'error' => '参数不完整']);
        }
        $ok = Category::delete((int)$user['id'], $id);
        if (!$ok) {
            json_response(400, ['success' => false, 'error' => '该分类已有记账数据，无法删除']);
        }
        json_response(200, ['success' => true]);
        break;
    }

    case 'items/list': {
        $user = require_auth_user();
        $categoryId = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null;
        $items = Item::allByUser((int)$user['id'], $categoryId);

        // 预加载当前用户的分类，方便输出友好的所属分类名称
        $categories = Category::allByUser((int)$user['id']);
        $categoryMap = [];
        foreach ($categories as $c) {
            $id = (int)$c['id'];
            $label = '[' . ($c['type'] === 'income' ? '收入' : '支出') . '] ' . $c['name'];
            $categoryMap[$id] = [
                'name' => (string)$c['name'],
                'type' => (string)$c['type'],
                'label' => $label,
            ];
        }
        $result = [];
        foreach ($items as $i) {
            $cid = (int)$i['category_id'];
            $cat = $categoryMap[$cid] ?? null;
            $catName = $cat['name'] ?? null;
            $catType = $cat['type'] ?? null;
            $catLabel = $cat['label'] ?? null;
            $result[] = [
                'id' => (int)$i['id'],
                'category_id' => (int)$i['category_id'],
                'category_name' => $catName,
                'category_type' => $catType,
                'category_label' => $catLabel,
                'name' => $i['name'],
                'sort_order' => (int)$i['sort_order'],
                'icon_type' => $i['icon_type'],
                'icon_value' => $i['icon_value'],
                'icon_url' => $i['icon_type'] === 'file' ? build_file_url($i['icon_value']) : null,
            ];
        }
        json_response(200, ['success' => true, 'items' => $result]);
        break;
    }

    case 'items/create': {
        $user = require_auth_user();
        $body = parse_json_body();
        $categoryId = (int)($body['category_id'] ?? 0);
        $name = trim((string)($body['name'] ?? ''));
        $sortOrder = isset($body['sort_order']) ? (int)$body['sort_order'] : 0;

        if ($categoryId <= 0 || $name === '') {
            json_response(400, ['success' => false, 'error' => '请选择分类并填写项目名称']);
        }

        $iconType = null;
        $iconValue = null;
        $iconLibId = (int)($body['icon_library_id'] ?? 0);
        if ($iconLibId > 0) {
            $icon = IconLibrary::findByUser((int)$user['id'], $iconLibId);
            if ($icon) {
                $iconType = 'file';
                $iconValue = $icon['file_path'] ?? null;
            }
        }

        try {
            Item::create((int)$user['id'], $categoryId, $name, $sortOrder, $iconType, $iconValue);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'duplicate_item') {
                json_response(400, ['success' => false, 'error' => '该分类下已存在同名项目，请勿重复添加']);
            }
            json_response(500, ['success' => false, 'error' => '新增项目时发生错误']);
        }

        json_response(200, ['success' => true]);
        break;
    }

    case 'items/update': {
        $user = require_auth_user();
        $body = parse_json_body();
        $id = (int)($body['id'] ?? 0);
        $categoryId = (int)($body['category_id'] ?? 0);
        $name = trim((string)($body['name'] ?? ''));
        $sortOrder = isset($body['sort_order']) ? (int)$body['sort_order'] : 0;

        if ($id <= 0 || $categoryId <= 0 || $name === '') {
            json_response(400, ['success' => false, 'error' => '参数不完整']);
        }

        $current = Item::findByUser((int)$user['id'], $id);
        if (!$current) {
            json_response(404, ['success' => false, 'error' => '项目不存在']);
        }

        $iconType = $current['icon_type'] ?? null;
        $iconValue = $current['icon_value'] ?? null;
        $iconLibId = (int)($body['icon_library_id'] ?? 0);
        $iconClear = !empty($body['icon_clear']);
        if ($iconClear) {
            $iconType = null;
            $iconValue = null;
        } elseif ($iconLibId > 0) {
            $icon = IconLibrary::findByUser((int)$user['id'], $iconLibId);
            if ($icon) {
                $iconType = 'file';
                $iconValue = $icon['file_path'] ?? null;
            }
        }

        try {
            Item::update((int)$user['id'], $id, $categoryId, $name, $sortOrder, $iconType, $iconValue);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'duplicate_item') {
                json_response(400, ['success' => false, 'error' => '该分类下已存在同名项目，请更换一个名称']);
            }
            json_response(500, ['success' => false, 'error' => '更新项目时发生错误']);
        }

        json_response(200, ['success' => true]);
        break;
    }

    case 'items/delete': {
        $user = require_auth_user();
        $body = parse_json_body();
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) {
            json_response(400, ['success' => false, 'error' => '参数不完整']);
        }
        $ok = Item::delete((int)$user['id'], $id);
        if (!$ok) {
            json_response(400, ['success' => false, 'error' => '该项目已有记账数据，无法删除']);
        }
        json_response(200, ['success' => true]);
        break;
    }

    case 'icon-library/list': {
        $user = require_auth_user();
        $icons = IconLibrary::allByUser((int)$user['id']);
        $result = [];
        foreach ($icons as $icon) {
            $result[] = [
                'id' => (int)$icon['id'],
                'name' => $icon['name'],
                'file_path' => $icon['file_path'],
                'file_url' => build_file_url($icon['file_path']),
            ];
        }
        json_response(200, ['success' => true, 'icons' => $result]);
        break;
    }

    case 'icon-library/upload': {
        $user = require_auth_user();
        if (!isset($_FILES['file'])) {
            json_response(400, ['success' => false, 'error' => '缺少文件']);
        }

        $name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
        if ($name === '') {
            $name = '自定义图标';
        }

        $path = Upload::saveAttachment((int)$user['id'], $_FILES['file']);
        if (!$path) {
            json_response(400, ['success' => false, 'error' => '上传失败或文件不合法']);
        }

        $iconId = IconLibrary::create((int)$user['id'], $name, $path);
        $fileUrl = build_file_url($path);

        json_response(200, [
            'success' => true,
            'icon' => [
                'id' => $iconId,
                'name' => $name,
                'file_path' => $path,
                'file_url' => $fileUrl,
            ],
        ]);
        break;
    }

    case 'icon-library/update': {
        $user = require_auth_user();
        $body = parse_json_body();
        $id = (int)($body['id'] ?? 0);
        $name = isset($body['name']) ? trim((string)$body['name']) : '';
        if ($id <= 0) {
            json_response(400, ['success' => false, 'error' => '参数不完整']);
        }
        if ($name === '') {
            $name = '自定义图标';
        }
        $ok = IconLibrary::updateName((int)$user['id'], $id, $name);
        if (!$ok) {
            json_response(400, ['success' => false, 'error' => '更新失败，该图标可能不存在或不属于当前用户']);
        }
        json_response(200, ['success' => true]);
        break;
    }

    case 'icon-library/update-file': {
        $user = require_auth_user();
        if (!isset($_FILES['file'])) {
            json_response(400, ['success' => false, 'error' => '缺少文件']);
        }
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
        if ($id <= 0) {
            json_response(400, ['success' => false, 'error' => '参数不完整']);
        }

        $path = Upload::saveAttachment((int)$user['id'], $_FILES['file']);
        if (!$path) {
            json_response(400, ['success' => false, 'error' => '上传失败或文件不合法']);
        }

        $ok = IconLibrary::updateFile((int)$user['id'], $id, $path, $name !== '' ? $name : null);
        if (!$ok) {
            json_response(400, ['success' => false, 'error' => '更新失败，该图标可能不存在或不属于当前用户']);
        }

        $icon = IconLibrary::findByUser((int)$user['id'], $id);
        if (!$icon) {
            json_response(200, ['success' => true]);
        }

        json_response(200, [
            'success' => true,
            'icon' => [
                'id' => (int)$icon['id'],
                'name' => $icon['name'],
                'file_path' => $icon['file_path'],
                'file_url' => build_file_url($icon['file_path']),
            ],
        ]);
        break;
    }

    case 'icon-library/delete': {
        $user = require_auth_user();
        $body = parse_json_body();
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) {
            json_response(400, ['success' => false, 'error' => '参数不完整']);
        }
        $ok = IconLibrary::delete((int)$user['id'], $id);
        if (!$ok) {
            json_response(400, ['success' => false, 'error' => '删除失败，该图标可能不存在或不属于当前用户']);
        }
        json_response(200, ['success' => true]);
        break;
    }

    case 'qr-login/status': {
        // PC 端轮询：根据 token 查询状态（无需授权）
        $token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';
        if ($token === '') {
            json_response(400, ['success' => false, 'error' => '缺少 token']);
        }
        $row = LoginToken::findByToken($token);
        if (!$row) {
            json_response(404, ['success' => false, 'error' => '不存在或已过期']);
        }
        $status = (string)$row['status'];
        $userId = isset($row['user_id']) ? (int)$row['user_id'] : 0;
        $user = null;
        if ($status === 'confirmed' && $userId > 0) {
            $u = User::findById($userId);
            if ($u) {
                $user = [
                    'id' => (int)$u['id'],
                    'username' => $u['username'],
                    'nickname' => $u['nickname'],
                    'role' => $u['role'],
                ];
            }
        }
        json_response(200, ['success' => true, 'status' => $status, 'user' => $user]);
        break;
    }

    case 'qr-login/confirm': {
        // 小程序端确认：需要用户令牌
        $user = require_auth_user();
        $body = parse_json_body();
        $token = isset($body['token']) ? trim((string)$body['token']) : '';
        if ($token === '') {
            json_response(400, ['success' => false, 'error' => '缺少 token']);
        }
        $row = LoginToken::findByToken($token);
        if (!$row) {
            json_response(404, ['success' => false, 'error' => '二维码不存在或已过期']);
        }
        $expiresTs = strtotime((string)$row['expires_at']) ?: 0;
        if ($expiresTs > 0 && time() > $expiresTs) {
            LoginToken::expire($token);
            json_response(400, ['success' => false, 'error' => '二维码已过期，请刷新重试']);
        }
        $ok = LoginToken::confirm($token, (int)$user['id']);
        if (!$ok) {
            json_response(400, ['success' => false, 'error' => '确认失败，该二维码可能已被使用']);
        }
        json_response(200, ['success' => true]);
        break;
    }

    case 'budget/month': {
        $user = require_auth_user();
        if (!empty($_GET['ym']) && preg_match('/^(\d{4})-(\d{2})$/', (string)$_GET['ym'], $m)) {
            $year = (int)$m[1];
            $month = (int)$m[2];
        } else {
            $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
            $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
        }

        $prevYear = $year - 1;
        $prevMonth = $month;

        $budgets = Budget::listByUserMonth((int)$user['id'], $year, $month);
        $pdo = Database::getConnection();

        $prevBudgets = Budget::listByUserMonth((int)$user['id'], $prevYear, $prevMonth);
        $prevBudgetMap = [];
        $totalPrevBudgetExpense = 0.0;
        $totalPrevUsedExpense = 0.0;
        foreach ($prevBudgets as &$pb) {
            $sql = 'SELECT COALESCE(SUM(amount),0) AS used_amount FROM transactions WHERE user_id = :uid AND type = :type AND YEAR(trans_time) = :y AND MONTH(trans_time) = :m';
            $params = [
                ':uid' => (int)$user['id'],
                ':type' => $pb['type'],
                ':y' => $prevYear,
                ':m' => $prevMonth,
            ];
            if (!empty($pb['category_id'])) {
                $sql .= ' AND category_id = :cid';
                $params[':cid'] = $pb['category_id'];
            }
            if (!empty($pb['item_id'])) {
                $sql .= ' AND item_id = :iid';
                $params[':iid'] = $pb['item_id'];
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['used_amount' => 0];
            $pb['used_amount'] = (float)$row['used_amount'];
            $pb['remain_amount'] = (float)$pb['amount'] - $pb['used_amount'];

            if ($pb['type'] === 'expense') {
                $totalPrevBudgetExpense += (float)$pb['amount'];
                $totalPrevUsedExpense += (float)$pb['used_amount'];
            }

            $key = $pb['type'] . '|' . ((int)($pb['category_id'] ?? 0)) . '|' . ((int)($pb['item_id'] ?? 0));
            $prevBudgetMap[$key] = $pb;
        }
        unset($pb);

        $totalBudgetExpense = 0.0;
        $totalUsedExpense = 0.0;
        $resultBudgets = [];
        foreach ($budgets as &$b) {
            $sql = 'SELECT COALESCE(SUM(amount),0) AS used_amount FROM transactions WHERE user_id = :uid AND type = :type AND YEAR(trans_time) = :y AND MONTH(trans_time) = :m';
            $params = [
                ':uid' => (int)$user['id'],
                ':type' => $b['type'],
                ':y' => $year,
                ':m' => $month,
            ];
            if (!empty($b['category_id'])) {
                $sql .= ' AND category_id = :cid';
                $params[':cid'] = $b['category_id'];
            }
            if (!empty($b['item_id'])) {
                $sql .= ' AND item_id = :iid';
                $params[':iid'] = $b['item_id'];
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['used_amount' => 0];
            $b['used_amount'] = (float)$row['used_amount'];
            $b['remain_amount'] = (float)$b['amount'] - $b['used_amount'];

            $key = $b['type'] . '|' . ((int)($b['category_id'] ?? 0)) . '|' . ((int)($b['item_id'] ?? 0));
            if (isset($prevBudgetMap[$key])) {
                $b['prev_budget_amount'] = (float)$prevBudgetMap[$key]['amount'];
                $b['prev_used_amount'] = (float)$prevBudgetMap[$key]['used_amount'];
            } else {
                $b['prev_budget_amount'] = 0.0;
                $b['prev_used_amount'] = 0.0;
            }

            if ($b['type'] === 'expense') {
                $totalBudgetExpense += (float)$b['amount'];
                $totalUsedExpense += (float)$b['used_amount'];
            }

            $resultBudgets[] = [
                'id' => (int)$b['id'],
                'type' => $b['type'],
                'category_id' => $b['category_id'] !== null ? (int)$b['category_id'] : null,
                'category_name' => $b['category_name'] ?? null,
                'item_id' => $b['item_id'] !== null ? (int)$b['item_id'] : null,
                'item_name' => $b['item_name'] ?? null,
                'amount' => (float)$b['amount'],
                'used_amount' => (float)$b['used_amount'],
                'remain_amount' => (float)$b['remain_amount'],
                'prev_budget_amount' => (float)$b['prev_budget_amount'],
                'prev_used_amount' => (float)$b['prev_used_amount'],
            ];
        }
        unset($b);

        json_response(200, [
            'success' => true,
            'year' => $year,
            'month' => $month,
            'prevYear' => $prevYear,
            'prevMonth' => $prevMonth,
            'budgets' => $resultBudgets,
            'totalBudgetExpense' => $totalBudgetExpense,
            'totalUsedExpense' => $totalUsedExpense,
            'totalPrevBudgetExpense' => $totalPrevBudgetExpense,
            'totalPrevUsedExpense' => $totalPrevUsedExpense,
        ]);
        break;
    }

    case 'budget/upsert': {
        $user = require_auth_user();
        $body = parse_json_body();
        $year = (int)($body['year'] ?? date('Y'));
        $month = (int)($body['month'] ?? date('n'));
        $type = (string)($body['type'] ?? 'expense');
        $categoryId = isset($body['category_id']) && $body['category_id'] !== null ? (int)$body['category_id'] : null;
        $itemId = isset($body['item_id']) && $body['item_id'] !== null ? (int)$body['item_id'] : null;
        $amount = isset($body['amount']) ? (float)$body['amount'] : 0.0;

        if ($year <= 0 || $month <= 0 || $amount <= 0) {
            json_response(400, ['success' => false, 'error' => '参数不合法']);
        }

        Budget::upsert((int)$user['id'], $year, $month, $type, $categoryId, $itemId, $amount);
        json_response(200, ['success' => true]);
        break;
    }

    case 'budget/update-amount': {
        $user = require_auth_user();
        $body = parse_json_body();
        $id = (int)($body['id'] ?? 0);
        $amount = isset($body['amount']) ? (float)$body['amount'] : 0.0;
        if ($id <= 0 || $amount <= 0) {
            json_response(400, ['success' => false, 'error' => '参数不合法']);
        }
        Budget::updateAmount((int)$user['id'], $id, $amount);
        json_response(200, ['success' => true]);
        break;
    }

    case 'budget/delete': {
        $user = require_auth_user();
        $body = parse_json_body();
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) {
            json_response(400, ['success' => false, 'error' => '参数不合法']);
        }
        Budget::delete((int)$user['id'], $id);
        json_response(200, ['success' => true]);
        break;
    }

    case 'transactions/upload-attachment': {
        $user = require_auth_user();
        if (!isset($_FILES['file'])) {
            json_response(400, ['success' => false, 'error' => '缺少文件']);
        }

        $path = Upload::saveAttachment((int)$user['id'], $_FILES['file']);
        if (!$path) {
            json_response(400, ['success' => false, 'error' => '上传失败或文件不合法']);
        }

        json_response(200, [
            'success' => true,
            'path' => $path,
            'url' => build_file_url($path),
        ]);
        break;
    }

    case 'transactions/list': {
        $user = require_auth_user();
        $filters = [
            'type' => $_GET['type'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'item_id' => $_GET['item_id'] ?? '',
            'account_id' => $_GET['account_id'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'amount_min' => $_GET['amount_min'] ?? '',
            'amount_max' => $_GET['amount_max'] ?? '',
            'remark' => $_GET['remark'] ?? '',
        ];

        $all = Transaction::search((int)$user['id'], $filters);
        $summary = Transaction::summarize((int)$user['id'], $filters);

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $pageSize = isset($_GET['page_size']) ? (int)$_GET['page_size'] : 20;
        if ($pageSize <= 0) {
            $pageSize = 20;
        } elseif ($pageSize > 100) {
            $pageSize = 100;
        }
        $total = count($all);
        $offset = ($page - 1) * $pageSize;
        $slice = array_slice($all, $offset, $pageSize);

        $list = [];
        foreach ($slice as $r) {
            // 计算图标 URL（仅对文件型图标输出 URL，其它类型交由前端自行处理）
            $categoryIconUrl = null;
            if (!empty($r['category_icon_type']) && $r['category_icon_type'] === 'file' && !empty($r['category_icon_value'])) {
                $categoryIconUrl = build_file_url($r['category_icon_value']);
            }

            $itemIconUrl = null;
            if (!empty($r['item_icon_type']) && $r['item_icon_type'] === 'file' && !empty($r['item_icon_value'])) {
                $itemIconUrl = build_file_url($r['item_icon_value']);
            }

            $fromAccountIconUrl = null;
            if (!empty($r['from_account_icon_type']) && $r['from_account_icon_type'] === 'file' && !empty($r['from_account_icon_value'])) {
                $fromAccountIconUrl = build_file_url($r['from_account_icon_value']);
            }

            $toAccountIconUrl = null;
            if (!empty($r['to_account_icon_type']) && $r['to_account_icon_type'] === 'file' && !empty($r['to_account_icon_value'])) {
                $toAccountIconUrl = build_file_url($r['to_account_icon_value']);
            }

            $list[] = [
                'id' => (int)$r['id'],
                'type' => $r['type'],
                'category_id' => $r['category_id'] !== null ? (int)$r['category_id'] : null,
                'category_name' => $r['category_name'] ?? null,
                'category_icon_url' => $categoryIconUrl,
                'item_id' => $r['item_id'] !== null ? (int)$r['item_id'] : null,
                'item_name' => $r['item_name'] ?? null,
                'item_icon_url' => $itemIconUrl,
                'from_account_id' => $r['from_account_id'] !== null ? (int)$r['from_account_id'] : null,
                'from_account_name' => $r['from_account_name'] ?? null,
                'from_account_icon_url' => $fromAccountIconUrl,
                'to_account_id' => $r['to_account_id'] !== null ? (int)$r['to_account_id'] : null,
                'to_account_name' => $r['to_account_name'] ?? null,
                'to_account_icon_url' => $toAccountIconUrl,
                'amount' => (float)$r['amount'],
                'trans_time' => $r['trans_time'],
                'remark' => $r['remark'],
                'attachment_path' => $r['attachment_path'],
                'attachment_url' => $r['attachment_path'] ? build_file_url($r['attachment_path']) : null,
            ];
        }

        json_response(200, [
            'success' => true,
            'transactions' => $list,
            'summary' => [
                'income' => (float)$summary['income'],
                'expense' => (float)$summary['expense'],
            ],
            'pagination' => [
                'page' => $page,
                'page_size' => $pageSize,
                'total' => $total,
            ],
        ]);
        break;
    }

    case 'transactions/create': {
        $user = require_auth_user();
        $body = parse_json_body();
        $type = (string)($body['type'] ?? 'expense');
        if ($type !== 'expense' && $type !== 'income') {
            $type = 'expense';
        }
        $categoryId = (int)($body['category_id'] ?? 0);
        $itemId = isset($body['item_id']) ? (int)$body['item_id'] : 0;
        $fromAccountId = isset($body['from_account_id']) ? (int)$body['from_account_id'] : 0;
        $toAccountId = isset($body['to_account_id']) ? (int)$body['to_account_id'] : 0;
        $amount = isset($body['amount']) ? (float)$body['amount'] : 0.0;
        $remark = trim((string)($body['remark'] ?? ''));
        $transTime = normalize_trans_time($body['trans_time'] ?? '');
        $attachmentPath = isset($body['attachment_path']) ? trim((string)$body['attachment_path']) : '';

        if ($amount <= 0) {
            json_response(400, ['success' => false, 'error' => '金额必须大于0']);
        }
        if ($categoryId <= 0) {
            json_response(400, ['success' => false, 'error' => '请选择分类']);
        }
        if ($type === 'expense' && $fromAccountId <= 0) {
            json_response(400, ['success' => false, 'error' => '支出需要选择支出账户']);
        }
        if ($type === 'income' && $toAccountId <= 0) {
            json_response(400, ['success' => false, 'error' => '收入需要选择收入账户']);
        }

        $data = [
            'user_id' => (int)$user['id'],
            'type' => $type,
            'category_id' => $categoryId,
            'item_id' => $itemId ?: null,
            'from_account_id' => $fromAccountId ?: null,
            'to_account_id' => $toAccountId ?: null,
            'amount' => $amount,
            'trans_time' => $transTime,
            'remark' => $remark,
            'attachment_path' => $attachmentPath !== '' ? $attachmentPath : null,
        ];

        apply_balance_change($type, $fromAccountId, $toAccountId, $amount, 1);
        $id = Transaction::create($data);

        json_response(200, ['success' => true, 'id' => $id]);
        break;
    }

    case 'transactions/update': {
        $user = require_auth_user();
        $body = parse_json_body();
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) {
            json_response(400, ['success' => false, 'error' => '参数不完整']);
        }
        $tx = Transaction::findById($id, (int)$user['id']);
        if (!$tx) {
            json_response(404, ['success' => false, 'error' => '记录不存在']);
        }

        $type = isset($body['type']) ? (string)$body['type'] : $tx['type'];
        if ($type !== 'expense' && $type !== 'income') {
            $type = $tx['type'];
        }
        $categoryId = isset($body['category_id']) ? (int)$body['category_id'] : (int)$tx['category_id'];
        $itemId = isset($body['item_id']) ? (int)$body['item_id'] : (int)($tx['item_id'] ?? 0);
        $fromAccountId = isset($body['from_account_id']) ? (int)$body['from_account_id'] : (int)($tx['from_account_id'] ?? 0);
        $toAccountId = isset($body['to_account_id']) ? (int)$body['to_account_id'] : (int)($tx['to_account_id'] ?? 0);
        $amount = isset($body['amount']) ? (float)$body['amount'] : (float)$tx['amount'];
        $remark = isset($body['remark']) ? trim((string)$body['remark']) : (string)($tx['remark'] ?? '');
        $transTime = isset($body['trans_time']) ? normalize_trans_time($body['trans_time']) : $tx['trans_time'];
        $attachmentPath = array_key_exists('attachment_path', $body)
            ? (trim((string)$body['attachment_path']) ?: null)
            : $tx['attachment_path'];

        if ($amount <= 0) {
            json_response(400, ['success' => false, 'error' => '金额必须大于0']);
        }
        if ($categoryId <= 0) {
            json_response(400, ['success' => false, 'error' => '请选择分类']);
        }
        if ($type === 'expense' && $fromAccountId <= 0) {
            json_response(400, ['success' => false, 'error' => '支出需要选择支出账户']);
        }
        if ($type === 'income' && $toAccountId <= 0) {
            json_response(400, ['success' => false, 'error' => '收入需要选择收入账户']);
        }

        apply_balance_change($tx['type'], (int)($tx['from_account_id'] ?? 0), (int)($tx['to_account_id'] ?? 0), (float)$tx['amount'], -1);
        apply_balance_change($type, $fromAccountId, $toAccountId, $amount, 1);

        $data = [
            'type' => $type,
            'category_id' => $categoryId,
            'item_id' => $itemId ?: null,
            'from_account_id' => $fromAccountId ?: null,
            'to_account_id' => $toAccountId ?: null,
            'amount' => $amount,
            'trans_time' => $transTime,
            'remark' => $remark,
            'attachment_path' => $attachmentPath,
        ];
        Transaction::update($id, (int)$user['id'], $data);

        json_response(200, ['success' => true]);
        break;
    }

    case 'transactions/delete': {
        $user = require_auth_user();
        $body = parse_json_body();
        $ids = isset($body['ids']) && is_array($body['ids']) ? array_map('intval', $body['ids']) : [];
        if (empty($ids)) {
            json_response(400, ['success' => false, 'error' => '缺少要删除的ID']);
        }

        foreach ($ids as $id) {
            $tx = Transaction::findById($id, (int)$user['id']);
            if ($tx) {
                apply_balance_change($tx['type'], (int)($tx['from_account_id'] ?? 0), (int)($tx['to_account_id'] ?? 0), (float)$tx['amount'], -1);
            }
        }
        $deleted = Transaction::deleteMany((int)$user['id'], $ids);

        json_response(200, ['success' => true, 'deleted' => $deleted]);
        break;
    }

    case 'reports/summary': {
        $userId = require_auth_user()['id'];
        $mode = $_GET['mode'] ?? 'month';
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
        $quarter = isset($_GET['quarter']) ? (int)$_GET['quarter'] : (int)ceil($month / 3);

        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';

        $start = new \DateTime('today');
        $end = new \DateTime('today');

        switch ($mode) {
            case 'year':
                $start = new \DateTime($year . '-01-01');
                $end = new \DateTime($year . '-12-31');
                break;
            case 'quarter':
                $startMonth = ($quarter - 1) * 3 + 1;
                $start = new \DateTime(sprintf('%d-%02d-01', $year, $startMonth));
                $end = clone $start;
                $end->modify('+2 months')->modify('last day of this month');
                break;
            case 'day':
                $start = new \DateTime();
                $end = new \DateTime();
                break;
            case 'yesterday':
                $start = new \DateTime('yesterday');
                $end = new \DateTime('yesterday');
                break;
            case 'custom':
                if ($dateFrom && $dateTo) {
                    $start = new \DateTime($dateFrom);
                    $end = new \DateTime($dateTo);
                }
                break;
            case 'month':
            default:
                $start = new \DateTime(sprintf('%d-%02d-01', $year, $month));
                $end = clone $start;
                $end->modify('last day of this month');
                break;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT DATE(trans_time) AS d, type, COALESCE(SUM(amount),0) AS total
            FROM transactions
            WHERE user_id = :uid AND trans_time BETWEEN :from AND :to
            GROUP BY DATE(trans_time), type
            ORDER BY d');
        $stmt->execute([
            ':uid' => (int)$userId,
            ':from' => $start->format('Y-m-d 00:00:00'),
            ':to' => $end->format('Y-m-d 23:59:59'),
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $labels = [];
        $incomeData = [];
        $expenseData = [];
        $byDate = [];
        foreach ($rows as $r) {
            $d = $r['d'];
            if (!isset($byDate[$d])) {
                $byDate[$d] = ['income' => 0.0, 'expense' => 0.0];
            }
            if ($r['type'] === 'income') {
                $byDate[$d]['income'] += (float)$r['total'];
            } elseif ($r['type'] === 'expense') {
                $byDate[$d]['expense'] += (float)$r['total'];
            }
        }
        foreach ($byDate as $d => $v) {
            $labels[] = $d;
            $incomeData[] = $v['income'];
            $expenseData[] = $v['expense'];
        }

        $totalIncome = array_sum($incomeData);
        $totalExpense = array_sum($expenseData);

        $totalBudgetExpense = 0.0;
        $totalUsedExpense = 0.0;
        if (in_array($mode, ['year', 'quarter', 'month'], true)) {
            if ($mode === 'year') {
                $startMonth = 1;
                $endMonth = 12;
            } elseif ($mode === 'quarter') {
                $startMonth = ($quarter - 1) * 3 + 1;
                $endMonth = $startMonth + 2;
            } else {
                $startMonth = $month;
                $endMonth = $month;
            }

            for ($m = $startMonth; $m <= $endMonth; $m++) {
                [$bTotal, $uTotal] = summarize_budget_by_month((int)$userId, $year, $m);
                $totalBudgetExpense += $bTotal;
                $totalUsedExpense += $uTotal;
            }
        }

        json_response(200, [
            'success' => true,
            'mode' => $mode,
            'year' => $year,
            'month' => $month,
            'quarter' => $quarter,
            'dateFrom' => $start->format('Y-m-d'),
            'dateTo' => $end->format('Y-m-d'),
            'labels' => $labels,
            'incomeData' => $incomeData,
            'expenseData' => $expenseData,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'totalBudgetExpense' => $totalBudgetExpense,
            'totalUsedExpense' => $totalUsedExpense,
        ]);
        break;
    }

    default:
        json_response(404, ['success' => false, 'error' => '未知接口']);
}
