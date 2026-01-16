<?php
namespace App\Controller;

use App\Service\Config;
use App\Model\SystemSetting;
use App\Model\LicenseUser;
use App\Model\LicensePricing;
use App\Model\LicenseRequest;
use App\Service\Mailer;

class LicenseAdminController
{
    private function ensureAdmin(): void
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /public/index.php?route=login');
            exit;
        }
        $role = (string)($_SESSION['user_role'] ?? 'user');
        if ($role !== 'admin') {
            http_response_code(403);
            echo '403 Forbidden';
            exit;
        }
    }

    private function render(string $view, array $params = []): void
    {
        $this->ensureAdmin();
        extract($params);
        $appName = Config::get('app.name');
        $view = 'license_admin/' . $view;
        include __DIR__ . '/../../templates/layout_main.php';
    }

    private function savePaymentQrs(): void
    {
        $uploadDir = Config::get('app.upload_dir', __DIR__ . '/../../uploads');
        $systemDir = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . 'system';
        if (!is_dir($systemDir)) {
            @mkdir($systemDir, 0777, true);
        }

        $map = [
            'wechat_qr' => 'pay_wechat.png',
            'alipay_qr' => 'pay_alipay.png',
            'qq_qr' => 'pay_qq.png',
        ];

        foreach ($map as $field => $filename) {
            if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
                continue;
            }
            $error = (int)($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($error !== UPLOAD_ERR_OK) {
                continue;
            }
            $tmp = (string)($_FILES[$field]['tmp_name'] ?? '');
            if ($tmp === '' || !is_uploaded_file($tmp)) {
                continue;
            }
            $targetPath = $systemDir . DIRECTORY_SEPARATOR . $filename;
            @move_uploaded_file($tmp, $targetPath);
        }
    }

    public function index(): void
    {
        $this->ensureAdmin();

        $tab = $_GET['tab'] ?? 'users';

        // 简单处理几个后台操作：保存价格配置、更新授权用户状态等
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'save_pricing') {
                LicensePricing::saveFromPost($_POST);
                header('Location: /public/index.php?route=license-admin&tab=pricing');
                exit;
            }
            if ($action === 'save_pay_qr') {
                $this->savePaymentQrs();
                header('Location: /public/index.php?route=license-admin&tab=pricing');
                exit;
            }
            if ($action === 'update_user_status') {
                $id = (int)($_POST['id'] ?? 0);
                $status = (string)($_POST['status'] ?? 'normal');
                if ($id > 0) {
                    LicenseUser::updateStatus($id, $status);
                }
                header('Location: /public/index.php?route=license-admin&tab=users');
                exit;
            }
            if ($action === 'update_user') {
                $id = (int)($_POST['id'] ?? 0);
                $email = trim((string)($_POST['email'] ?? ''));
                $domain = trim((string)($_POST['domain'] ?? ''));
                if ($id > 0 && $email !== '' && $domain !== '') {
                    LicenseUser::updateBasic($id, $email, $domain);
                }
                header('Location: /public/index.php?route=license-admin&tab=users');
                exit;
            }
            if ($action === 'stop_user') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    LicenseUser::stopLicense($id);
                }
                header('Location: /public/index.php?route=license-admin&tab=users');
                exit;
            }
            if ($action === 'delete_user') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    LicenseUser::deleteById($id);
                }
                header('Location: /public/index.php?route=license-admin&tab=users');
                exit;
            }
            if ($action === 'change_domain') {
                $id = (int)($_POST['id'] ?? 0);
                $newDomain = trim((string)($_POST['new_domain'] ?? ''));
                if ($id > 0 && $newDomain !== '') {
                    LicenseUser::changeDomain($id, $newDomain);
                }
                header('Location: /public/index.php?route=license-admin&tab=users');
                exit;
            }
            if ($action === 'resend_license') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    $user = LicenseUser::findById($id);
                    if ($user) {
                        $email = (string)($user['email'] ?? '');
                        $domain = (string)($user['domain'] ?? '');
                        $licenseCode = (string)($user['license_code'] ?? '');
                        $periodType = $user['license_period'] ?? null;

                        $periodLabel = '未知';
                        if ($periodType === 'month') {
                            $periodLabel = '按月';
                        } elseif ($periodType === 'year') {
                            $periodLabel = '按年';
                        } elseif ($periodType === 'lifetime') {
                            $periodLabel = '永久';
                        }

                        $subject = '【部署授权信息重发】' . $domain;
                        $lines = [];
                        $lines[] = '您好，以下是您的部署授权信息：';
                        $lines[] = '';
                        $lines[] = '授权邮箱：' . $email;
                        $lines[] = '授权域名：' . $domain;
                        $lines[] = '授权周期：' . $periodLabel;
                        $lines[] = '授权码：' . $licenseCode;
                        $lines[] = '';
                        $lines[] = '请在系统后台「系统参数」中填写授权码保持在线授权，如有疑问可直接回复本邮件联系管理员。';
                        $body = implode("\n", $lines);

                        if ($email !== '' && $licenseCode !== '') {
                            try {
                                Mailer::send($email, '', $subject, $body);
                            } catch (\Throwable $e) {
                                // 忽略邮件异常
                            }
                        }
                    }
                }
                header('Location: /public/index.php?route=license-admin&tab=users');
                exit;
            }
            if ($action === 'generate_license') {
                $requestId = (int)($_POST['request_id'] ?? 0);
                if ($requestId > 0) {
                    LicenseUser::createFromRequest($requestId);
                }
                header('Location: /public/index.php?route=license-admin&tab=requests');
                exit;
            }
            if ($action === 'update_request_note') {
                $id = (int)($_POST['id'] ?? 0);
                $note = trim((string)($_POST['note'] ?? ''));
                if ($id > 0) {
                    LicenseRequest::updateNote($id, $note);
                }
                header('Location: /public/index.php?route=license-admin&tab=requests');
                exit;
            }
            if ($action === 'delete_request') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    LicenseRequest::deleteById($id);
                }
                header('Location: /public/index.php?route=license-admin&tab=requests');
                exit;
            }
        }

        $systemSetting = SystemSetting::get();
        $pricing = LicensePricing::getDefault();

        // 授权用户支持按邮箱/域名搜索
        $userSearch = isset($_GET['user_q']) ? trim((string)$_GET['user_q']) : '';
        if ($userSearch !== '') {
            $users = LicenseUser::search($userSearch, 200);
        } else {
            $users = LicenseUser::listLatest(100);
        }

        // 授权申请：后台展示全部记录，按时间倒序
        $requests = LicenseRequest::listAll();

        $this->render('index', [
            'systemSetting' => $systemSetting,
            'pricing' => $pricing,
            'users' => $users,
            'requests' => $requests,
            'userSearch' => $userSearch,
            'tab' => $tab,
        ]);
    }
}
