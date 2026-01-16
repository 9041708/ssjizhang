<?php
namespace App\Controller;

use App\Service\Config;
use App\Model\IconLibrary;
use App\Model\Category;
use App\Model\Item;
use App\Model\Account;
use App\Service\Upload;

class IconController
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
        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'create') {
                $name = trim($_POST['name'] ?? '');
                if ($name === '') {
                    $error = '图标名称不能为空';
                } else {
                    $savedPath = !empty($_FILES['icon_file']) ? Upload::saveAttachment($userId, $_FILES['icon_file']) : null;
                    if (!$savedPath) {
                        $error = '图标上传失败，请选择有效的图片文件（小于 10MB）。';
                    } else {
                        IconLibrary::create($userId, $name, $savedPath);
                        $success = '新增图标成功';
                    }
                }
            } elseif ($action === 'init_from_existing') {
                $processed = 0;

                // 从分类中导入
                $categories = Category::allByUser($userId);
                foreach ($categories as $c) {
                    $iconType = $c['icon_type'] ?? null;
                    $iconValue = trim((string)($c['icon_value'] ?? ''));
                    if ($iconType === 'file' && $iconValue !== '') {
                        IconLibrary::ensureExists($userId, $iconValue, '分类-' . ($c['name'] ?? '未命名'));
                        $processed++;
                    }
                }

                // 从项目中导入
                $items = Item::allByUser($userId);
                foreach ($items as $i) {
                    $iconType = $i['icon_type'] ?? null;
                    $iconValue = trim((string)($i['icon_value'] ?? ''));
                    if ($iconType === 'file' && $iconValue !== '') {
                        IconLibrary::ensureExists($userId, $iconValue, '项目-' . ($i['name'] ?? '未命名'));
                        $processed++;
                    }
                }

                // 从账户中导入
                $accounts = Account::allByUser($userId);
                foreach ($accounts as $a) {
                    $iconType = $a['icon_type'] ?? null;
                    $iconValue = trim((string)($a['icon_value'] ?? ''));
                    if ($iconType === 'file' && $iconValue !== '') {
                        $defaultName = '账户-' . ($a['group_name'] ?? '') . '-' . ($a['name'] ?? '未命名');
                        IconLibrary::ensureExists($userId, $iconValue, $defaultName);
                        $processed++;
                    }
                }

                if ($processed === 0) {
                    $success = '没有在现有分类/项目/账户中找到已上传的文件图标，无需导入。';
                } else {
                    $success = '已扫描现有分类/项目/账户中的文件图标，并尝试写入图标库，共处理 ' . $processed . ' 条记录（已存在路径会自动跳过）。';
                }
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    $error = '参数错误，未指定要删除的图标。';
                } else {
                    if (IconLibrary::delete($userId, $id)) {
                        $success = '已从图标库中删除该图标记录（不会删除实际文件）。';
                    } else {
                        $error = '删除失败，该图标可能不存在或不属于当前用户。';
                    }
                }
            }
        }

        $icons = IconLibrary::allByUser($userId);
        $this->render('icons/index', compact('icons', 'error', 'success'));
    }
}
