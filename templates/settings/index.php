<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">系统设置</h2>
    <div class="small text-muted">管理个人资料、安全设置以及系统参数和用户账号。</div>
</div>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
		<a class="nav-link <?= $tab === 'profile' ? 'active' : '' ?>" href="/public/index.php?route=settings&tab=profile">个人信息</a>
    </li>
    <li class="nav-item">
		<a class="nav-link <?= $tab === 'security' ? 'active' : '' ?>" href="/public/index.php?route=settings&tab=security">安全设置</a>
    </li>
    <?php if ($isAdmin): ?>
        <li class="nav-item">
			<a class="nav-link <?= $tab === 'system' ? 'active' : '' ?>" href="/public/index.php?route=settings&tab=system">系统参数</a>
        </li>
        <li class="nav-item">
			<a class="nav-link <?= $tab === 'users' ? 'active' : '' ?>" href="/public/index.php?route=settings&tab=users">用户管理</a>
        </li>
    <?php endif; ?>
</ul>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success py-2 small"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php
$miniappEnabled = \App\Service\Config::get('wechat.enable_miniapp', true);
?>

<?php if ($tab === 'profile'): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="h6 mb-0">个人信息</h3>
                <div class="small text-muted">用户ID：<?= isset($currentUser['id']) ? (int)$currentUser['id'] : '-' ?></div>
            </div>
            <form method="post" enctype="multipart/form-data" class="row g-3 mb-3 align-items-center">
                <input type="hidden" name="action" value="update_avatar">
                <div class="col-12 col-md-6 d-flex align-items-center gap-3">
                    <div>
                        <?php if (!empty($currentUser['avatar_path'])): ?>
                            <img src="/uploads/<?= htmlspecialchars($currentUser['avatar_path']) ?>" alt="头像" class="rounded-circle" style="width:64px;height:64px;object-fit:cover;">
                        <?php else: ?>
                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width:64px;height:64px;font-size:1.25rem;">👤</div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1">
                        <label class="form-label small mb-1">头像</label>
                        <input type="file" name="avatar" accept="image/*" class="form-control form-control-sm">
                        <div class="form-text small">支持常见图片格式，文件大小不超过 5MB。更换头像后将自动删除旧头像文件。</div>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-outline-primary">上传头像</button>
                    </div>
                </div>
            </form>

            <form method="post" class="row g-3">
                <input type="hidden" name="action" value="update_profile">
                <div class="col-12 col-md-6">
                    <label class="form-label small d-flex justify-content-between align-items-center">
                        <span>用户名（登录账号）</span>
                        <button type="button" class="btn btn-link btn-sm p-0" data-bs-toggle="modal" data-bs-target="#modalUsernameChange">修改用户名</button>
                    </label>
                    <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($currentUser['username'] ?? '') ?>" disabled>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label small">昵称（展示用）</label>
                    <input type="text" name="nickname" class="form-control form-control-sm" value="<?= htmlspecialchars($currentUser['nickname'] ?? '') ?>" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label small d-flex justify-content-between align-items-center">
                        <span>邮箱</span>
                        <button type="button" class="btn btn-link btn-sm p-0" data-bs-toggle="modal" data-bs-target="#modalEmailChange">换绑邮箱</button>
                    </label>
                    <input type="email" class="form-control form-control-sm" value="<?= htmlspecialchars($currentUser['email'] ?? '') ?>" disabled>
                    <div class="form-text small">
                        <?= !empty($currentUser['email_verified']) ? '当前邮箱已验证，可用于登录通知和重置密码。' : '当前邮箱尚未验证，部分功能可能受限，请尽快完成验证。' ?>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label small">注册时间</label>
                    <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($currentUser['created_at'] ?? '') ?>" disabled>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-sm btn-primary">保存昵称</button>
                </div>
            </form>

            <?php if ($miniappEnabled): ?>
            <?php $miniappEnabled = \App\Service\Config::get('wechat.enable_miniapp', true); ?>

            <?php if ($miniappEnabled): ?>
            <div class="mt-3 pt-3 border-top">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="small text-muted">微信小程序绑定</div>
                    <?php if (!empty($hasWechatBinding)): ?>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success">已绑定</span>
                            <form method="post" class="d-inline" onsubmit="return confirm('确认解绑当前微信？解绑后可用新微信在小程序中登录或重新扫码绑定。');">
                                <input type="hidden" name="action" value="unbind_wechat">
                                <button type="submit" class="btn btn-sm btn-outline-danger">解绑微信</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="action" value="self_generate_bind_qr">
                            <button type="submit" class="btn btn-sm btn-outline-success">生成绑定二维码</button>
                        </form>
                    <?php endif; ?>
                </div>
                <?php if (!empty($isMiniappUser) && !empty($hasWechatBinding)): ?>
                    <div class="small text-muted">您是通过小程序注册的账号，默认已完成微信绑定，无需重复绑定。如需更换微信，可先解绑后再在小程序中登录/绑定。</div>
                <?php elseif (!empty($hasWechatBinding)): ?>
                    <div class="small text-muted">当前账号已绑定微信<?= !empty($wechatBinding['last_login_at']) ? '，最近微信登录：' . htmlspecialchars($wechatBinding['last_login_at']) : '' ?>。如需更换微信，可解绑后在小程序中重新绑定。</div>
                <?php else: ?>
                    <div class="small text-muted">用于将当前账号与小程序绑定，便于在手机端使用同一数据。生成后请在有效期内打开小程序扫码完成绑定。</div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <div class="mt-3 pt-3 border-top">
                <form method="post" class="row g-2 align-items-center">
                    <input type="hidden" name="action" value="update_theme">
                    <?php $themeMode = $currentUser['theme_mode'] ?? ($_SESSION['theme_mode'] ?? 'light'); ?>
                    <div class="col-12 col-md-auto">
                        <label class="form-label small mb-1 mb-md-0">主题模式</label>
                    </div>
                    <div class="col-8 col-md-4 col-lg-3">
                        <select name="theme_mode" class="form-select form-select-sm">
                            <option value="light" <?= $themeMode === 'light' ? 'selected' : '' ?>>白天模式</option>
                            <option value="dark" <?= $themeMode === 'dark' ? 'selected' : '' ?>>夜间模式</option>
                        </select>
                    </div>
                    <div class="col-4 col-md-3 col-lg-2">
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">保存主题</button>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="form-text small mt-1 mt-lg-0">更改后将在下次页面加载时应用到整个系统。</div>
                    </div>
                </form>
            </div>

            <div class="mt-3 pt-3 border-top">
                <form method="post" class="row g-2 align-items-center">
                    <input type="hidden" name="action" value="update_budget_reminder">
                    <?php $budgetReminderEnabled = isset($currentUser['budget_reminder_enabled']) ? (int)$currentUser['budget_reminder_enabled'] : 1; ?>
                    <div class="col-12 col-md-auto">
                        <label class="form-label small mb-1 mb-md-0">预算提醒</label>
                    </div>
                    <div class="col-8 col-md-4 col-lg-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="budgetReminderEnabled" name="budget_reminder_enabled" value="1" <?= $budgetReminderEnabled ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="budgetReminderEnabled">开启接近上限 / 超支提醒</label>
                        </div>
                    </div>
                    <div class="col-4 col-md-3 col-lg-2">
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">保存设置</button>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="form-text small mt-1 mt-lg-0">关闭后，小程序和 PC 端仅展示预算数据，不再高亮或提示“接近上限 / 已超支”。</div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- 修改用户名弹窗 -->
    <div class="modal fade" id="modalUsernameChange" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">修改用户名</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($usernameModalError)): ?>
                        <div class="alert alert-danger py-2 small mb-2"><?= htmlspecialchars($usernameModalError) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($usernameModalSuccess)): ?>
                        <div class="alert alert-success py-2 small mb-2"><?= htmlspecialchars($usernameModalSuccess) ?></div>
                    <?php endif; ?>
                    <form method="post" class="row g-3">
                        <input type="hidden" name="action" value="change_username">
                        <div class="col-12">
                            <label class="form-label small">新用户名</label>
                            <input type="text" name="new_username" class="form-control form-control-sm" value="<?= htmlspecialchars($pendingUsername !== '' ? $pendingUsername : ($currentUser['username'] ?? '')) ?>" required>
                            <div class="form-text small">建议保持原有用户名；如需修改，请先验证新用户名是否可用。</div>
                        </div>
                        <div class="col-12 d-flex justify-content-end align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                            <button type="submit" class="btn btn-sm btn-outline-secondary" name="submit_type" value="check">验证</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="suggestUsername()">使用推荐</button>
                            <button type="submit" class="btn btn-sm btn-primary" name="submit_type" value="save">确认修改</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php if (!empty($openModal) && $openModal === 'username'): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modalEl = document.getElementById('modalUsernameChange');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            });
        </script>
    <?php endif; ?>

    <!-- 个人绑定二维码弹窗 -->
    <div class="modal fade" id="modalBindQr" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">微信绑定二维码</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="small text-muted mb-2"><?= htmlspecialchars($system['bind_qr_text'] ?? '') ?></div>
                    <?php if (!empty($selfBindQrPayload) && !empty($selfBindQrToken)): ?>
                        <div class="d-flex flex-column align-items-center">
                            <div id="selfBindQr" class="border rounded mb-2" style="width:180px;height:180px;"></div>
                            <div class="small text-muted">过期时间：<?= htmlspecialchars($selfBindQrExpiresAt ?? '') ?>，绑定码：<span class="badge bg-secondary"><?= htmlspecialchars($selfBindQrToken) ?></span></div>
                        </div>
                        <script src="/assets/js/qrcode.min.js"></script>
                        <script>
                        (function(){
                            try {
                                var payload = <?= json_encode($selfBindQrPayload ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                                var el = document.getElementById('selfBindQr');
                                if (window.QRCode && typeof window.QRCode === 'function' && el) {
                                    new QRCode(el, { text: payload, width: 180, height: 180 });
                                }
                            } catch (e) { console.error(e); }
                        })();
                        </script>
                    <?php else: ?>
                        <div class="text-muted small">请点击“生成绑定二维码”按钮创建二维码。</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php if (!empty($openModal) && $openModal === 'bindqr'): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modalEl = document.getElementById('modalBindQr');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            });
        </script>
    <?php endif; ?>

    <!-- 换绑邮箱弹窗 -->
    <div class="modal fade" id="modalEmailChange" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">编辑邮箱</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" class="row g-3">
                        <input type="hidden" name="action" value="change_email">
                        <div class="col-12">
                            <?php if (!empty($emailModalError)): ?>
                                <div class="alert alert-danger py-2 small mb-2"><?= htmlspecialchars($emailModalError) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($emailModalSuccess)): ?>
                                <div class="alert alert-success py-2 small mb-2"><?= htmlspecialchars($emailModalSuccess) ?></div>
                            <?php endif; ?>
                            <label class="form-label small">邮箱地址</label>
                            <input type="email" name="new_email" class="form-control form-control-sm" value="<?= htmlspecialchars($pendingEmail !== '' ? $pendingEmail : ($currentUser['email'] ?? '')) ?>" required>
                            <div class="form-text small">直接编辑并保存即可更新邮箱，用于接收公告和密码重置邮件。</div>
                        </div>
                        <div class="col-12 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                            <button type="submit" class="btn btn-sm btn-primary">保存</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php if (!empty($openModal) && $openModal === 'email'): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modalEl = document.getElementById('modalEmailChange');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            });
        </script>
    <?php endif; ?>
<?php elseif ($tab === 'security'): ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h3 class="h6 mb-3">修改登录密码</h3>
            <form method="post" class="row g-3">
                <input type="hidden" name="action" value="change_password">
                <div class="col-12 col-md-4">
                    <label class="form-label small d-block mb-1">旧密码</label>
                    <input type="password" name="old_password" class="form-control form-control-sm w-75" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small d-block mb-1">新密码</label>
                    <input type="password" name="new_password" class="form-control form-control-sm w-75" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small d-block mb-1">确认新密码</label>
                    <input type="password" name="confirm_password" class="form-control form-control-sm w-75" required>
                </div>
                <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="small text-muted">建议使用至少 8 位且包含大小写字母与数字的密码。</div>
					<a href="/public/index.php?route=forgot-password" class="btn btn-link btn-sm p-0">忘记密码？</a>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-sm btn-primary">保存密码</button>
                </div>
            </form>
        </div>
    </div>
<?php elseif ($tab === 'system' && $isAdmin): ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h3 class="h6 mb-3">系统参数</h3>
            <form method="post" class="row g-3">
                <input type="hidden" name="action" value="update_system">
                <div class="col-12 col-md-6">
                    <label class="form-label small">站点名称</label>
                    <input type="text" name="site_name" class="form-control form-control-sm" value="<?= htmlspecialchars($system['site_name'] ?? '') ?>" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label small">站点网址</label>
                    <input type="url" name="site_url" class="form-control form-control-sm" value="<?= htmlspecialchars($system['site_url'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-6">
					<label class="form-label small">自动退出时间（小时）</label>
					<div class="input-group input-group-sm">
						<input type="number" name="session_timeout_hours" class="form-control" min="1" max="168" step="1" value="<?= htmlspecialchars((string)($system['session_timeout_hours'] ?? 24)) ?>">
						<button class="btn btn-outline-primary" type="submit">保存时间</button>
					</div>
					<div class="form-text small">从最后一次操作开始计时，超过设定时长将自动退出登录。建议设置为 24 小时，允许范围 1~168 小时。</div>
				</div>
                <div class="col-12">
                    <label class="form-label small">系统图标（SVG）</label>
                    <textarea id="site_icon_svg" name="site_icon_svg" class="form-control form-control-sm" rows="4" placeholder="在此粘贴完整的 &lt;svg&gt;...&lt;/svg&gt; 代码，用作浏览器标签页图标。"><?= htmlspecialchars($system['site_icon_svg'] ?? '') ?></textarea>
                    <div class="form-text small mb-2">
                        仅管理员可见。此 SVG 将作为全系统浏览器标签页的图标（favicon）使用，建议图形简洁、尺寸不宜过大。
                    </div>
                    <label class="form-label small mb-1">图标预览</label>
                    <div class="border rounded bg-white d-inline-flex align-items-center justify-content-center" style="width:48px;height:48px;overflow:hidden;">
                        <div id="site_icon_preview" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;"></div>
                    </div>
                    <div class="form-text small">预览仅基于当前输入内容，保存后全站标签页图标将更新为该 SVG。</div>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="allow_register" id="allow_register" <?= !empty($system['allow_register']) ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="allow_register">允许新用户注册</label>
                    </div>
                    <div class="form-text small">关闭注册后，仅管理员可通过数据库或其他方式创建新账号。</div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label small">绑定二维码有效期（分钟）</label>
                    <input type="number" name="bind_qr_expires_minutes" class="form-control form-control-sm" min="1" max="1440" step="1" value="<?= htmlspecialchars((string)($system['bind_qr_expires_minutes'] ?? 10)) ?>">
                    <div class="form-text small">用于注册成功页和后台“生成绑定码”所用二维码的有效期，建议 10~30 分钟，范围 1~1440 分钟。</div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label small">绑定二维码提示文案</label>
                    <textarea name="bind_qr_text" class="form-control form-control-sm" rows="3" placeholder="扫码绑定时展示的说明文字，可告诉用户如何在小程序中完成绑定。"><?= htmlspecialchars($system['bind_qr_text'] ?? '') ?></textarea>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-sm btn-primary">保存参数</button>
                </div>
            </form>
        </div>
    </div>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h3 class="h6 mb-3">授权设置</h3>
            <?php $licenseFixedCode = (string)\App\Service\Config::get('license.fixed_code', ''); ?>
            <form method="post" class="row g-3">
                <input type="hidden" name="action" value="update_license">
                <div class="col-12 col-md-6">
                    <label class="form-label small">授权码</label>
                    <?php if ($licenseFixedCode !== ''): ?>
                        <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($licenseFixedCode) ?>" disabled>
                        <div class="form-text small text-muted">当前授权码由配置文件 config.php 中的 <code>license.fixed_code</code> 固定管理，如需更换请编辑配置文件并重新部署。</div>
                    <?php else: ?>
                        <input type="text" name="license_code" class="form-control form-control-sm" value="<?= htmlspecialchars($system['license_code'] ?? '') ?>" placeholder="粘贴授权邮件中收到的授权码" required>
                        <div class="form-text small">系统只需要授权码即可联机校验，授权邮箱将由授权中心记录。</div>
                    <?php endif; ?>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label small">最近一次联机校验时间</label>
                    <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($system['license_last_check_at'] ?? '尚未联机') ?>" disabled>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label small">最近一次校验结果</label>
                    <?php
                    $licenseStatus = (string)($system['license_status'] ?? '');
                    $licenseStatusLabel = '未校验';
                    if ($licenseStatus === 'normal') {
                        $licenseStatusLabel = '正常（授权有效）';
                    } elseif ($licenseStatus === 'expired') {
                        $licenseStatusLabel = '已失效（停用或到期）';
                    } elseif ($licenseStatus === 'domain_mismatch') {
                        $licenseStatusLabel = '域名不匹配';
                    } elseif ($licenseStatus === 'not_found') {
                        $licenseStatusLabel = '未找到授权记录';
                    } elseif ($licenseStatus === 'network_error') {
                        $licenseStatusLabel = '网络异常，待下次重试';
                    } elseif ($licenseStatus !== '') {
                        $licenseStatusLabel = $licenseStatus;
                    }
                    ?>
                    <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($licenseStatusLabel) ?>" disabled>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <?php if ($licenseFixedCode !== ''): ?>
                        <button type="submit" class="btn btn-sm btn-primary" disabled title="授权码由配置文件固定管理，无法在此修改">保存并立即校验</button>
                    <?php else: ?>
                        <button type="submit" class="btn btn-sm btn-primary">保存并立即校验</button>
                    <?php endif; ?>
                </div>
            </form>
            <div class="small text-muted mt-2">
                系统将在每次访问时自动判断是否需要联机校验授权，默认每 24 小时联机一次；如连续离线超过 7 天且未成功联机，将自动暂停系统使用以保障授权安全。
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="h6 mb-0">公告推送</h3>
                <button type="button" class="btn btn-sm btn-primary" id="btnAnnouncementCreate" data-bs-toggle="modal" data-bs-target="#announcementModal">新建公告</button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th style="width:60px;">ID</th>
                        <th style="width:180px;">标题</th>
                        <th>内容预览</th>
                        <th style="width:180px;">推送时间</th>
                        <th style="width:120px;">查看用户数</th>
                        <th style="width:220px;" class="text-center">操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($announcements)): ?>
                        <tr><td colspan="6" class="text-center text-muted small">暂无公告</td></tr>
                    <?php else: ?>
                        <?php foreach ($announcements as $a): ?>
                            <?php
                            $preview = trim(mb_substr(strip_tags((string)($a['content'] ?? '')), 0, 10, 'UTF-8'));
                            if ($preview === '') { $preview = '（无内容）'; }
                            ?>
                            <tr>
                                <td><?= (int)$a['id'] ?></td>
                                <td><?= htmlspecialchars($a['title'] ?? '') ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($preview) ?><?= mb_strlen((string)($a['content'] ?? ''), 'UTF-8') > 10 ? '…' : '' ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($a['scheduled_at'] ?? '') ?></td>
                                <td><?= (int)($a['view_count'] ?? 0) ?></td>
                                <td class="text-center">
                                    <div class="d-flex flex-wrap justify-content-center gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="editAnnouncement(
                                                <?= (int)$a['id'] ?>,
                                                <?= json_encode((string)($a['title'] ?? ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                                                <?= json_encode((string)($a['content'] ?? ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                                                <?= json_encode((string)($a['scheduled_at'] ?? ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
                                            )">编辑</button>
                                        <form method="post" class="d-inline" onsubmit="return confirm('确定要删除该公告及其阅读统计吗？');">
                                            <input type="hidden" name="action" value="announcement_delete">
                                            <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">删除</button>
                                        </form>
                                        <form method="post" class="d-inline" onsubmit="return confirm('确定要以当前内容重新推送一条新公告吗？');">
                                            <input type="hidden" name="action" value="announcement_repush">
                                            <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">重新推送</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="h6 mb-0">邮件推送</h3>
                <button type="button" class="btn btn-sm btn-primary" id="btnEmailPushCreate" data-bs-toggle="modal" data-bs-target="#emailPushModal">新建推送</button>
            </div>
            <div class="form-text small mb-2">
                当前系统使用企业邮箱的 SMTP 或 PHP mail() 直接发送邮件，配置在 config/config.php 中。全量推送会向所有状态正常且已填写邮箱的用户发送，选择推送则仅向勾选的用户发送。
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th style="width:60px;">ID</th>
                        <th style="width:200px;">标题</th>
                        <th>内容预览</th>
                        <th style="width:160px;">计划时间</th>
                        <th style="width:160px;">最近发送时间</th>
                        <th style="width:100px;">状态</th>
                        <th style="width:200px;" class="text-center">操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($emailPushes)): ?>
                        <tr><td colspan="7" class="text-center text-muted small">暂无邮件推送记录</td></tr>
                    <?php else: ?>
                        <?php foreach ($emailPushes as $p): ?>
                            <?php
                            $preview = trim(mb_substr(strip_tags((string)($p['content'] ?? '')), 0, 10, 'UTF-8'));
                            if ($preview === '') { $preview = '（无内容）'; }
                            ?>
                            <tr>
                                <td><?= (int)$p['id'] ?></td>
                                <td><?= htmlspecialchars($p['title'] ?? '') ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($preview) ?><?= mb_strlen((string)($p['content'] ?? ''), 'UTF-8') > 10 ? '…' : '' ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($p['scheduled_at'] ?? '') ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($p['sent_at'] ?? '') ?></td>
                                <td class="small"><?= htmlspecialchars($p['status'] ?? '') ?></td>
                                <td class="text-center">
                                    <div class="d-flex flex-wrap justify-content-center gap-1">
                                        <form method="post" class="d-inline" onsubmit="return confirm('确定要重新发送该邮件推送吗？');">
                                            <input type="hidden" name="action" value="email_push_resend">
                                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">重新发送</button>
                                        </form>
                                        <form method="post" class="d-inline" onsubmit="return confirm('确定要删除该邮件推送记录吗？不会影响已发送的邮件。');">
                                            <input type="hidden" name="action" value="email_push_delete">
                                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">删除</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- 公告推送弹窗 -->
    <div class="modal fade" id="announcementModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">公告推送</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" class="row g-2 align-items-end" id="announcement-form">
                        <input type="hidden" name="action" id="announcement_action" value="announcement_create">
                        <input type="hidden" name="id" id="announcement_id" value="">
                        <div class="col-12 col-md-4">
                            <label class="form-label small">公告标题</label>
                            <input type="text" name="announcement_title" id="announcement_title" class="form-control form-control-sm" maxlength="255" required>
                        </div>
                        <div class="col-12 col-md-8">
                            <label class="form-label small">公告内容</label>
                            <textarea name="announcement_content" id="announcement_content" class="form-control form-control-sm" rows="3" placeholder="请输入需要展示给所有用户的公告内容" required></textarea>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small">推送方式</label>
                            <div class="mb-1">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="announcement_send_type" id="announcement_send_now" value="now" checked>
                                    <label class="form-check-label small" for="announcement_send_now">立即推送</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="announcement_send_type" id="announcement_send_schedule" value="schedule">
                                    <label class="form-check-label small" for="announcement_send_schedule">按时间推送</label>
                                </div>
                            </div>
                            <input type="datetime-local" name="announcement_scheduled_at" id="announcement_scheduled_at" class="form-control form-control-sm" placeholder="默认为当前时间，可自定义">
                        </div>
                        <div class="col-12 col-md-6 d-flex justify-content-end align-items-end gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                            <button type="submit" class="btn btn-sm btn-primary" id="announcement_submit_btn">创建公告</button>
                        </div>
                        <div class="col-12 mt-1">
                            <div class="form-text small" id="announcement_form_hint">创建后，公告将在 PC 首页和小程序首页登录时以弹窗形式展示，用户关闭视为已查看。</div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 邮件推送弹窗 -->
    <div class="modal fade" id="emailPushModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">新建邮件推送</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" class="row g-2 align-items-end">
                        <input type="hidden" name="action" value="email_push_create">
                        <div class="col-12 col-md-4">
                            <label class="form-label small">邮件标题</label>
                            <input type="text" name="email_title" id="email_title" class="form-control form-control-sm" maxlength="255" required>
                        </div>
                        <div class="col-12 col-md-8">
                            <label class="form-label small">邮件内容</label>
                            <textarea name="email_content" id="email_content" class="form-control form-control-sm" rows="3" placeholder="支持 HTML 内容，用于向用户发送维护通知等" required></textarea>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small">推送范围</label>
                            <div class="mb-1">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="email_scope" id="email_scope_all" value="all" checked>
                                    <label class="form-check-label small" for="email_scope_all">全量推送</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="email_scope" id="email_scope_selected" value="selected">
                                    <label class="form-check-label small" for="email_scope_selected">选择推送</label>
                                </div>
                            </div>
                            <select name="email_selected_users[]" id="email_selected_users" class="form-select form-select-sm" multiple size="6" disabled>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= (int)$u['id'] ?>"><?= (int)$u['id'] ?> - <?= htmlspecialchars($u['username']) ?> (<?= htmlspecialchars($u['email']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small">发送时间</label>
                            <div class="mb-1">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="email_send_type" id="email_send_now" value="now" checked>
                                    <label class="form-check-label small" for="email_send_now">立即发送</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="email_send_type" id="email_send_schedule" value="schedule">
                                    <label class="form-check-label small" for="email_send_schedule">定时发送</label>
                                </div>
                            </div>
                            <input type="datetime-local" name="email_scheduled_at" id="email_scheduled_at" class="form-control form-control-sm" placeholder="留空则使用当前时间">
                        </div>
                        <div class="col-12 col-md-4 d-flex justify-content-end align-items-end gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                            <button type="submit" class="btn btn-sm btn-primary">创建推送</button>
                        </div>
                        <div class="col-12 mt-1">
                            <div class="form-text small">全量推送会向所有状态正常且已填写邮箱的用户发送，选择推送则仅向勾选的用户发送。</div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        (function() {
            function toggleEmailUserSelect() {
                var scopeAll = document.getElementById('email_scope_all');
                var selectEl = document.getElementById('email_selected_users');
                if (!scopeAll || !selectEl) return;
                var useAll = scopeAll.checked;
                selectEl.disabled = useAll;
            }
            document.addEventListener('DOMContentLoaded', function () {
                var scopeRadios = document.querySelectorAll('input[name="email_scope"]');
                scopeRadios.forEach(function (r) { r.addEventListener('change', toggleEmailUserSelect); });
                toggleEmailUserSelect();

                var btnAnnouncement = document.getElementById('btnAnnouncementCreate');
                if (btnAnnouncement) {
                    btnAnnouncement.addEventListener('click', function () {
                        if (typeof resetAnnouncementForm === 'function') {
                            resetAnnouncementForm();
                        }
                    });
                }

                var btnEmailPush = document.getElementById('btnEmailPushCreate');
                if (btnEmailPush) {
                    btnEmailPush.addEventListener('click', function () {
                        if (typeof resetEmailPushForm === 'function') {
                            resetEmailPushForm();
                        }
                    });
                }
            });
        })();

        function resetAnnouncementForm() {
            try {
                var action = document.getElementById('announcement_action');
                var idInput = document.getElementById('announcement_id');
                var titleInput = document.getElementById('announcement_title');
                var contentInput = document.getElementById('announcement_content');
                var dtInput = document.getElementById('announcement_scheduled_at');
                var nowRadio = document.getElementById('announcement_send_now');
                var scheduleRadio = document.getElementById('announcement_send_schedule');
                if (action) action.value = 'announcement_create';
                if (idInput) idInput.value = '';
                if (titleInput) titleInput.value = '';
                if (contentInput) contentInput.value = '';
                if (dtInput) dtInput.value = '';
                if (nowRadio) nowRadio.checked = true;
                if (scheduleRadio) scheduleRadio.checked = false;
                var hint = document.getElementById('announcement_form_hint');
                if (hint) {
                    hint.textContent = '创建后，公告将在 PC 首页和小程序首页登录时以弹窗形式展示，用户关闭视为已查看。';
                }
                var btn = document.getElementById('announcement_submit_btn');
                if (btn) {
                    btn.textContent = '创建公告';
                }
            } catch (e) { console.error(e); }
        }

        function editAnnouncement(id, title, content, scheduledAt) {
            try {
                var form = document.getElementById('announcement-form');
                if (!form) return;
                document.getElementById('announcement_action').value = 'announcement_update';
                document.getElementById('announcement_id').value = id;
                document.getElementById('announcement_title').value = title || '';
                document.getElementById('announcement_content').value = content || '';
                // 将 YYYY-MM-DD HH:MM:SS 转为 datetime-local 可识别格式
                var dtInput = document.getElementById('announcement_scheduled_at');
                if (scheduledAt && dtInput) {
                    var replaced = scheduledAt.replace(' ', 'T').slice(0, 16);
                    dtInput.value = replaced;
                }
                var nowRadio = document.getElementById('announcement_send_now');
                var scheduleRadio = document.getElementById('announcement_send_schedule');
                if (scheduleRadio && dtInput && dtInput.value) {
                    scheduleRadio.checked = true;
                } else if (nowRadio) {
                    nowRadio.checked = true;
                }
                var hint = document.getElementById('announcement_form_hint');
                if (hint) {
                    hint.textContent = '当前为“编辑公告”模式，保存后将覆盖该公告的标题、内容和推送时间。点击浏览器刷新可退出编辑模式。';
                }
                var btn = document.getElementById('announcement_submit_btn');
                if (btn) {
                    btn.textContent = '保存公告修改';
                }

                var modalEl = document.getElementById('announcementModal');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            } catch (e) { console.error(e); }
        }

        function resetEmailPushForm() {
            try {
                var titleInput = document.getElementById('email_title');
                var contentInput = document.getElementById('email_content');
                var scopeAll = document.getElementById('email_scope_all');
                var scopeSelected = document.getElementById('email_scope_selected');
                var selectedUsers = document.getElementById('email_selected_users');
                var sendNow = document.getElementById('email_send_now');
                var sendSchedule = document.getElementById('email_send_schedule');
                var dtInput = document.getElementById('email_scheduled_at');
                if (titleInput) titleInput.value = '';
                if (contentInput) contentInput.value = '';
                if (scopeAll) scopeAll.checked = true;
                if (scopeSelected) scopeSelected.checked = false;
                if (selectedUsers) {
                    selectedUsers.disabled = true;
                    for (var i = 0; i < selectedUsers.options.length; i++) {
                        selectedUsers.options[i].selected = false;
                    }
                }
                if (sendNow) sendNow.checked = true;
                if (sendSchedule) sendSchedule.checked = false;
                if (dtInput) dtInput.value = '';
            } catch (e) { console.error(e); }
        }
    </script>
<?php elseif ($tab === 'users' && $isAdmin): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h3 class="h6 mb-3">用户管理</h3>
            <form class="mb-3" onsubmit="return false;">
                <div class="row g-2 align-items-center">
                    <div class="col-auto">
                        <label for="user-search-input" class="col-form-label small text-muted">模糊搜索</label>
                    </div>
                    <div class="col-sm-4 col-md-4 col-lg-3">
                        <input type="search" id="user-search-input" class="form-control form-control-sm" placeholder="输入任意关键字，实时筛选列表">
                    </div>
                    <div class="col-sm-3 col-md-3 col-lg-2">
                        <select id="user-bind-filter" class="form-select form-select-sm">
                            <option value="">全部绑定状态</option>
                            <option value="bound">仅已绑定</option>
                            <option value="unbound">仅未绑定</option>
                        </select>
                    </div>
                    <div class="col-auto small text-muted">
                        支持按用户名、昵称、邮箱、注册来源、微信绑定、角色、状态等任意字段模糊匹配，并可按绑定状态快速筛选。
                    </div>
                </div>
            </form>
            <?php /* 移除后台在列表页生成绑定二维码的入口，绑定二维码改为用户个人信息页自行生成查看 */ ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0 settings-users-table">
                    <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>头像</th>
                        <th>用户名</th>
                        <th>昵称</th>
                        <th>邮箱</th>
                        <th>注册来源</th>
                        <th>微信绑定</th>
                        <th>角色</th>
                        <th>状态</th>
                        <th>邮箱验证</th>
                        <th>注册时间</th>
                        <th class="text-center">操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="10" class="text-center text-muted small">暂无用户</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <?php $bindCount = (int)($u['wechat_bind_count'] ?? 0); ?>
                            <tr data-wechat-bind="<?= $bindCount > 0 ? 'bound' : 'unbound' ?>">
                                <td><?= (int)$u['id'] ?></td>
                                <td>
                                    <?php if (!empty($u['avatar_path'])): ?>
                                        <img src="/uploads/<?= htmlspecialchars($u['avatar_path']) ?>" alt="头像" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;">
                                    <?php else: ?>
                                        <span class="text-muted small">无</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($u['username']) ?></td>
                                <td><?= htmlspecialchars($u['nickname']) ?></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <?php $src = $u['register_source'] ?? 'pc'; ?>
                                    <?php if ($src === 'miniapp'): ?>
                                        <span class="badge bg-info text-dark">小程序注册</span>
                                    <?php else: ?>
                                        <span class="text-muted small">PC/网页注册</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $lastLoginAt = $u['wechat_last_login_at'] ?? null;
                                    if ($bindCount > 0): ?>
                                        <span class="badge bg-success me-1">已绑定</span>
                                        <?php if (!empty($lastLoginAt)): ?>
                                            <span class="text-muted small">最近登录：<?= htmlspecialchars($lastLoginAt) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">有绑定记录</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">未绑定</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $u['role'] === 'admin' ? '管理员' : '普通用户' ?></td>
                                <td><?= (int)$u['status'] === 1 ? '正常' : '禁用' ?></td>
                                <td><?= !empty($u['email_verified']) ? '已验证' : '未验证' ?></td>
                                <td><?= htmlspecialchars($u['created_at']) ?></td>
                                <td class="text-center">
                                    <div class="d-flex flex-wrap justify-content-center gap-1">
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="action" value="user_status">
                                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                            <input type="hidden" name="status" value="<?= (int)$u['status'] === 1 ? 0 : 1 ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                <?= (int)$u['status'] === 1 ? '禁用' : '启用' ?>
                                            </button>
                                        </form>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="action" value="user_role">
                                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                            <input type="hidden" name="role" value="<?= $u['role'] === 'admin' ? 'user' : 'admin' ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                                <?= $u['role'] === 'admin' ? '设为普通' : '设为管理员' ?>
                                            </button>
                                        </form>
                                        <form method="post" class="d-inline" onsubmit="return confirm('确定要为该用户重置密码并发送邮件通知吗？');">
                                            <input type="hidden" name="action" value="user_reset_password">
                                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-warning">重置密码</button>
                                        </form>
                                        <form method="post" class="d-inline" onsubmit="return confirm('确定要强制删除该用户及其所有数据吗？此操作无法恢复。');">
                                            <input type="hidden" name="action" value="user_delete">
                                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">强制删除</button>
                                        </form>
                                        <?php /* 绑定二维码按钮已迁移至用户个人信息页 */ ?>
                                        <form method="post" class="d-inline" onsubmit="return confirm('将为该用户注入一套默认分类/项目/账户，仅在其当前无任何相关数据时生效，确定继续吗？');">
                                            <input type="hidden" name="action" value="user_seed_defaults">
                                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-info">注入默认数据</button>
                                        </form>
                                        <?php if ($bindCount > 0): ?>
                                            <form method="post" class="d-inline" onsubmit="return confirm('确定要为该用户解除微信绑定吗？解绑后该用户需要重新在小程序登录或扫码绑定才能继续使用。');">
                                                <input type="hidden" name="action" value="user_unbind_wechat">
                                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-success">解除微信绑定</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="small text-muted mt-2">提示：无法禁用或删除当前登录账号，以避免误操作。</div>
        </div>
    </div>
<?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="text-muted small">请选择上方标签进入对应设置页面。</div>
        </div>
    </div>
<?php endif; ?>

<?php if ($tab === 'users' && $isAdmin): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var input = document.getElementById('user-search-input');
            var bindSelect = document.getElementById('user-bind-filter');
            var tableBody = document.querySelector('.settings-users-table tbody');
            if (!input || !tableBody) return;

            function applyUserFilter() {
                var keyword = input.value.trim().toLowerCase();
                var bindStatus = bindSelect ? bindSelect.value : '';
                var rows = tableBody.querySelectorAll('tr');
                rows.forEach(function (row) {
                    // "暂无用户" 这种只有一格提示行特殊处理
                    if (row.children.length <= 1) {
                        row.style.display = (keyword || bindStatus) ? 'none' : '';
                        return;
                    }
                    var text = (row.textContent || '').toLowerCase();
                    var rowBind = row.getAttribute('data-wechat-bind') || '';

                    if (keyword && text.indexOf(keyword) === -1) {
                        row.style.display = 'none';
                        return;
                    }

                    if (bindStatus === 'bound' && rowBind !== 'bound') {
                        row.style.display = 'none';
                        return;
                    }
                    if (bindStatus === 'unbound' && rowBind !== 'unbound') {
                        row.style.display = 'none';
                        return;
                    }

                    row.style.display = '';
                });
            }

            input.addEventListener('input', applyUserFilter);
            if (bindSelect) {
                bindSelect.addEventListener('change', applyUserFilter);
            }
        });
    </script>
<?php endif; ?>

<?php if ($tab === 'system' && $isAdmin): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var textarea = document.getElementById('site_icon_svg');
            var preview = document.getElementById('site_icon_preview');
            if (!textarea || !preview) return;

            function updatePreview() {
                var svg = textarea.value.trim();
                if (svg) {
                    preview.innerHTML = svg;
                } else {
                    preview.innerHTML = '<span class="text-muted small">暂无图标</span>';
                }
            }

            textarea.addEventListener('input', updatePreview);
            updatePreview();
        });
    </script>
<?php endif; ?>
