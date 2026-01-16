<?php
namespace App\Controller;

use App\Service\Config;
use App\Model\SystemSetting;
use App\Model\LicensePricing;
use App\Model\LicenseRequest;
use App\Model\LicenseUser;
use App\Model\LicenseMessage;
use App\Service\Upload;
use App\Service\Mailer;

class LandingController
{
    private function render(string $view, array $params = []): void
    {
        extract($params);
        $appName = Config::get('app.name');
        include __DIR__ . '/../../templates/layout_public.php';
    }

    public function index(): void
    {
        $systemSetting = SystemSetting::get();
        $appVersion = Config::get('app.version', 'v1.0.0');
        $this->render('landing/index', [
            'systemSetting' => $systemSetting,
            'appVersion' => $appVersion,
        ]);
    }

    public function deployAuth(): void
    {
        $systemSetting = SystemSetting::get();
        $pricing = LicensePricing::getDefault();
        $success = isset($_GET['success']) && $_GET['success'] === '1';
        $errorMessage = isset($_GET['error']) ? (string)$_GET['error'] : '';

        // 支付收款码（由授权后台上传到固定路径）
        $uploadDir = Config::get('app.upload_dir', __DIR__ . '/../../uploads');
        $systemDir = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . 'system';
        $paymentQrs = [
            'wechat' => is_file($systemDir . DIRECTORY_SEPARATOR . 'pay_wechat.png') ? '/uploads/system/pay_wechat.png' : '',
            'alipay' => is_file($systemDir . DIRECTORY_SEPARATOR . 'pay_alipay.png') ? '/uploads/system/pay_alipay.png' : '',
            'qq' => is_file($systemDir . DIRECTORY_SEPARATOR . 'pay_qq.png') ? '/uploads/system/pay_qq.png' : '',
        ];

        $messageSuccess = isset($_GET['msg_success']) && $_GET['msg_success'] === '1';
        $messageError = isset($_GET['msg_error']) ? (string)$_GET['msg_error'] : '';
        $messages = LicenseMessage::listLatest(50);

        $this->render('landing/deploy_auth', [
            'systemSetting' => $systemSetting,
            'pricing' => $pricing,
            'success' => $success,
            'errorMessage' => $errorMessage,
            'paymentQrs' => $paymentQrs,
            'messageSuccess' => $messageSuccess,
            'messageError' => $messageError,
            'messages' => $messages,
        ]);
    }

    public function submitLicenseRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /public/index.php?route=deploy-auth');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $domain = trim($_POST['domain'] ?? '');
        $typeRaw = $_POST['request_type'] ?? '';
        $period = $_POST['period'] ?? null;
        $payMethodRaw = trim((string)($_POST['pay_method'] ?? ''));

        // 付款截图（必填）
        $payProofRelative = null;
        if (!empty($_FILES['pay_proof']) && is_array($_FILES['pay_proof'])) {
            $payProofRelative = Upload::saveAttachment(0, $_FILES['pay_proof']);
        }

        $type = $typeRaw === 'change' ? 'change' : ($typeRaw === 'first' ? 'first' : '');
        if ($type === 'first') {
            $allowedPeriods = ['month', 'year', 'lifetime'];
            if (!in_array($period, $allowedPeriods, true)) {
                $period = null;
            }
        } else {
            $period = null;
        }

        $allowedPayMethods = ['wechat', 'alipay', 'qq'];
        $payMethod = in_array($payMethodRaw, $allowedPayMethods, true) ? $payMethodRaw : '';

        $error = '';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = '请填写有效的邮箱地址';
        } elseif ($domain === '') {
            $error = '请填写授权域名';
        } elseif ($type === '') {
            $error = '请选择申请类型';
        } elseif ($type === 'first' && $period === null) {
            $error = '请选择授权周期';
        } elseif ($payMethod === '') {
            $error = '请选择支付方式';
        } elseif ($payProofRelative === null || $payProofRelative === '') {
            $error = '请上传付款成功截图';
        }

        if ($error !== '') {
            header('Location: /public/index.php?route=deploy-auth&error=' . urlencode($error));
            exit;
        }

        // 记录授权需求（如数据库异常，则给出友好提示而不是 500）
        try {
            LicenseRequest::create($email, $domain, $type, $period, $payProofRelative);
        } catch (\Throwable $e) {
            $fallbackError = '系统在保存授权申请时出现异常，请稍后重试，或直接发送邮件至 9041708@qq.com 进行人工处理。';
            header('Location: /public/index.php?route=deploy-auth&error=' . urlencode($fallbackError));
            exit;
        }

        // 发送通知邮件给管理邮箱
        $system = SystemSetting::get();
        $adminEmail = (string)($system['license_admin_email'] ?? '');
        if ($adminEmail === '') {
            $adminEmail = '9041708@qq.com';
        }

        $typeLabel = $type === 'first' ? '首次授权' : '更换授权';
        $periodLabel = '（更换授权无周期）';
        if ($type === 'first') {
            if ($period === 'month') {
                $periodLabel = '按月';
            } elseif ($period === 'year') {
                $periodLabel = '按年';
            } elseif ($period === 'lifetime') {
                $periodLabel = '永久';
            } else {
                $periodLabel = '未选择';
            }
        }

        $payLabel = '未选择';
        if ($payMethod === 'wechat') {
            $payLabel = '微信';
        } elseif ($payMethod === 'alipay') {
            $payLabel = '支付宝';
        } elseif ($payMethod === 'qq') {
            $payLabel = 'QQ';
        }

        $subject = '【部署授权申请】' . $domain . ' - ' . $typeLabel;

        $applyTime = date('Y-m-d H:i:s');

        // 生成付款截图完整链接（如配置了 site_url）
        $siteUrl = rtrim(Config::get('app.site_url', ''), '/');
        $payProofUrlFull = '';
        if ($payProofRelative) {
            $relativePath = '/uploads/' . ltrim($payProofRelative, '/\\');
            $payProofUrlFull = $siteUrl !== '' ? ($siteUrl . $relativePath) : $relativePath;
        }

        // 使用纯文本邮件内容，避免 HTML 标签在部分客户端显示为代码
        $lines = [];
        $lines[] = '收到一条新的部署授权申请，请尽快处理：';
        $lines[] = '';
        $lines[] = '申请时间：' . $applyTime;
        $lines[] = '联系邮箱：' . $email;
        $lines[] = '授权域名：' . $domain;
        $lines[] = '申请类型：' . $typeLabel;
        $lines[] = '授权周期：' . $periodLabel;
        $lines[] = '支付方式：' . $payLabel;
        if ($payProofUrlFull !== '') {
            $lines[] = '付款截图：' . $payProofUrlFull;
        } else {
            $lines[] = '付款截图：未上传';
        }
        $lines[] = '';
        $lines[] = '可在授权后台「授权管理 > 授权申请」中查看并处理本次申请，如有疑问可直接回复本邮件与申请人联系。';

        $bodyText = implode("\n", $lines);

        // 尝试发送但不因失败中断主流程
        try {
            Mailer::send($adminEmail, '', $subject, $bodyText);
        } catch (\Throwable $e) {
            // 忽略邮件异常
        }

        header('Location: /public/index.php?route=deploy-auth&success=1');
        exit;
    }

    public function submitLicenseMessage(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /public/index.php?route=deploy-auth#message-board');
            exit;
        }

        $email = trim($_POST['msg_email'] ?? '');
        $nickname = trim($_POST['msg_nickname'] ?? '');
        $content = trim($_POST['msg_content'] ?? '');

        $error = '';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = '请填写有效的邮箱地址';
        } elseif ($nickname === '') {
            $error = '请填写昵称';
        } elseif ($content === '') {
            $error = '请填写留言内容';
        }

        if ($error !== '') {
            header('Location: /public/index.php?route=deploy-auth&msg_error=' . urlencode($error) . '#message-board');
            exit;
        }

        LicenseMessage::create($email, $nickname, $content);

        // 邮件通知管理员
        $system = SystemSetting::get();
        $adminEmail = (string)($system['license_admin_email'] ?? '');
        if ($adminEmail === '') {
            $adminEmail = '9041708@qq.com';
        }

        $subject = '新的部署授权留言';
        $html = '<p>您有新的部署授权留言：</p>' .
            '<ul>' .
            '<li>邮箱：' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</li>' .
            '<li>昵称：' . htmlspecialchars($nickname, ENT_QUOTES, 'UTF-8') . '</li>' .
            '</ul>' .
            '<p>留言内容：</p><p>' . nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8')) . '</p>';

        try {
            Mailer::send($adminEmail, '', $subject, $html);
        } catch (\Throwable $e) {
            // 忽略邮件异常
        }

        header('Location: /public/index.php?route=deploy-auth&msg_success=1#message-board');
        exit;
    }

    public function downloadSource(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /public/index.php?route=deploy-auth');
            exit;
        }

        $email = trim($_POST['download_email'] ?? '');
        $licenseCode = trim($_POST['download_license_code'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = '请填写有效的邮箱地址后再下载部署包';
        } elseif ($licenseCode === '') {
            $error = '请填写授权码后再下载部署包';
        } else {
            $license = LicenseUser::findByEmailAndCode($email, $licenseCode);
            if (!$license) {
                $error = '邮箱与授权码不匹配，未找到授权记录';
            } else {
                $system = SystemSetting::get();
                $downloadUrl = (string)($system['license_source_path'] ?? '');
                if ($downloadUrl === '') {
                    $error = '部署包下载地址暂未配置，请联系管理员';
                } else {
                    header('Location: ' . $downloadUrl);
                    exit;
                }
            }
        }

        header('Location: /public/index.php?route=deploy-auth&error=' . urlencode($error));
        exit;
    }

    public function queryLicense(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => '不支持的请求方式']);
            return;
        }

        $email = trim($_POST['email'] ?? '');
        $domain = trim($_POST['domain'] ?? '');

        header('Content-Type: application/json; charset=utf-8');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['ok' => false, 'message' => '请填写有效的邮箱地址']);
            return;
        }
        if ($domain === '') {
            echo json_encode(['ok' => false, 'message' => '请填写授权域名']);
            return;
        }

        $license = LicenseUser::findByEmailAndDomain($email, $domain);
        if (!$license) {
            echo json_encode(['ok' => false, 'message' => '未找到对应的授权记录，请确认邮箱和域名是否填写正确']);
            return;
        }

        $licenseCode = (string)($license['license_code'] ?? '');
        $statusRaw = (string)($license['license_status'] ?? ($license['status'] ?? ''));
        $periodType = $license['period_type'] ?? ($license['license_period'] ?? null);
        $expireAt = $license['license_expire_at'] ?? null;

        $statusLabel = '未知';
        switch ($statusRaw) {
            case 'unused':
                $statusLabel = '未使用';
                break;
            case 'normal':
                $statusLabel = '正常';
                break;
            case 'expired':
                $statusLabel = '已过期';
                break;
        }

        $periodLabel = '未知';
        if ($periodType === 'month') {
            $periodLabel = '按月';
        } elseif ($periodType === 'year') {
            $periodLabel = '按年';
        } elseif ($periodType === 'lifetime') {
            $periodLabel = '永久';
        }

        echo json_encode([
            'ok' => true,
            'data' => [
                'license_code' => $licenseCode,
                'status' => $statusLabel,
                'period' => $periodLabel,
                'expire_at' => $expireAt,
            ],
        ]);
    }
}
