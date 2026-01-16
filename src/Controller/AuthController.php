<?php
namespace App\Controller;

use App\Model\User;
use App\Model\LoginToken;
use App\Model\EmailToken;
use App\Service\Mailer;
use App\Service\Config;
use App\Service\Seeder;

class AuthController
{
    private function render(string $view, array $params = []): void
    {
        extract($params);
        $appName = Config::get('app.name');
        include __DIR__ . '/../../templates/layout_auth.php';
    }

    public function login(): void
    {
        $now = time();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $account = trim($_POST['account'] ?? '');
            $password = $_POST['password'] ?? '';

            // 支持邮箱或用户名登录
            $user = null;
            if ($account !== '') {
                if (filter_var($account, FILTER_VALIDATE_EMAIL)) {
                    $user = User::findByEmail($account);
                } else {
                    $user = User::findByUsername($account);
                }
            }

            $userFailedCount = 0;
            $userLockUntilTs = 0;
            if ($user) {
                $userFailedCount = (int)($user['failed_login_count'] ?? 0);
                $lockUntilStr = $user['login_lock_until'] ?? null;
                if ($lockUntilStr) {
                    $userLockUntilTs = strtotime($lockUntilStr) ?: 0;
                }
            }

            // 若当前账号已被临时锁定，直接提示
            if ($user && $userLockUntilTs > $now) {
                $remain = max(0, $userLockUntilTs - $now);
                $minutes = (int)ceil($remain / 60);
                $error = '密码连续输错次数过多，账户已暂时锁定，请约 ' . $minutes . ' 分钟后重试，或点击“忘记密码”重置密码。';
                $showCaptcha = true;
                $this->render('auth/login', compact('error', 'showCaptcha'));
                return;
            }

            // 第二次及之后的尝试需要输入验证码（按账号的失败次数计算），或之前已在本会话中出错
            $needCaptcha = !empty($_SESSION['login_show_captcha']) || ($user && $userFailedCount >= 1);
            if ($needCaptcha) {
                $inputCaptcha = trim($_POST['captcha'] ?? '');
                $sessionCode = (string)($_SESSION['captcha_code_login'] ?? '');
                if ($inputCaptcha === '' || $sessionCode === '' || strcasecmp($inputCaptcha, $sessionCode) !== 0) {
                    $_SESSION['login_show_captcha'] = true;
                    $error = '图形验证码错误，请重新输入。';
                    $showCaptcha = true;
                    $this->render('auth/login', compact('error', 'showCaptcha'));
                    return;
                }
            }

            // 账号不存在或密码错误时，仅按密码错误计入账号级失败次数（验证码错误不计入）
            if (!$user || !password_verify($password, $user['password_hash'])) {
                $_SESSION['login_show_captcha'] = true; // 从下一次开始显示验证码

                if ($user) {
                    $userFailedCount++;
                    $lockUntilTs = null;
                    if ($userFailedCount >= 5) {
                        $lockUntilTs = $now + 180; // 3 分钟
                        $error = '邮箱或密码错误，且已连续输错 5 次，账户已暂时锁定 3 分钟。您可以稍后重试，或点击“忘记密码”重置密码。';
                    } else {
                        $error = '邮箱或密码错误';
                    }
                    User::updateLoginSecurity((int)$user['id'], $userFailedCount, $lockUntilTs);
                } else {
                    $error = '邮箱或密码错误';
                }
                $showCaptcha = true;
                $this->render('auth/login', compact('error', 'showCaptcha'));
                return;
            }
            if ((int)$user['status'] !== 1) {
                $error = '账号已被禁用';
                $this->render('auth/login', compact('error'));
                return;
            }
            if ((int)$user['email_verified'] !== 1) {
                $error = '邮箱尚未验证，请先完成邮箱验证';
                $showCaptcha = !empty($_SESSION['login_show_captcha']);
                $this->render('auth/login', compact('error', 'showCaptcha'));
                return;
            }

            // 登录成功，重置账号级失败计数与锁定状态
            User::updateLoginSecurity((int)$user['id'], 0, null);
            unset($_SESSION['login_show_captcha'], $_SESSION['captcha_code_login']);

            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_nickname'] = $user['nickname'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['theme_mode'] = $user['theme_mode'] ?? 'light';
            // 顶部栏显示头像
            if (!empty($user['avatar_path'])) {
                $_SESSION['user_avatar'] = '/uploads/' . ltrim((string)$user['avatar_path'], '/\\');
            } else {
                $_SESSION['user_avatar'] = null;
            }
			// 记录最近一次操作时间，用于 24 小时未操作自动退出
			$_SESSION['last_activity'] = time();

            // 为新账号注入一套初始化数据（若当前用户尚无数据）
            Seeder::seedIfEmpty((int)$user['id']);

            header('Location: /');
            exit;
        }

        $showCaptcha = !empty($_SESSION['login_show_captcha']);
        $this->render('auth/login', compact('showCaptcha'));
    }

    // 注册完成后的第二步：引导绑定小程序（独立页面，刷新不影响已注册的数据）
    public function registerBind(): void
    {
        $token = trim($_GET['token'] ?? '');
        $bindToken = null;
        $bindExpiresAt = null;
        $bindQrPayload = null;

        if ($token !== '') {
            $row = \App\Model\LoginToken::findByToken($token);
            if ($row && ($row['status'] ?? '') === 'pending') {
                $bindToken = $token;
                $bindExpiresAt = $row['expires_at'] ?? null;
                $bindQrPayload = json_encode(['type' => 'bind', 'token' => $bindToken], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        $this->render('auth/register_bind', compact('bindToken', 'bindExpiresAt', 'bindQrPayload'));
    }

    public function logout(): void
    {
        session_destroy();
		header('Location: /public/index.php?route=login');
        exit;
    }

    public function register(): void
    {
        $allowRegister = (bool)Config::get('app.allow_register', true);
        if (!$allowRegister) {
            http_response_code(403);
            echo '当前系统已关闭注册';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $nickname = trim($_POST['nickname'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';
            $captcha = trim($_POST['captcha'] ?? '');

            $errors = [];
            if ($username === '' || $nickname === '' || $email === '' || $password === '') {
                $errors[] = '请完整填写所有必填信息';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = '邮箱格式不正确';
            }
            if ($password !== $passwordConfirm) {
                $errors[] = '两次输入的密码不一致';
            }
            if (User::findByUsername($username)) {
                $errors[] = '用户名已存在';
            }
            if (User::findByEmail($email)) {
                $errors[] = '邮箱已被使用';
            }

            // 图形验证码校验
            $sessionCaptcha = (string)($_SESSION['captcha_code_register'] ?? '');
            if ($captcha === '' || $sessionCaptcha === '' || strcasecmp($captcha, $sessionCaptcha) !== 0) {
                $errors[] = '图形验证码错误，请重新输入';
            }

            if (empty($errors)) {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $userId = User::create($username, $nickname, $email, $passwordHash);

                // 直接标记邮箱已验证，不再发送验证邮件，注册后可立即登录
                User::markEmailVerified($userId);

                // 注册完成即为该用户注入默认数据，登录后可直接使用
                Seeder::seedIfEmpty($userId);
                $success = '注册成功，您现在可以使用该账号直接登录。';

                // 生成一次性绑定令牌（有效期 10 分钟），用于小程序扫码直接绑定
                $bindToken = bin2hex(random_bytes(16));
                $bindExpiresAt = date('Y-m-d H:i:s', time() + 600);
                \App\Model\LoginToken::createForBind($bindToken, $userId, $bindExpiresAt);
                $bindQrPayload = json_encode([
                    'type' => 'bind',
                    'token' => $bindToken,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                $this->render('auth/register', compact('success', 'bindToken', 'bindQrPayload', 'bindExpiresAt'));
                return;
            }

            $this->render('auth/register', ['errors' => $errors]);
            return;
        }

        // 默认展示传统表单注册；支持通过 ?mode=quick 切换到“扫码注册”
        $mode = isset($_GET['mode']) ? trim((string)$_GET['mode']) : '';
        if ($mode === 'quick') {
            $token = bin2hex(random_bytes(16));
            $expiresAt = date('Y-m-d H:i:s', time() + 120);
            LoginToken::create($token, $expiresAt);
            $qrPayload = json_encode([
                'type' => 'qr-login',
                'token' => $token,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $this->render('auth/register_quick', [
                'token' => $token,
                'qrPayload' => $qrPayload,
                'expiresAt' => $expiresAt,
            ]);
            return;
        }
        $this->render('auth/register');
    }

    public function qrLogin(): void
    {
        // 生成一次性 token，有效期 2 分钟
        $token = bin2hex(random_bytes(16));
        $expiresAt = date('Y-m-d H:i:s', time() + 120);
        LoginToken::create($token, $expiresAt);

        $qrPayload = json_encode([
            'type' => 'qr-login',
            'token' => $token,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->render('auth/qr_login', [
            'token' => $token,
            'qrPayload' => $qrPayload,
            'expiresAt' => $expiresAt,
        ]);
    }

    public function qrLoginComplete(): void
    {
        $token = isset($_POST['token']) ? trim((string)$_POST['token']) : '';
        if ($token === '') {
            $error = '缺少 token';
            $this->render('auth/qr_login', compact('error'));
            return;
        }
        $row = LoginToken::findByToken($token);
        if (!$row) {
            $error = '二维码不存在或已过期';
            $this->render('auth/qr_login', compact('error'));
            return;
        }
        if ((string)$row['status'] !== 'confirmed' || (int)$row['user_id'] <= 0) {
            $error = '二维码尚未确认';
            $this->render('auth/qr_login', compact('error'));
            return;
        }
        $user = User::findById((int)$row['user_id']);
        if (!$user) {
            $error = '用户不存在';
            $this->render('auth/qr_login', compact('error'));
            return;
        }
        if ((int)$user['status'] !== 1) {
            $error = '账号已被禁用';
            $this->render('auth/qr_login', compact('error'));
            return;
        }
        // 允许未验证邮箱的账号通过扫码建立会话，后续引导完善资料

        // 建立 PC 会话
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_nickname'] = $user['nickname'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['theme_mode'] = $user['theme_mode'] ?? 'light';
        // 顶部栏显示头像
        if (!empty($user['avatar_path'])) {
            $_SESSION['user_avatar'] = '/uploads/' . ltrim((string)$user['avatar_path'], '/\\');
        } else {
            $_SESSION['user_avatar'] = null;
        }
        $_SESSION['last_activity'] = time();

        // 二维码登录成功后也进行一次初始化检查
        Seeder::seedIfEmpty((int)$user['id']);

        $needOnboarding = empty($user['email']) || empty($user['password_hash']);
        if ($needOnboarding) {
            header('Location: /public/index.php?route=onboarding');
        } else {
            header('Location: /');
        }
        exit;
    }

    public function verifyEmail(): void
    {
        $token = $_GET['token'] ?? '';
        if ($token === '') {
            echo '无效的验证链接';
            return;
        }

        $record = EmailToken::findValid($token, 'register');
        if (!$record) {
            echo '链接已失效或已使用';
            return;
        }

        User::markEmailVerified((int)$record['user_id']);
        EmailToken::markUsed((int)$record['id']);

        echo '邮箱验证成功，请返回登录页面登录。';
    }

    public function forgotPassword(): void
    {
        $email = '';
        $message = '';
        $error = '';
        $emailProvider = '';
        $emailLoginUrl = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = '请输入有效的邮箱地址';
            } else {
                $user = User::findByEmail($email);

                if (!$user) {
                    // 邮箱不存在时直接提示
                    $error = '未找到该邮箱对应的用户';
                } else {
                    $token = bin2hex(random_bytes(32));
						$expiresAt = date('Y-m-d H:i:s', time() + 3600);
						EmailToken::create((int)$user['id'], $email, 'reset_password', $token, $expiresAt);

						$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
						$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
						$resetUrl = $scheme . '://' . $host . '/public/index.php?route=reset-password&token=' . urlencode($token);

						$subject = 'SanS三石记账系统 - 重置密码';
						$body = "您好，{$user['nickname']}：\n\n" .
							"请复制或点击以下链接重置密码：\n" .
							$resetUrl . "\n\n" .
							"如果不是您本人操作，请忽略本邮件。";

						$sent = Mailer::send($email, $user['nickname'], $subject, $body);

						if ($sent) {
							// 常见邮箱服务商登录页面
							$emailDomain = strtolower(substr(strrchr($email, '@'), 1));
							$emailProviders = [
								// 国内邮箱
								'qq.com' => ['name' => 'QQ邮箱', 'url' => 'https://mail.qq.com/'],
								'163.com' => ['name' => '网易163邮箱', 'url' => 'https://mail.163.com/'],
								'126.com' => ['name' => '网易126邮箱', 'url' => 'https://mail.126.com/'],
								'yeah.net' => ['name' => '网易yeah邮箱', 'url' => 'https://mail.yeah.net/'],
								'sina.com' => ['name' => '新浪邮箱', 'url' => 'https://mail.sina.com.cn/'],
								'sina.cn' => ['name' => '新浪邮箱', 'url' => 'https://mail.sina.com.cn/'],
								'sohu.com' => ['name' => '搜狐邮箱', 'url' => 'https://mail.sohu.com/'],
								'139.com' => ['name' => '移动139邮箱', 'url' => 'https://mail.10086.cn/'],
								'189.cn' => ['name' => '天翼邮箱', 'url' => 'https://mail.189.cn/'],
								'wo.com.cn' => ['name' => '沃邮箱', 'url' => 'https://mail.wo.com.cn/'],
								'foxmail.com' => ['name' => 'Foxmail', 'url' => 'https://mail.qq.com/'],
								// 国外邮箱
								'gmail.com' => ['name' => 'Gmail', 'url' => 'https://mail.google.com/'],
								'outlook.com' => ['name' => 'Outlook', 'url' => 'https://outlook.live.com/'],
								'hotmail.com' => ['name' => 'Hotmail', 'url' => 'https://outlook.live.com/'],
								'live.com' => ['name' => 'Live邮箱', 'url' => 'https://outlook.live.com/'],
								'yahoo.com' => ['name' => 'Yahoo邮箱', 'url' => 'https://mail.yahoo.com/'],
								'yahoo.cn' => ['name' => 'Yahoo中国', 'url' => 'https://mail.yahoo.cn/'],
								'icloud.com' => ['name' => 'iCloud邮箱', 'url' => 'https://www.icloud.com/mail/'],
								'me.com' => ['name' => 'iCloud邮箱', 'url' => 'https://www.icloud.com/mail/'],
								'mac.com' => ['name' => 'iCloud邮箱', 'url' => 'https://www.icloud.com/mail/'],
							];

							if (isset($emailProviders[$emailDomain])) {
								$emailProvider = $emailProviders[$emailDomain]['name'];
								$emailLoginUrl = $emailProviders[$emailDomain]['url'];
							}

							$message = '重置链接已发送到您的邮箱，有效期1小时，请及时查收并完成重置。';
						} else {
							$error = '邮件发送失败，请稍后重试';
						}
                }
            }

			$this->render('auth/forgot_password', [
				'email' => $email,
				'error' => $error,
				'message' => $message,
				'emailProvider' => $emailProvider,
				'emailLoginUrl' => $emailLoginUrl,
			]);
            return;
        }

		$this->render('auth/forgot_password');
    }

    // 扫码后第二步：完善资料（设置密码与邮箱，可跳过）
    public function onboarding(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: /public/index.php?route=login');
            exit;
        }
        $user = User::findById((int)$_SESSION['user_id']);
        if (!$user) {
            header('Location: /public/index.php?route=logout');
            exit;
        }

        $success = null;
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
            $password = (string)($_POST['password'] ?? '');
            $confirm = (string)($_POST['password_confirm'] ?? '');

            if ($email !== '') {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = '邮箱格式不正确';
                } else {
                    $exists = User::findByEmail($email);
                    if ($exists && (int)$exists['id'] !== (int)$user['id']) {
                        $errors[] = '该邮箱已被使用';
                    }
                }
            }
            if ($password !== '' || $confirm !== '') {
                if ($password !== $confirm) {
                    $errors[] = '两次输入的密码不一致';
                } elseif (strlen($password) < 6) {
                    $errors[] = '密码至少 6 位';
                }
            }

            if (empty($errors)) {
                if ($email !== '' && $email !== (string)$user['email']) {
                    User::updateEmail((int)$user['id'], $email, true);
                }
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    User::updatePassword((int)$user['id'], $hash);
                }
                $success = '已保存，您可以开始使用啦。';
                $user = User::findById((int)$user['id']);
            }
        }

        $this->render('auth/onboarding', compact('user', 'success', 'errors'));
    }

    public function resetPassword(): void
    {
        $token = $_GET['token'] ?? ($_POST['token'] ?? '');
        if ($token === '') {
            echo '无效的重置链接';
            return;
        }

        $record = EmailToken::findValid($token, 'reset_password');
        if (!$record) {
            echo '链接已失效或已使用';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';
            $errors = [];
            if ($password === '' || $passwordConfirm === '') {
                $errors[] = '请填写新密码和确认密码';
            }
            if ($password !== $passwordConfirm) {
                $errors[] = '两次输入的密码不一致';
            }
            if (empty($errors)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                User::updatePassword((int)$record['user_id'], $hash);
                EmailToken::markUsed((int)$record['id']);
                echo '密码重置成功，请返回登录页面登录。';
                return;
            }

            $this->render('auth/reset_password', ['token' => $token, 'errors' => $errors]);
            return;
        }

        $this->render('auth/reset_password', ['token' => $token]);
    }
}
