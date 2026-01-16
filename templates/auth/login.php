<?php
use App\Service\Config as AppConfig;
$miniappEnabled = (bool)AppConfig::get('wechat.enable_miniapp', true);
?>

<h2 class="h5 mb-3">登录</h2>

<?php if (!empty($_GET['expired'])): ?>
	<div class="alert alert-info small">已超过 24 小时未操作，请重新登录。</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<form method="post" novalidate>
    <div class="mb-3">
        <label class="form-label">用户名或邮箱</label>
        <input type="text" name="account" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">密码</label>
        <div class="input-group">
            <input type="password" name="password" id="login_password" class="form-control" required>
            <button class="btn btn-outline-secondary" type="button" data-toggle="password" data-target="login_password">显示</button>
        </div>
    </div>
    <?php if (!empty($showCaptcha)): ?>
    <div class="mb-3">
        <label class="form-label">图形验证码</label>
        <div class="input-group">
            <input type="text" name="captcha" class="form-control" maxlength="6" placeholder="请输入图中的数字" required>
            <span class="input-group-text p-0 bg-white">
                <img src="/public/captcha.php?scene=login&rand=<?= time() ?>" alt="验证码" style="height:38px;cursor:pointer;border-radius:0 0.25rem 0.25rem 0;" onclick="this.src='/public/captcha.php?scene=login&rand=' + Date.now();" title="看不清？点击更换">
            </span>
        </div>
        <div class="form-text small">为保护账户安全，多次输错密码后需要输入验证码。</div>
    </div>
    <?php endif; ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="/public/index.php?route=forgot-password" class="small">忘记密码？</a>
        <a href="/public/index.php?route=register" class="small">没有账号？去注册</a>
    </div>
        <button type="submit" class="btn btn-primary w-100">登录</button>
</form>
<?php if ($miniappEnabled): ?>
<div class="mt-3">
    <a href="/public/index.php?route=qr-login" class="btn btn-outline-primary w-100">使用小程序扫码登录</a>
</div>
<?php endif; ?>
