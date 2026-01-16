<?php
namespace App\Controller;

use App\Service\Config;
use App\Model\Category;
use App\Model\IconLibrary;

class CategoryController
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
                $type = $_POST['type'] ?? 'expense';
                $name = trim($_POST['name'] ?? '');
                $sort = (int)($_POST['sort_order'] ?? 0);
                $iconType = null;
                $iconValue = null;

                $iconMode = $_POST['icon_mode'] ?? 'none';
                if ($iconMode === 'file' && !empty($_FILES['icon_file'])) {
                    $savedPath = \App\Service\Upload::saveAttachment($userId, $_FILES['icon_file']);
                    if ($savedPath) {
                        $iconType = 'file';
                        $iconValue = $savedPath;
                        // 新上传的图标自动加入图标库，名称默认使用分类名
                        IconLibrary::ensureExists($userId, $savedPath, $name ?: '分类图标');
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

                if ($name === '') {
                    $error = '名称不能为空';
                } else {
                    Category::create($userId, $type, $name, $sort, $iconType, $iconValue);
                    $success = '新增分类成功';
                }
            } elseif ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $sort = (int)($_POST['sort_order'] ?? 0);
                $iconType = null;
                $iconValue = null;

                $iconMode = $_POST['icon_mode'] ?? 'none';
                $current = \App\Model\Category::findByUser($userId, $id);

                if ($iconMode === 'file' && !empty($_FILES['icon_file'])) {
                    $savedPath = \App\Service\Upload::saveAttachment($userId, $_FILES['icon_file']);
                    if ($savedPath) {
                        $iconType = 'file';
                        $iconValue = $savedPath;
                        IconLibrary::ensureExists($userId, $savedPath, $name ?: '分类图标');
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

                if ($name === '') {
                    $error = '名称不能为空';
                } else {
                    Category::update($userId, $id, $name, $sort, $iconType, $iconValue);
                    $success = '更新分类成功';
                }
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if (!Category::delete($userId, $id)) {
                    $error = '该分类已有记账数据，无法删除';
                } else {
                    $success = '删除分类成功';
                }
            }
        }

        $categories = Category::allByUser($userId);
        $iconLibrary = IconLibrary::allByUser($userId);
        $this->render('categories/index', compact('categories', 'iconLibrary', 'error', 'success'));
    }
}
