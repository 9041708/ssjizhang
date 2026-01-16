<h2 class="h5 mb-3">注册</h2>
<div class="mb-3 small text-muted">
    想要更快捷？<a href="/public/index.php?route=register&amp;mode=quick">使用扫码注册</a>（用小程序微信一键登录后，扫码即可在PC自动登录）。
    完成后还可在第二步设置密码与邮箱。
  
</div>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger small">
        <?php foreach ($errors as $e): ?>
            <div><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success small"><?= htmlspecialchars($success) ?></div>
    <div class="d-flex flex-wrap gap-2 mb-3">
        <?php if (!empty($bindToken)): ?>
        <a class="btn btn-primary btn-sm" href="/public/index.php?route=register-bind&amp;token=<?= urlencode($bindToken) ?>">去第二步：绑定小程序（推荐）</a>
        <?php else: ?>
        <a class="btn btn-primary btn-sm" href="/public/index.php?route=register-bind">去第二步：绑定小程序（推荐）</a>
        <?php endif; ?>
        <a class="btn btn-outline-secondary btn-sm" href="/public/index.php?route=login">暂不绑定，先去登录</a>
    </div>
<?php endif; ?>
<form method="post" novalidate>
    <div class="mb-3">
        <label class="form-label">用户名（系统唯一）</label>
        <input type="text" name="username" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">用户昵称</label>
        <input type="text" name="nickname" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">邮箱</label>
        <input type="email" name="email" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">密码</label>
        <div class="input-group">
            <input type="password" name="password" id="register_password" class="form-control" required>
            <button class="btn btn-outline-secondary" type="button" data-toggle="password" data-target="register_password">显示</button>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">确认密码</label>
        <div class="input-group">
            <input type="password" name="password_confirm" id="register_password_confirm" class="form-control" required>
            <button class="btn btn-outline-secondary" type="button" data-toggle="password" data-target="register_password_confirm">显示</button>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">图形验证码</label>
        <div class="input-group">
            <input type="text" name="captcha" class="form-control" maxlength="6" placeholder="请输入图中的数字" required>
            <span class="input-group-text p-0 bg-white">
                <img src="/public/captcha.php?scene=register&rand=<?= time() ?>" alt="验证码" style="height:38px;cursor:pointer;border-radius:0 0.25rem 0.25rem 0;" onclick="this.src='/public/captcha.php?scene=register&rand=' + Date.now();" title="看不清？点击更换">
            </span>
        </div>
        <div class="form-text small">请输入图中的 4-6 位数字。</div>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="/public/index.php?route=login" class="small">已有账号？去登录</a>
    </div>
    <button type="submit" class="btn btn-primary w-100">注册</button>
</form>
