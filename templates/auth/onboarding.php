<h2 class="h5 mb-3">第二步：完善资料</h2>
<?php if (!empty($errors)): ?>
  <div class="alert alert-danger small mb-3">
    <?php foreach ($errors as $e): ?>
      <div><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php if (!empty($success)): ?>
  <div class="alert alert-success small mb-3"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<div class="mb-3 small text-muted">当前账号：<strong><?= htmlspecialchars($user['username'] ?? '') ?></strong>（可在小程序设置中修改用户名）。</div>
<form method="post" novalidate>
  <div class="mb-3">
    <label class="form-label">邮箱（可选，建议填写）</label>
    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="用于找回密码、接收通知">
  </div>
  <div class="mb-3">
    <label class="form-label">设置登录密码（可选）</label>
    <div class="input-group">
      <input type="password" name="password" id="onboarding_password" class="form-control" placeholder="至少 6 位">
      <button class="btn btn-outline-secondary" type="button" data-toggle="password" data-target="onboarding_password">显示</button>
    </div>
  </div>
  <div class="mb-3">
    <label class="form-label">确认密码</label>
    <div class="input-group">
      <input type="password" name="password_confirm" id="onboarding_password_confirm" class="form-control">
      <button class="btn btn-outline-secondary" type="button" data-toggle="password" data-target="onboarding_password_confirm">显示</button>
    </div>
  </div>
  <div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary flex-grow-1">保存并进入首页</button>
    <a class="btn btn-outline-secondary" href="/">暂时跳过</a>
  </div>
</form>
<script>
(function(){
  document.addEventListener('click', function(e){
    var t = e.target as HTMLElement;
    if (!t) return;
    var toggle = t.getAttribute('data-toggle');
    if (toggle === 'password'){
      var id = t.getAttribute('data-target');
      var el = id ? document.getElementById(id) as HTMLInputElement : null;
      if (el){ el.type = (el.type === 'password') ? 'text' : 'password'; }
    }
  });
})();
</script>
