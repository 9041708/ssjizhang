<?php
/** @var string $appName */
use App\Service\Config;
use App\Model\SystemSetting;

$appVersion = Config::get('app.version', 'v1.0.0');
$cssPath = __DIR__ . '/../assets/css/app.css';
$cssVersion = is_file($cssPath) ? (string)filemtime($cssPath) : $appVersion;
$systemSetting = SystemSetting::get();
$siteIconSvg = $systemSetting['site_icon_svg'] ?? null;
$miniappEnabled = (bool)Config::get('wechat.enable_miniapp', true);
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d6efd">
    <title><?= htmlspecialchars($appName) ?> - 控制台</title>
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
    <?php $choicesCssLocal = __DIR__ . '/../assets/vendor/choices/choices.min.css'; if (is_file($choicesCssLocal)): ?>
        <link rel="stylesheet" href="/assets/vendor/choices/choices.min.css">
    <?php else: ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <?php endif; ?>
	<link href="/assets/css/app.css?v=<?= htmlspecialchars($cssVersion) ?>" rel="stylesheet">
</head>
<?php
$themeMode = $_SESSION['theme_mode'] ?? 'light';
$themeClass = $themeMode === 'dark' ? 'theme-dark' : 'theme-light';
$themeIcon = $themeMode === 'dark' ? '☾' : '☀';
$themeTitle = $themeMode === 'dark' ? '切换为白天模式' : '切换为夜间模式';
?>
<body class="<?= $themeClass ?>">
<div class="d-flex" style="min-height:100vh;">
    <!-- 左侧侧边栏菜单 -->
    <nav class="sidebar flex-shrink-0 bg-dark text-white d-flex flex-column">
        <div class="sidebar-header px-3 py-3 border-bottom border-secondary">
            <div class="fw-semibold small text-uppercase text-muted">SanS 三石记账</div>
            <div class="fw-bold">系统菜单</div>
        </div>
        <div class="list-group list-group-flush mt-2">
            <a href="/public/index.php" class="list-group-item list-group-item-action bg-dark text-white border-0">首页</a>
            <a href="/public/index.php?route=transaction-create" class="list-group-item list-group-item-action bg-dark text-white border-0">记账</a>
            <a href="/public/index.php?route=transactions" class="list-group-item list-group-item-action bg-dark text-white border-0">明细</a>
            <a href="/public/index.php?route=reports" class="list-group-item list-group-item-action bg-dark text-white border-0">统计报表</a>
            <a href="/public/index.php?route=categories" class="list-group-item list-group-item-action bg-dark text-white border-0">分类管理</a>
            <a href="/public/index.php?route=items" class="list-group-item list-group-item-action bg-dark text-white border-0">项目管理</a>
            <a href="/public/index.php?route=accounts" class="list-group-item list-group-item-action bg-dark text-white border-0">账户管理</a>
            <a href="/public/index.php?route=icons" class="list-group-item list-group-item-action bg-dark text-white border-0">图标库</a>
            <a href="/public/index.php?route=budget" class="list-group-item list-group-item-action bg-dark text-white border-0">预算管理</a>
            <a href="/public/index.php?route=feedback" class="list-group-item list-group-item-action bg-dark text-white border-0">问题反馈 / FAQ</a>
            <a href="/public/index.php?route=settings" class="list-group-item list-group-item-action bg-dark text-white border-0">系统设置</a>
            <a href="/public/index.php?route=changelog" class="list-group-item list-group-item-action bg-dark text-white border-0">更新日志</a>
            <?php
            $sidebarRole = $_SESSION['user_role'] ?? 'user';
            if ($sidebarRole === 'admin' && \App\Service\Config::get('app.license_admin_enabled', false)):
            ?>
                <a href="/public/index.php?route=license-admin" class="list-group-item list-group-item-action bg-dark text-white border-0">授权管理</a>
            <?php endif; ?>
        </div>
        <div class="mt-auto px-3 py-3 border-top border-secondary small text-white">
            <div>版本 <?= htmlspecialchars($appVersion) ?></div>
            <div>&copy; 2025-<?= date('Y') ?> SanS 三石</div>
        </div>
    </nav>

    <!-- 移动端侧边栏遮罩层 -->
    <div class="sidebar-overlay d-md-none" id="sidebarOverlay"></div>

    <!-- 右侧主内容区域 -->
    <div class="flex-grow-1 d-flex flex-column">
        <header class="navbar navbar-light bg-white shadow-sm px-3">
            <div class="container-fluid">
				<div class="d-flex align-items-center">
					<button class="btn btn-outline-secondary btn-sm d-md-none me-2" id="sidebarToggle" type="button">
						☰
					</button>
					<span class="navbar-brand mb-0 h6 mb-0"><?= htmlspecialchars($appName) ?></span>
				</div>
	    		<div class="d-flex align-items-center">
                        <button
                            type="button"
                            id="themeToggleBtn"
                            class="btn btn-outline-secondary btn-sm me-2 btn-theme-toggle"
                            title="<?= htmlspecialchars($themeTitle) ?>"
                            aria-label="<?= htmlspecialchars($themeTitle) ?>"
                        >
                            <?= htmlspecialchars($themeIcon) ?>
                        </button>
                        <?php
                        $sessionNickname = $_SESSION['user_nickname'] ?? '';
                        $sessionAvatar = $_SESSION['user_avatar'] ?? null;
                        ?>
                        <div class="d-flex align-items-center me-3">
                            <?php if (!empty($sessionAvatar)): ?>
                                <img src="<?= htmlspecialchars($sessionAvatar) ?>" alt="头像" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width:32px;height:32px;font-size:0.75rem;">👤</div>
                            <?php endif; ?>
                            <span class="small text-muted text-nowrap"><?= htmlspecialchars($sessionNickname) ?></span>
                        </div>
                        <?php if ($miniappEnabled): ?>
                        <button
                            type="button"
                            id="miniAppBtn"
                            class="btn btn-outline-primary btn-sm me-2"
                            data-bs-toggle="modal"
                            data-bs-target="#miniAppModal"
                        >使用小程序</button>
                        <?php endif; ?>
                        <a class="btn btn-outline-secondary btn-sm" href="/public/index.php?route=logout">退出</a>
                    </div>
            </div>
        </header>
        <main class="flex-grow-1 py-4 px-3 px-md-4">
            <div class="container-fluid">
                <?php include __DIR__ . '/' . $view . '.php'; ?>
            </div>
        </main>
    </div>
</div>

<!-- 全局凭证大图预览弹窗 -->
<div class="modal fade" id="attachmentPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-body p-0 text-center">
                <img src="" alt="凭证预览" id="attachmentPreviewImage" class="img-fluid attachment-modal-img">
            </div>
        </div>
    </div>
</div>

<?php if ($miniappEnabled): ?>
<!-- 小程序扫码弹窗 -->
<div class="modal fade" id="miniAppModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">微信小程序扫码</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img src="/xiaochengxu.png" alt="小程序码" class="img-fluid" style="max-width:100%;height:auto;">
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php if (!empty($latestAnnouncement) && is_array($latestAnnouncement)): ?>
<!-- PC 首页公告弹窗 -->
<div class="modal fade" id="pcAnnouncementModal" tabindex="-1" aria-hidden="true" data-announcement-id="<?= (int)($latestAnnouncement['id'] ?? 0) ?>">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">系统公告</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" data-announcement-close="1"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-semibold mb-2"><?= htmlspecialchars($latestAnnouncement['title'] ?? '') ?></h6>
                <div class="small text-muted mb-2"><?= htmlspecialchars($latestAnnouncement['scheduled_at'] ?? '') ?></div>
                <div class="announcement-content small" style="white-space:pre-wrap;">
                    <?= nl2br(htmlspecialchars($latestAnnouncement['content'] ?? '')) ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-primary" data-bs-dismiss="modal" data-announcement-close="1">知道了</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php $bootstrapJsLocal = __DIR__ . '/../assets/vendor/bootstrap/bootstrap.bundle.min.js'; ?>
<?php if (is_file($bootstrapJsLocal)): ?>
<script src="/assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<?php else: ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php endif; ?>
<?php $choicesJsLocal = __DIR__ . '/../assets/vendor/choices/choices.min.js'; if (is_file($choicesJsLocal)): ?>
<script src="/assets/vendor/choices/choices.min.js"></script>
<?php else: ?>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<?php endif; ?>
<script>
// 简单用户名推荐：在当前用户名后追加 3 位随机数字
function suggestUsername() {
    var input = document.querySelector('#modalUsernameChange input[name="new_username"]');
    if (!input) return;
    var base = input.value.trim();
    if (!base) {
        base = 'user';
    }
    var suffix = Math.floor(100 + Math.random() * 900); // 100-999
    input.value = base.replace(/[^a-zA-Z0-9_]/g, '') + '_' + suffix;
}

// 凭证图片点击放大预览（取消悬浮预览）
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('attachmentPreviewModal');
    var modalImg = document.getElementById('attachmentPreviewImage');

    function bindAttachmentPreview(root) {
        var triggers = (root || document).querySelectorAll('[data-attachment-preview]');
        triggers.forEach(function (el) {
            var url = el.getAttribute('data-attachment-preview');
            if (!url) return;
            el.addEventListener('click', function (e) {
                e.preventDefault();
                if (!modalEl || !modalImg || !window.bootstrap) return;
                modalImg.src = url;
                var modal = new bootstrap.Modal(modalEl);
                modal.show();
            });
        });
    }

    bindAttachmentPreview(document);

    // 移动端侧边栏收起/展开
    var sidebarToggle = document.getElementById('sidebarToggle');
    var sidebarOverlay = document.getElementById('sidebarOverlay');

    function closeSidebar() {
        document.body.classList.remove('sidebar-open');
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            document.body.classList.toggle('sidebar-open');
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }

    var sidebarLinks = document.querySelectorAll('.sidebar .list-group-item');
    sidebarLinks.forEach(function (link) {
        link.addEventListener('click', closeSidebar);
    });

    // 日/夜模式一键切换
    var themeBtn = document.getElementById('themeToggleBtn');
    if (themeBtn) {
        themeBtn.addEventListener('click', function () {
            var current = document.body.classList.contains('theme-dark') ? 'dark' : 'light';
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '/public/index.php?route=theme-toggle', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var data = JSON.parse(xhr.responseText || '{}');
                            var mode = data.mode === 'dark' ? 'dark' : 'light';
                            document.body.classList.remove('theme-light', 'theme-dark');
                            document.body.classList.add(mode === 'dark' ? 'theme-dark' : 'theme-light');

                            if (mode === 'dark') {
                                themeBtn.textContent = '☾';
                                themeBtn.title = '切换为白天模式';
                                themeBtn.setAttribute('aria-label', '切换为白天模式');
                            } else {
                                themeBtn.textContent = '☀';
                                themeBtn.title = '切换为夜间模式';
                                themeBtn.setAttribute('aria-label', '切换为夜间模式');
                            }
                        } catch (e) {
                            // 如果解析失败，则回退为整页刷新
                            window.location.href = '/public/index.php?route=theme-toggle';
                        }
                    } else {
                        // 请求失败也回退为整页刷新
                        window.location.href = '/public/index.php?route=theme-toggle';
                    }
                }
            };
            xhr.send('current=' + encodeURIComponent(current));
        });
    }

    // 兜底：如果 data-bs-* 未生效，手动触发
    var miniBtn = document.getElementById('miniAppBtn');
    if (miniBtn && window.bootstrap) {
        miniBtn.addEventListener('click', function (e) {
            // 若已由 data-bs-* 处理则不重复
            if (e.defaultPrevented) return;
            var modalEl = document.getElementById('miniAppModal');
            if (!modalEl) return;
            var m = bootstrap.Modal.getOrCreateInstance(modalEl);
            m.show();
        }, { once: false });
    }

    // PC 首页系统公告弹窗
    try {
        var annModal = document.getElementById('pcAnnouncementModal');
        if (annModal && window.bootstrap) {
            var annId = annModal.getAttribute('data-announcement-id');
            var hasId = annId && parseInt(annId, 10) > 0;
            var markReadOnce = false;

            function markAnnouncementRead() {
                if (!hasId || markReadOnce) return;
                markReadOnce = true;
                try {
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', '/public/index.php?route=announcement-mark-read', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                    xhr.send('id=' + encodeURIComponent(String(annId)));
                } catch (e) {}
            }

            var modal = bootstrap.Modal.getOrCreateInstance(annModal);
            modal.show();

            var closeBtns = annModal.querySelectorAll('[data-announcement-close]');
            closeBtns.forEach(function (btn) {
                btn.addEventListener('click', markAnnouncementRead);
            });
            annModal.addEventListener('hidden.bs.modal', markAnnouncementRead);
        }
    } catch (e) {}

    // 兜底：清理可能残留的 Bootstrap 模态遮罩/锁定状态（避免整页无法点击）
    window.setTimeout(function () {
        try {
            var anyShownModal = document.querySelector('.modal.show');
            if (!anyShownModal) {
                var backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(function (b) {
                    if (b && b.parentNode) b.parentNode.removeChild(b);
                });
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
                document.body.style.removeProperty('overflow');
            }
        } catch (e) {}
    }, 800);

    // 注册 Service Worker，用于 PWA 支持
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/public/sw.js').catch(function (err) {
            console.warn('ServiceWorker 注册失败:', err);
        });
    }
});
</script>
</body>
</html>
