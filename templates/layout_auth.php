<?php
/** @var string $appName */
use App\Model\SystemSetting;

$systemSetting = SystemSetting::get();
$siteIconSvg = $systemSetting['site_icon_svg'] ?? null;
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d6efd">
    <title><?= htmlspecialchars($appName) ?> - 登录/注册</title>
    <?php if (!empty($siteIconSvg)): ?>
        <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<?= rawurlencode($siteIconSvg) ?>">
    <?php endif; ?>
    <link rel="manifest" href="/public/manifest.json">
    <?php $bootstrapCssLocal = __DIR__ . '/../assets/vendor/bootstrap/bootstrap.min.css'; ?>
    <?php if (is_file($bootstrapCssLocal)): ?>
        <link href="/assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <?php else: ?>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php endif; ?>
    <link href="/assets/css/app.css" rel="stylesheet">
</head>
<?php
$themeMode = $_SESSION['theme_mode'] ?? 'light';
$themeClass = $themeMode === 'dark' ? 'theme-dark' : 'theme-light';
?>
<body class="<?= $themeClass ?> d-flex align-items-center" style="min-height:100vh;">
<div class="container py-4 py-md-5">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-7 col-lg-6 col-xl-5">
            <div class="text-center mb-4">
                <h1 class="h2 fw-bold mb-1"><?= htmlspecialchars($appName) ?></h1>
                <div class="text-muted small">请登录后开始记账</div>
            </div>
            <div class="card shadow border-0">
                <div class="card-body p-4 p-md-5">
                    <?php include __DIR__ . '/' . $view . '.php'; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $bootstrapJsLocal = __DIR__ . '/../assets/vendor/bootstrap/bootstrap.bundle.min.js'; ?>
<?php if (is_file($bootstrapJsLocal)): ?>
<script src="/assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<?php else: ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php endif; ?>
<script>
document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-toggle="password"]');
    if (!btn) return;
    var targetId = btn.getAttribute('data-target');
    if (!targetId) return;
    var input = document.getElementById(targetId);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = '隐藏';
    } else {
        input.type = 'password';
        btn.textContent = '显示';
    }
});
</script>
</body>
</html>
