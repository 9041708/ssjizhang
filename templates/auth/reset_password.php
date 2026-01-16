<h2 class="h5 mb-3">设置新密码</h2>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger small">
        <?php foreach ($errors as $e): ?>
            <div><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<form method="post" novalidate>
    <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">
    <div class="mb-3">
        <label class="form-label">新密码</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">确认新密码</label>
        <input type="password" name="password_confirm" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">提交</button>
</form>
