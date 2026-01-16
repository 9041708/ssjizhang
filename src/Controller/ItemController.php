<?php
namespace App\Controller;

use App\Service\Config;
use App\Model\Item;
use App\Model\Category;
use App\Model\IconLibrary;
use App\Service\Upload;

class ItemController
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

        $categories = Category::allByUser($userId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'create') {
                $categoryId = (int)($_POST['category_id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $sort = (int)($_POST['sort_order'] ?? 0);
                $iconType = null;
                $iconValue = null;

                $iconMode = $_POST['icon_mode'] ?? 'none';
                if ($iconMode === 'file' && !empty($_FILES['icon_file'])) {
                    $savedPath = Upload::saveAttachment($userId, $_FILES['icon_file']);
                    if ($savedPath) {
                        $iconType = 'file';
                        $iconValue = $savedPath;
                        IconLibrary::ensureExists($userId, $savedPath, $name ?: '项目图标');
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
                if ($categoryId <= 0 || $name === '') {
                    $error = '请选择分类并填写名称';
                } else {
                    try {
                        Item::create($userId, $categoryId, $name, $sort, $iconType, $iconValue);
                        $success = '新增项目成功';
                    } catch (\RuntimeException $e) {
                        if ($e->getMessage() === 'duplicate_item') {
                            $error = '该分类下已存在同名项目，请勿重复添加。';
                        } else {
                            $error = '新增项目时发生未知错误，请稍后重试。';
                        }
                    }
                }
            } elseif ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $categoryId = (int)($_POST['category_id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $sort = (int)($_POST['sort_order'] ?? 0);
                $iconType = null;
                $iconValue = null;

                $iconMode = $_POST['icon_mode'] ?? 'none';
                $current = Item::findByUser($userId, $id);

                if ($iconMode === 'file' && !empty($_FILES['icon_file'])) {
                    $savedPath = Upload::saveAttachment($userId, $_FILES['icon_file']);
                    if ($savedPath) {
                        $iconType = 'file';
                        $iconValue = $savedPath;
                        IconLibrary::ensureExists($userId, $savedPath, $name ?: '项目图标');
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
                if ($categoryId <= 0 || $name === '') {
                    $error = '请选择分类并填写名称';
                } else {
                    try {
                        Item::update($userId, $id, $categoryId, $name, $sort, $iconType, $iconValue);
                        $success = '更新项目成功';
                    } catch (\RuntimeException $e) {
                        if ($e->getMessage() === 'duplicate_item') {
                            $error = '该分类下已存在同名项目，请更换一个名称。';
                        } else {
                            $error = '更新项目时发生未知错误，请稍后重试。';
                        }
                    }
                }
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if (!Item::delete($userId, $id)) {
                    $error = '该项目已有记账数据，无法删除';
                } else {
                    $success = '删除项目成功';
                }
            }
        }

        $items = Item::allByUser($userId);
        $iconLibrary = IconLibrary::allByUser($userId);
        $this->render('items/index', compact('items', 'categories', 'iconLibrary', 'error', 'success'));
    }
}
