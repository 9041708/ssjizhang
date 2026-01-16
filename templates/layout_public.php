<?php
use App\Service\Config;
use App\Model\SystemSetting;

$systemSetting = $systemSetting ?? SystemSetting::get();
$appVersion = $appVersion ?? Config::get('app.version', 'v1.0.0');
$cssPath = __DIR__ . '/../assets/css/app.css';
$cssVersion = is_file($cssPath) ? (string)filemtime($cssPath) : $appVersion;
$appName = $appName ?? Config::get('app.name', '收支记账');

$miniappEnabled = (bool)Config::get('wechat.enable_miniapp', true);

$route = $_GET['route'] ?? '';
$siteIconSvg = $systemSetting['site_icon_svg'] ?? null;

?><!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d6efd">
    <title><?php echo htmlspecialchars($appName, ENT_QUOTES, 'UTF-8'); ?></title>
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
    <link href="/assets/css/app.css?v=<?= htmlspecialchars($cssVersion, ENT_QUOTES, 'UTF-8') ?>" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="/public/index.php?route=landing">
            <span class="fw-bold"><?php echo htmlspecialchars($appName, ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($route === '' || $route === 'landing') ? 'active' : ''; ?>" href="/public/index.php?route=landing">首页</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($route === 'login') ? 'active' : ''; ?>" href="/public/index.php?route=login">普通登录</a>
                </li>
                <?php if ($miniappEnabled): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($route === 'qr-login') ? 'active' : ''; ?>" href="/public/index.php?route=qr-login">小程序登录</a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($route === 'deploy-auth') ? 'active' : ''; ?>" href="/public/index.php?route=deploy-auth">部署授权</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="flex-grow-1">
    <div class="container py-5">
        <?php
        $viewFile = __DIR__ . '/' . $view . '.php';
        if (is_file($viewFile)) {
            include $viewFile;
        } else {
            echo '<p class="text-muted">页面施工中…</p>';
        }
        ?>
    </div>
</main>

<footer class="bg-white border-top py-3 mt-auto">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center small text-muted">
        <div>
            <?php
            $icp = $systemSetting['site_icp'] ?? '';
            if ($icp !== '') {
                echo '备案号：' . htmlspecialchars($icp, ENT_QUOTES, 'UTF-8');
            }
            ?>
        </div>
        <div class="mt-2 mt-md-0">
            版本：<?php echo htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div class="mt-2 mt-md-0">
            <?php
            $copyright = $systemSetting['site_copyright'] ?? '';
            if ($copyright !== '') {
                echo htmlspecialchars($copyright, ENT_QUOTES, 'UTF-8');
            } else {
                echo '&copy; ' . date('Y') . ' ' . htmlspecialchars($appName, ENT_QUOTES, 'UTF-8');
            }
            ?>
        </div>
    </div>

</footer>

<?php $bootstrapJsLocal = __DIR__ . '/../assets/vendor/bootstrap/bootstrap.bundle.min.js'; ?>
<?php if (is_file($bootstrapJsLocal)): ?>
<script src="/assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<?php else: ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php endif; ?>
</body>
</html>
