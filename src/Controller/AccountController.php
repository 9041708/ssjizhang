<?php
namespace App\Controller;

use App\Service\Config;
use App\Service\Database;
use App\Model\Account;
use App\Model\IconLibrary;
use App\Service\Upload;
use PDO;

class AccountController
{
    private function requireLogin(): int
    {
        $uid = (int)($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) {
            header('Location: /public/index.php?route=login');
            exit;
        }
        return $uid;
    }

    private function render(string $view, array $params = []): void
    {
        extract($params);
        $appName = Config::get('app.name');
        include __DIR__ . '/../../templates/layout_main.php';
    }

    public function index(): void
    {
        $userId = $this->requireLogin();
        $pdo = Database::getConnection();
        $groups = $pdo->query('SELECT * FROM account_groups ORDER BY id')->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'create') {
                $groupId = (int)($_POST['group_id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $accountNo = trim($_POST['account_no'] ?? '');
                $initial = (float)($_POST['initial_balance'] ?? 0);
                $iconType = null;
                $iconValue = null;

                $iconMode = $_POST['icon_mode'] ?? 'none';
                if ($iconMode === 'file' && !empty($_FILES['icon_file'])) {
                    $savedPath = Upload::saveAttachment($userId, $_FILES['icon_file']);
                    if ($savedPath) {
                        $iconType = 'file';
                        $iconValue = $savedPath;
                        IconLibrary::ensureExists($userId, $savedPath, $name ?: '账户图标');
                    }
                } elseif ($iconMode === 'library') {
                    $libId = (int)($_POST['icon_library_id'] ?? 0);
                    if ($libId > 0) {
                        $icon = IconLibrary::findByUser($userId, $libId);
                        if ($icon) {
                            $iconType = 'file';
                            $iconValue = $icon['file_path'] ?? null;
                        }
                    }
                }
                if ($groupId <= 0 || $name === '') {
                    $error = '请选择账户大类并填写账户名称';
                } else {
                    Account::create($userId, $groupId, $name, $accountNo ?: null, $initial, $iconType, $iconValue);
                    $success = '新增账户成功';
                }
            } elseif ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $groupId = (int)($_POST['group_id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $accountNo = trim($_POST['account_no'] ?? '');
                $iconType = null;
                $iconValue = null;

                $iconMode = $_POST['icon_mode'] ?? 'none';
                $current = Account::findByUser($userId, $id);

                if ($iconMode === 'file' && !empty($_FILES['icon_file'])) {
                    $savedPath = Upload::saveAttachment($userId, $_FILES['icon_file']);
                    if ($savedPath) {
                        $iconType = 'file';
                        $iconValue = $savedPath;
                        IconLibrary::ensureExists($userId, $savedPath, $name ?: '账户图标');
                    } elseif ($current) {
                        $iconType = $current['icon_type'] ?? null;
                        $iconValue = $current['icon_value'] ?? null;
                    }
                } elseif ($iconMode === 'library') {
                    $libId = (int)($_POST['icon_library_id'] ?? 0);
                    if ($libId > 0) {
                        $icon = IconLibrary::findByUser($userId, $libId);
                        if ($icon) {
                            $iconType = 'file';
                            $iconValue = $icon['file_path'] ?? null;
                        }
                    }
                    if (!$iconType && $current) {
                        $iconType = $current['icon_type'] ?? null;
                        $iconValue = $current['icon_value'] ?? null;
                    }
                } elseif ($iconMode === 'clear') {
                    $iconType = null;
                    $iconValue = null;
                } else { // none: 保持不变
                    if ($current) {
                        $iconType = $current['icon_type'] ?? null;
                        $iconValue = $current['icon_value'] ?? null;
                    }
                }
                if ($groupId <= 0 || $name === '') {
                    $error = '请选择账户大类并填写账户名称';
                } else {
                    Account::update($userId, $id, $groupId, $name, $accountNo ?: null, $iconType, $iconValue);
                    $success = '更新账户成功';
                }
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if (!Account::delete($userId, $id)) {
                    $error = '该账户已有记账数据，无法删除';
                } else {
                    $success = '删除账户成功';
                }
            }
        }

        $accounts = Account::allByUser($userId);
        $iconLibrary = IconLibrary::allByUser($userId);
        $this->render('accounts/index', compact('accounts', 'groups', 'iconLibrary', 'error', 'success'));
    }
}
