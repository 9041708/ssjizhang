<?php
namespace App\Controller;

use App\Service\Config;
use App\Model\Feedback;
use App\Model\User;
use App\Service\Upload;

class FeedbackController
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

        $currentUser = User::findById($userId);
        $isAdmin = ($currentUser['role'] ?? 'user') === 'admin';

        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? 'create';

            if ($action === 'reply' && $isAdmin) {
                $id = (int)($_POST['id'] ?? 0);
                $reply = trim((string)($_POST['reply'] ?? ''));
                $status = (string)($_POST['status'] ?? 'resolved');

                if ($id <= 0) {
                    $error = '无效的反馈 ID';
                } elseif ($reply === '') {
                    $error = '请填写系统回复内容';
                } else {
                    // 计算上传图片应归属的用户（按反馈所属用户保存到其 uploads 目录）
                    $feedbackOwnerId = Feedback::getUserId($id) ?? $userId;
                    $replyImagePaths = [];
                    if (isset($_FILES['reply_images']) && is_array($_FILES['reply_images']['name'] ?? null)) {
                        $fileCount = count($_FILES['reply_images']['name']);
                        for ($i = 0; $i < $fileCount; $i++) {
                            $file = [
                                'name' => $_FILES['reply_images']['name'][$i] ?? null,
                                'type' => $_FILES['reply_images']['type'][$i] ?? null,
                                'tmp_name' => $_FILES['reply_images']['tmp_name'][$i] ?? null,
                                'error' => $_FILES['reply_images']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                                'size' => $_FILES['reply_images']['size'][$i] ?? 0,
                            ];
                            if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                                continue;
                            }
                            $saved = Upload::saveAttachment($feedbackOwnerId, $file);
                            if ($saved !== null) {
                                $replyImagePaths[] = $saved;
                            }
                        }
                    }

                    Feedback::updateReply($id, $userId, $reply, $status, $replyImagePaths);
                    $success = '系统回复已保存，并已同步更新状态。';
                }
            } else {
                $category = (string)($_POST['category'] ?? Feedback::CATEGORY_SUGGEST);
                $content = trim((string)($_POST['content'] ?? ''));

                if ($content === '') {
                    $error = '请填写问题或建议的具体描述';
                } else {
                    $imagePaths = [];
                    if (isset($_FILES['images']) && is_array($_FILES['images']['name'] ?? null)) {
                        $fileCount = count($_FILES['images']['name']);
                        for ($i = 0; $i < $fileCount; $i++) {
                            $file = [
                                'name' => $_FILES['images']['name'][$i] ?? null,
                                'type' => $_FILES['images']['type'][$i] ?? null,
                                'tmp_name' => $_FILES['images']['tmp_name'][$i] ?? null,
                                'error' => $_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                                'size' => $_FILES['images']['size'][$i] ?? 0,
                            ];
                            if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                                continue;
                            }
                            $saved = Upload::saveAttachment($userId, $file);
                            if ($saved !== null) {
                                $imagePaths[] = $saved;
                            }
                        }
                    }

                    Feedback::create($userId, $category, $content, $imagePaths);
                    $success = '反馈已提交，感谢您的支持！处理结果会同步展示在下方列表中。';
                }
            }
        }

        $categories = Feedback::categoryLabels();
        $feedbackList = Feedback::listForFaq(200);

        $this->render('feedback/index', [
            'error' => $error,
            'success' => $success,
            'categories' => $categories,
            'feedbackList' => $feedbackList,
            'isAdmin' => $isAdmin,
        ]);
    }
}
