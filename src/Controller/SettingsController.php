<?php
namespace App\Controller;

use App\Service\Config;
use App\Model\User;
use App\Model\SystemSetting;
use App\Model\Announcement;
use App\Model\EmailPush;
use App\Service\Mailer;
use App\Service\Seeder;
use App\Model\LoginToken;
use App\Model\UserWechatBinding;

class SettingsController
{
    private function requireLogin(): int
    {
        $uid = (int)($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) {
            header('Location: /public/index.php?route=login');
            exit;
        }
        return $uid;
    }

    private function render(string $view, array $params = []): void
    {
        extract($params);
        $appName = Config::get('app.name');
        include __DIR__ . '/../../templates/layout_main.php';
    }

    public function index(): void
    {
        $userId = $this->requireLogin();
        $currentUser = User::findById($userId);
        $isAdmin = ($currentUser['role'] ?? 'user') === 'admin';
        $registerSource = $currentUser['register_source'] ?? null;
        // 兼容旧库：无 register_source 时尝试根据绑定时间推断；若也无法推断，则按 PC 注册处理
        $currentBinding = UserWechatBinding::findByUserId($userId);
        $hasWechatBinding = $currentBinding !== null;
        if ($registerSource === null) {
            $isMiniappUser = false;
            if ($currentBinding && !empty($currentUser['created_at']) && !empty($currentBinding['created_at'])) {
                $uCreated = strtotime($currentUser['created_at']);
                $bCreated = strtotime($currentBinding['created_at']);
                if ($uCreated && $bCreated) {
                    $diffMin = abs(($bCreated - $uCreated) / 60);
                    $isMiniappUser = ($diffMin <= 5);
                }
            }
        } else {
            $isMiniappUser = ($registerSource === 'miniapp');
        }

        $tab = $_GET['tab'] ?? 'profile';
        $error = '';
        $success = '';
        $usernameModalError = '';
        $usernameModalSuccess = '';
        $pendingUsername = '';
        $emailModalError = '';
        $emailModalSuccess = '';
        $pendingEmail = '';
        $openModal = '';
        // 个人绑定二维码（当前用户自己生成并查看）
        $selfBindQrToken = null;
        $selfBindQrPayload = null;
        $selfBindQrExpiresAt = null;
        // 管理端生成的绑定二维码信息（仅本次请求展示）
        $bindQrUserId = null;
        $bindQrToken = null;
        $bindQrPayload = null;
        $bindQrExpiresAt = null;

        $licenseFixedCode = (string)Config::get('license.fixed_code', '');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'update_profile') {
                // 仅允许在此处修改昵称，用户名通过单独弹窗处理
                $nickname = trim($_POST['nickname'] ?? '');
                if ($nickname === '') {
                    $error = '昵称不能为空';
                } else {
                    User::updateProfile($userId, $currentUser['username'] ?? '', $nickname);
                    $_SESSION['user_nickname'] = $nickname;
                    $success = '昵称已更新';
                    $currentUser = User::findById($userId);
                }

            } elseif ($action === 'update_avatar') {
                // PC 端手动上传头像
                if (empty($_FILES['avatar']) || !is_array($_FILES['avatar'])) {
                    $error = '请选择要上传的头像文件';
                } else {
                    $oldAvatar = $currentUser['avatar_path'] ?? null;
                    $newPath = \App\Service\Upload::saveAvatar($userId, $_FILES['avatar']);
                    if ($newPath === null) {
                        $error = '头像上传失败，请确认文件大小不超过 5MB 且为常见图片格式';
                    } else {
                        User::updateAvatarPath($userId, $newPath);
                        if ($oldAvatar && $oldAvatar !== $newPath) {
                            \App\Service\Upload::deleteByRelativePath($oldAvatar);
                        }
                        $currentUser = User::findById($userId);
                        $_SESSION['user_avatar'] = '/uploads/' . ltrim((string)$currentUser['avatar_path'], '/\\');
                        $success = '头像已更新';
                    }
                }

            } elseif ($action === 'update_budget_reminder') {
                // 更新用户级预算提醒开关（接近上限 / 超支高亮与文案）
                $enabled = !empty($_POST['budget_reminder_enabled']);
                User::updateBudgetReminder($userId, $enabled);
                $success = '预算提醒设置已更新';
                $currentUser = User::findById($userId);

            } elseif ($action === 'change_username') {
                $tab = 'profile';
                $openModal = 'username';
                $newUsername = trim($_POST['new_username'] ?? '');
                $pendingUsername = $newUsername;
                $submitType = $_POST['submit_type'] ?? 'save';

                if ($newUsername === '') {
                    $usernameModalError = '新用户名不能为空';
                } else {
                    $uByName = User::findByUsername($newUsername);
                    if ($uByName && (int)$uByName['id'] !== $userId) {
                        $usernameModalError = '该用户名已被占用，请尝试其他名称或使用推荐';
                    } else {
                        if ($submitType === 'check') {
                            $usernameModalSuccess = '该用户名可以使用';
                        } else {
                            User::updateUsername($userId, $newUsername);
                            $success = '用户名已修改，下次登录请使用新用户名';
                            $currentUser = User::findById($userId);
                            $openModal = '';
                            $pendingUsername = '';
                        }
                    }
                }

            } elseif ($action === 'change_email') {
                $tab = 'profile';
                $newEmail = trim($_POST['new_email'] ?? '');
                $pendingEmail = $newEmail;
                if ($newEmail === '') {
                    $emailModalError = '新邮箱不能为空';
                    $openModal = 'email';
                } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                    $emailModalError = '请输入有效的新邮箱地址';
                    $openModal = 'email';
                } elseif (strcasecmp((string)($currentUser['email'] ?? ''), $newEmail) === 0) {
                    $emailModalError = '新邮箱不能与当前邮箱相同';
                    $openModal = 'email';
                } else {
                    $exist = User::findByEmail($newEmail);
                    if ($exist && (int)$exist['id'] !== $userId) {
                        $emailModalError = '该邮箱已被其他账号使用，请更换一个邮箱。';
                        $openModal = 'email';
                    } else {
                        // 直接更新邮箱并视为已验证
                        User::updateEmail($userId, $newEmail, true);
                        $success = '邮箱已更新，下次登录可使用新邮箱。';
                        $currentUser = User::findById($userId);
                        $pendingEmail = '';
                        $openModal = '';
                        // 清理旧的验证码会话数据（如存在）
                        unset($_SESSION['email_change']);
                    }
                }

            } elseif ($action === 'change_password') {
                $old = $_POST['old_password'] ?? '';
                $new = $_POST['new_password'] ?? '';
                $confirm = $_POST['confirm_password'] ?? '';
                if (!password_verify($old, $currentUser['password_hash'] ?? '')) {
                    $error = '旧密码不正确，如忘记可使用“忘记密码”功能';
                } elseif ($new === '' || $new !== $confirm) {
                    $error = '新密码不能为空且两次输入需一致';
                } else {
                    $hash = password_hash($new, PASSWORD_DEFAULT);
                    User::updatePassword($userId, $hash);
                    $success = '密码已更新';
                }
                $tab = 'security';

            } elseif ($isAdmin && $action === 'update_system') {
                $siteName = trim($_POST['site_name'] ?? '');
                $siteUrl = trim($_POST['site_url'] ?? '') ?: null;
                $allowRegister = isset($_POST['allow_register']);
                $siteIconSvg = trim($_POST['site_icon_svg'] ?? '');
                if ($siteIconSvg === '') {
                    $siteIconSvg = null;
                }
                // 会话超时时间（小时），允许管理员在 1~168 小时之间自定义，默认 24 小时
                $timeoutRaw = trim($_POST['session_timeout_hours'] ?? '');
                $sessionTimeoutHours = $timeoutRaw === '' ? 24 : (int)$timeoutRaw;
                if ($sessionTimeoutHours < 1 || $sessionTimeoutHours > 168) {
                    $error = '自动退出时间需在 1~168 小时之间，请重新填写。';
                } else {
                    SystemSetting::update($siteName, $siteUrl, $allowRegister, $siteIconSvg, $sessionTimeoutHours);
                    $success = '系统参数已保存';
                }
                $tab = 'system';
            } elseif ($isAdmin && $action === 'update_license') {
                $tab = 'system';

                // 当配置中写死了授权码时，不允许通过后台修改
                if ($licenseFixedCode !== '') {
                    $error = '当前系统授权码由配置文件固定管理，如需更换，请管理员直接修改 config.php 中的 license.fixed_code。';
                } else {
                    $code = trim($_POST['license_code'] ?? '');

                    if ($code === '') {
                        $error = '授权码不能为空';
                    } else {
                        // 读取当前已保存的授权邮箱（如有），仅用于兼容旧版本记录
                        $currentSettings = SystemSetting::get();
                        $email = trim((string)($currentSettings['license_email'] ?? '')) ?: null;

                        // 保存授权码，状态与时间交由联机校验更新
                        SystemSetting::updateLicense($email, $code, null, null);

                        // 触发一次强制联机校验
                        $result = \App\Service\LicenseClient::checkNow(true);
                        if ($result['ok']) {
                            $success = '授权信息已保存：' . ($result['message'] ?? '');
                        } else {
                            $error = '授权校验失败：' . ($result['message'] ?? '');
                        }

                        // 更新内存中的系统设置，用于页面展示最新状态
                        $system = SystemSetting::get();
                    }
                }
            } elseif ($isAdmin && $action === 'announcement_create') {
                $tab = 'system';
                $title = trim($_POST['announcement_title'] ?? '');
                $content = trim($_POST['announcement_content'] ?? '');
                $sendType = $_POST['announcement_send_type'] ?? 'now';
                $scheduledRaw = trim($_POST['announcement_scheduled_at'] ?? '');
                if ($title === '' || $content === '') {
                    $error = '公告标题和内容不能为空';
                } else {
                    $scheduledAt = date('Y-m-d H:i:s');
                    if ($sendType === 'schedule' && $scheduledRaw !== '') {
                        $ts = strtotime($scheduledRaw);
                        if ($ts !== false) {
                            $scheduledAt = date('Y-m-d H:i:s', $ts);
                        }
                    }
                    Announcement::create($title, $content, $scheduledAt);
                    $success = '公告已创建';
                }
            } elseif ($isAdmin && $action === 'announcement_update') {
                $tab = 'system';
                $id = (int)($_POST['id'] ?? 0);
                $title = trim($_POST['announcement_title'] ?? '');
                $content = trim($_POST['announcement_content'] ?? '');
                $scheduledRaw = trim($_POST['announcement_scheduled_at'] ?? '');
                if ($id <= 0) {
                    $error = '公告不存在';
                } elseif ($title === '' || $content === '') {
                    $error = '公告标题和内容不能为空';
                } else {
                    $scheduledAt = date('Y-m-d H:i:s');
                    if ($scheduledRaw !== '') {
                        $ts = strtotime($scheduledRaw);
                        if ($ts !== false) {
                            $scheduledAt = date('Y-m-d H:i:s', $ts);
                        }
                    }
                    Announcement::update($id, $title, $content, $scheduledAt);
                    $success = '公告已更新';
                }
            } elseif ($isAdmin && $action === 'announcement_delete') {
                $tab = 'system';
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    Announcement::delete($id);
                    $success = '公告已删除';
                } else {
                    $error = '公告不存在';
                }
            } elseif ($isAdmin && $action === 'announcement_repush') {
                $tab = 'system';
                $id = (int)($_POST['id'] ?? 0);
                $row = $id > 0 ? Announcement::findById($id) : null;
                if (!$row) {
                    $error = '公告不存在';
                } else {
                    $scheduledAt = date('Y-m-d H:i:s');
                    Announcement::create((string)$row['title'], (string)$row['content'], $scheduledAt);
                    $success = '公告已重新推送（生成一条新的公告记录）';
                }
            } elseif ($isAdmin && $action === 'email_push_create') {
                $tab = 'system';
                $title = trim($_POST['email_title'] ?? '');
                $content = trim($_POST['email_content'] ?? '');
                $scope = $_POST['email_scope'] ?? 'all';
                $sendType = $_POST['email_send_type'] ?? 'now';
                $scheduledRaw = trim($_POST['email_scheduled_at'] ?? '');
                $selectedIds = isset($_POST['email_selected_users']) && is_array($_POST['email_selected_users']) ? $_POST['email_selected_users'] : [];

                if ($title === '' || $content === '') {
                    $error = '邮件标题和内容不能为空';
                } elseif ($scope === 'selected' && empty($selectedIds)) {
                    $error = '请选择需要推送的用户';
                } else {
                    $scope = $scope === 'selected' ? 'selected' : 'all';
                    $scheduledAt = date('Y-m-d H:i:s');
                    if ($sendType === 'schedule' && $scheduledRaw !== '') {
                        $ts = strtotime($scheduledRaw);
                        if ($ts !== false) {
                            $scheduledAt = date('Y-m-d H:i:s', $ts);
                        }
                    }
                    $pushId = EmailPush::create($title, $content, $scope, $scheduledAt);
                    if ($scope === 'selected') {
                        EmailPush::seedRecipients($pushId, $selectedIds);
                    }
                    if ($sendType === 'now') {
                        $result = EmailPush::sendNow($pushId);
                        $success = '邮件已发送：成功 ' . (int)$result['sent'] . ' 封，失败 ' . (int)$result['failed'] . ' 封。';
                    } else {
                        $success = '邮件推送任务已创建，将在计划时间后自动发送（需有访问触发或定时任务调用）。';
                    }
                }
            } elseif ($isAdmin && $action === 'email_push_delete') {
                $tab = 'system';
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    EmailPush::delete($id);
                    $success = '邮件推送记录已删除';
                } else {
                    $error = '邮件推送记录不存在';
                }
            } elseif ($isAdmin && $action === 'email_push_resend') {
                $tab = 'system';
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    $result = EmailPush::sendNow($id);
                    $success = '邮件已重新发送：成功 ' . (int)$result['sent'] . ' 封，失败 ' . (int)$result['failed'] . ' 封。';
                } else {
                    $error = '邮件推送记录不存在';
                }
            } elseif ($isAdmin && $action === 'user_status') {
                $uid = (int)($_POST['id'] ?? 0);
                $status = (int)($_POST['status'] ?? 1);
                if ($uid !== $userId) {
                    User::updateStatus($uid, $status);
                    $success = '用户状态已更新';
                } else {
                    $error = '不能禁用当前登录账号';
                }
                $tab = 'users';
            } elseif ($isAdmin && $action === 'user_role') {
                $uid = (int)($_POST['id'] ?? 0);
                $role = $_POST['role'] ?? 'user';
                User::updateRole($uid, $role);
                $success = '用户角色已更新';
                $tab = 'users';
            } elseif ($isAdmin && $action === 'user_reset_password') {
                $uid = (int)($_POST['id'] ?? 0);
                $user = User::findById($uid);
                if ($user) {
                    $newPass = substr(bin2hex(random_bytes(4)), 0, 8);
                    $hash = password_hash($newPass, PASSWORD_DEFAULT);
                    User::updatePassword($uid, $hash);
                    // 发送邮件通知
                    $subject = 'SanS三石记账系统 - 密码已重置';
                    $html = '<p>您好，' . htmlspecialchars($user['nickname']) . '：</p>' .
                        '<p>管理员已为您重置登录密码，新密码为：<b>' . htmlspecialchars($newPass) . '</b></p>' .
                        '<p>请尽快登录系统并在“安全设置”中修改为您自己的密码。</p>';
                    Mailer::send($user['email'], $user['nickname'], $subject, $html);
                    $success = '已为该用户重置密码并发送邮件通知';
                }
                $tab = 'users';
            } elseif ($isAdmin && $action === 'user_delete') {
                $uid = (int)($_POST['id'] ?? 0);
                if ($uid === $userId) {
                    $error = '不能删除当前登录账号';
                } else {
                    User::deleteForce($uid);
                    $success = '已强制删除该用户及其所有数据';
                }
                $tab = 'users';
            } elseif ($isAdmin && $action === 'user_generate_bind_qr') {
                $tab = 'users';
                $uid = (int)($_POST['id'] ?? 0);
                $target = User::findById($uid);
                if (!$target) {
                    $error = '用户不存在';
                } else {
                    $systemTmp = SystemSetting::get();
                    $minutes = (int)($systemTmp['bind_qr_expires_minutes'] ?? 10);
                    if ($minutes <= 0) { $minutes = 10; }
                    $bindQrToken = bin2hex(random_bytes(16));
                    $bindQrExpiresAt = date('Y-m-d H:i:s', time() + $minutes * 60);
                    LoginToken::createForBind($bindQrToken, $uid, $bindQrExpiresAt);
                    $bindQrPayload = json_encode([
                        'type' => 'bind',
                        'token' => $bindQrToken,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $bindQrUserId = $uid;
                    $success = '已为该用户生成绑定二维码，请在有效期内使用微信小程序扫码绑定。';
                }
            } elseif ($isAdmin && $action === 'user_seed_defaults') {
                $tab = 'users';
                $uid = (int)($_POST['id'] ?? 0);
                $target = User::findById($uid);
                if (!$target) {
                    $error = '用户不存在';
                } else {
                    // 仅在该用户分类/项目/账户均为空时注入默认数据，具体判断由 Seeder 内部完成
                    Seeder::seedIfEmpty($uid);
                    $success = '已尝试为该用户注入默认数据：如其分类/项目/账户均为空，则已创建一套初始化数据；如已有数据则不会做任何更改。';
                }
            } elseif ($isAdmin && $action === 'user_unbind_wechat') {
                // 管理员为指定用户解除微信绑定，便于用户更换微信后重新绑定
                $tab = 'users';
                $uid = (int)($_POST['id'] ?? 0);
                if ($uid <= 0) {
                    $error = '用户不存在';
                } elseif ($uid === $userId) {
                    // 防止管理员通过用户管理误解绑自己，引导去个人信息页操作
                    $error = '不能在用户管理列表中为当前登录账号解绑微信，如需解绑请在“个人信息”页操作。';
                } else {
                    UserWechatBinding::deleteByUserId($uid);
                    // 简单记录一下管理员操作日志
                    try {
                        $msg = sprintf('[admin:%d] unbind wechat for user:%d at %s', $userId, $uid, date('Y-m-d H:i:s'));
                        error_log($msg);
                    } catch (\Throwable $e) {
                        // 忽略日志异常
                    }
                    $success = '已为该用户解除微信绑定，如需继续在小程序使用，请提醒其重新登录或扫码绑定。';
                }
            } elseif ($action === 'self_generate_bind_qr') {
                // 普通用户在个人信息页生成自己的绑定二维码
                $tab = 'profile';
                // 若用户已通过小程序注册或已经有绑定记录，则不重复生成，提示并提供解绑入口
                $currentBinding = UserWechatBinding::findByUserId($userId);
                $hasWechatBinding = $currentBinding !== null;
                $registerSource = $currentUser['register_source'] ?? null;
                $isMiniappUser = $registerSource === 'miniapp';
                if ($registerSource === null && $currentBinding && !empty($currentUser['created_at']) && !empty($currentBinding['created_at'])) {
                    $uCreated = strtotime($currentUser['created_at']);
                    $bCreated = strtotime($currentBinding['created_at']);
                    if ($uCreated && $bCreated && abs(($bCreated - $uCreated) / 60) <= 5) {
                        $isMiniappUser = true;
                    }
                }

                if ($isMiniappUser || $hasWechatBinding) {
                    $success = $isMiniappUser
                        ? '您是通过小程序注册的账号，默认已绑定，无需重复绑定。如需更换微信，可先解绑后再在小程序中重新绑定。'
                        : '当前账号已绑定微信，无需重复绑定。如需更换微信，可先解绑后再在小程序中重新绑定。';
                    $openModal = '';
                } else {
                    $systemTmp = SystemSetting::get();
                    $minutes = (int)($systemTmp['bind_qr_expires_minutes'] ?? 10);
                    if ($minutes <= 0) { $minutes = 10; }
                    $token = bin2hex(random_bytes(16));
                    $expiresAt = date('Y-m-d H:i:s', time() + $minutes * 60);
                    LoginToken::createForBind($token, $userId, $expiresAt);
                    $selfBindQrToken = $token;
                    $selfBindQrExpiresAt = $expiresAt;
                    $selfBindQrPayload = json_encode([
                        'type' => 'bind',
                        'token' => $token,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $openModal = 'bindqr';
                    $success = '已生成绑定二维码，请在有效期内使用微信小程序扫码完成绑定。';
                }
            } elseif ($action === 'unbind_wechat') {
                // 解绑当前账号的微信绑定，便于更换微信账号后重新绑定
                $tab = 'profile';
                UserWechatBinding::deleteByUserId($userId);
                $success = '已解绑微信。如需继续在小程序使用，请在小程序中登录账号或在本页生成绑定二维码后重新扫码绑定。';
            } elseif ($action === 'update_theme') {
                $tab = 'profile';
                $mode = $_POST['theme_mode'] ?? 'light';
                if (!in_array($mode, ['light', 'dark'], true)) {
                    $mode = 'light';
                }
                User::updateThemeMode($userId, $mode);
                $_SESSION['theme_mode'] = $mode;
                $success = '主题模式已更新';
            }
        }

        // 重新获取绑定状态以反映本次变更
        $currentBinding = UserWechatBinding::findByUserId($userId);
        $hasWechatBinding = $currentBinding !== null;
        $system = SystemSetting::get();
        $users = $isAdmin ? User::listAll() : [];

        // 管理端：处理到期但未发送的邮件推送任务，并准备公告/邮件推送列表
        $announcements = [];
        $emailPushes = [];
        if ($isAdmin) {
            // 轻量处理：每次进入设置页最多处理少量待发送任务
            try {
                EmailPush::processPending(3);
            } catch (\Throwable $e) {
                // 忽略后台定时任务错误，避免影响设置页打开
            }
            $announcements = Announcement::listAllWithViewCount();
            $emailPushes = EmailPush::listAll();
        }

        $this->render('settings/index', [
            'tab' => $tab,
            'currentUser' => $currentUser,
            'isAdmin' => $isAdmin,
            'isMiniappUser' => $isMiniappUser,
            'hasWechatBinding' => $hasWechatBinding,
            'wechatBinding' => $currentBinding,
            'system' => $system,
            'users' => $users,
            'announcements' => $announcements,
            'emailPushes' => $emailPushes,
            'bindQrUserId' => $bindQrUserId,
            'bindQrToken' => $bindQrToken,
            'bindQrPayload' => $bindQrPayload,
            'bindQrExpiresAt' => $bindQrExpiresAt,
            'selfBindQrToken' => $selfBindQrToken,
            'selfBindQrPayload' => $selfBindQrPayload,
            'selfBindQrExpiresAt' => $selfBindQrExpiresAt,
            'error' => $error,
            'success' => $success,
            'usernameModalError' => $usernameModalError,
            'usernameModalSuccess' => $usernameModalSuccess,
            'pendingUsername' => $pendingUsername,
            'emailModalError' => $emailModalError,
            'emailModalSuccess' => $emailModalSuccess,
            'pendingEmail' => $pendingEmail,
            'openModal' => $openModal,
        ]);
    }
}
