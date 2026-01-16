<h2 class="h5 mb-3">重置密码</h2>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger small mb-3"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (!empty($message)): ?>
    <div class="alert alert-success small mb-3">
        <div class="mb-2">
            ✅ <?= htmlspecialchars($message) ?>
        </div>
        <?php if (!empty($email)): ?>
            <div class="mb-2">
                📧 检测到您的邮箱为：
                <strong class="text-primary">
                    <?= htmlspecialchars($email) ?>
                </strong>
            </div>
        <?php endif; ?>
        <?php if (!empty($emailLoginUrl)): ?>
            <a href="<?= htmlspecialchars($emailLoginUrl) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                点击此处快速登录<?= htmlspecialchars($emailProvider) ?> →
            </a>
        <?php else: ?>
            <div class="text-muted">💡 请登录您的邮箱查收重置邮件，如未收到可检查垃圾邮箱。</div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<form method="post" novalidate>
    <div class="mb-3">
        <label class="form-label">注册邮箱</label>
        <input type="email" name="email" class="form-control" required value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
    </div>
    <button type="submit" class="btn btn-primary w-100">发送重置链接</button>
    <div class="mt-3 text-center">
        <a href="/public/index.php?route=login" class="small">返回登录</a>
    </div>
</form>
