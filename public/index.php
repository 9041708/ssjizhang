<?php
// Front controller
require __DIR__ . '/../src/bootstrap.php';

use App\Service\Config;
use App\Controller\AuthController;
use App\Controller\DashboardController;
use App\Controller\TransactionController;
use App\Controller\ReportController;
use App\Controller\CategoryController;
use App\Controller\ItemController;
use App\Controller\AccountController;
use App\Controller\SettingsController;
use App\Controller\ChangelogController;
use App\Controller\BudgetController;
use App\Controller\ThemeController;
use App\Controller\IconController;
use App\Controller\FeedbackController;
use App\Controller\LandingController;
use App\Controller\LicenseAdminController;
use App\Model\AnnouncementRead;
use App\Model\SystemSetting;
use App\Model\User;

// 使用显式的 route 参数，例如 index.php?route=login
// 如果未提供 route，则从 URL 中取最后一段作为兼容处理
$route = isset($_GET['route']) ? trim((string)$_GET['route'], '/') : '';
if ($route === '') {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
    $uri = trim($uri, '/');
    if ($uri !== '' && strpos($uri, '/') !== false) {
        $uri = basename($uri);
    }
    $route = $uri;
}

// 为记账系统使用独立的 Session 名称，避免与同域名下其他系统（如协作平台）共享登录状态
if (session_status() === PHP_SESSION_NONE) {
    session_name('SSJIZHANGSESSID');
    session_start();
}

// 为所有业务页面显式禁止浏览器/中间层缓存，避免首页等页面出现旧余额或过期登录状态
if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

// 未操作自动退出，超时时间从系统设置 session_timeout_hours 读取（单位：小时，1~168，默认 24）
if (isset($_SESSION['user_id'])) {
    $now = time();
    $system = SystemSetting::get();
    $hours = (int)($system['session_timeout_hours'] ?? 24);
    if ($hours < 1 || $hours > 168) {
        $hours = 24; // 防御性兜底
    }
    $timeout = $hours * 60 * 60;
    $last = (int)($_SESSION['last_activity'] ?? $now);
    if ($last > 0 && ($now - $last) > $timeout) {
        // 超过 24 小时未操作，清理会话并返回登录页
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        header('Location: index.php?route=login&expired=1');
        exit;
    }
    $_SESSION['last_activity'] = $now;

    // 同步用户资料到会话（尤其是头像），避免小程序更新后 PC 端仍显示旧数据
    try {
        $u = User::findById((int)$_SESSION['user_id']);
        if ($u) {
            $_SESSION['user_nickname'] = (string)($u['nickname'] ?? ($_SESSION['user_nickname'] ?? ''));
            $_SESSION['user_role'] = (string)($u['role'] ?? ($_SESSION['user_role'] ?? 'user'));
            $_SESSION['theme_mode'] = (string)($u['theme_mode'] ?? ($_SESSION['theme_mode'] ?? 'light'));
            if (!empty($u['avatar_path'])) {
                $_SESSION['user_avatar'] = '/uploads/' . ltrim((string)$u['avatar_path'], '/\\');
            } else {
                $_SESSION['user_avatar'] = null;
            }
        }
    } catch (\Throwable $e) {
        // 忽略同步异常，避免影响主流程
    }
}

function redirect(string $route): void {
    // 不使用伪静态，统一跳转到 index.php?route=xxx
    $target = 'index.php';
    $route = trim($route, '/');
    if ($route !== '') {
        $target .= '?route=' . urlencode($route);
    }
    header('Location: ' . $target);
    exit;
}

// Simple router
switch ($route) {
    case '':
    case 'index.php':
        if (!isset($_SESSION['user_id'])) {
            $landingEnabled = (bool)Config::get('app.landing_enabled', true);
            if ($landingEnabled) {
                (new LandingController())->index();
            } else {
                (new AuthController())->login();
            }
            break;
        }
        (new DashboardController())->index();
        break;
    case 'login':
        (new AuthController())->login();
        break;
    case 'logout':
        (new AuthController())->logout();
        break;
    case 'register':
        (new AuthController())->register();
        break;
    case 'register-bind':
        (new AuthController())->registerBind();
        break;
    case 'qr-login':
        (new AuthController())->qrLogin();
        break;
    case 'qr-login-complete':
        (new AuthController())->qrLoginComplete();
        break;
    case 'onboarding':
        (new AuthController())->onboarding();
        break;
    case 'verify-email':
        (new AuthController())->verifyEmail();
        break;
    case 'forgot-password':
        (new AuthController())->forgotPassword();
        break;
    case 'reset-password':
        (new AuthController())->resetPassword();
        break;
    case 'transactions':
        (new TransactionController())->index();
        break;
    case 'transaction-create':
        (new TransactionController())->create();
        break;
    case 'transaction-edit':
        (new TransactionController())->edit();
        break;
    case 'transaction-delete':
        (new TransactionController())->delete();
        break;
    case 'transactions-export':
        (new TransactionController())->export();
        break;
    case 'reports':
        (new ReportController())->index();
        break;
    case 'categories':
        (new CategoryController())->index();
        break;
    case 'items':
        (new ItemController())->index();
        break;
    case 'accounts':
        (new AccountController())->index();
        break;
    case 'icons':
        (new IconController())->index();
        break;
    case 'feedback':
        (new FeedbackController())->index();
        break;
    case 'license-admin':
        // 授权管理后台仅在主站或明确开启时可用，打包给最终用户默认关闭
        if (Config::get('app.license_admin_enabled', false)) {
            (new LicenseAdminController())->index();
        } else {
            http_response_code(404);
            echo '404 Not Found';
        }
        break;
    case 'settings':
        (new SettingsController())->index();
        break;
    case 'budget':
        (new BudgetController())->index();
        break;
    case 'changelog':
        (new ChangelogController())->index();
        break;
    case 'landing':
        (new LandingController())->index();
        break;
    case 'deploy-auth':
        (new LandingController())->deployAuth();
        break;
    case 'license-request-submit':
        (new LandingController())->submitLicenseRequest();
        break;
    case 'source-download':
        (new LandingController())->downloadSource();
        break;
    case 'license-query':
        (new LandingController())->queryLicense();
        break;
    case 'license-message-submit':
        (new LandingController())->submitLicenseMessage();
        break;
    case 'theme-toggle':
        (new ThemeController())->toggle();
        break;
    case 'announcement-mark-read':
        // PC 端首页公告关闭后标记为已读
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => '未登录']);
            exit;
        }
        $aid = (int)($_POST['id'] ?? 0);
        if ($aid > 0) {
            try {
                AnnouncementRead::markRead($aid, (int)$_SESSION['user_id'], 'pc');
            } catch (\Throwable $e) {
                // 忽略统计错误
            }
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true]);
        exit;
    default:
        http_response_code(404);
        echo '404 Not Found';
}
