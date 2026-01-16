<?php
namespace App\Controller;

use App\Service\Config;

class ChangelogController
{
    private function requireLogin(): void
    {
        $uid = (int)($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) {
            header('Location: /public/index.php?route=login');
            exit;
        }
    }

    private function render(string $view, array $params = []): void
    {
        extract($params);
        $appName = Config::get('app.name');
        include __DIR__ . '/../../templates/layout_main.php';
    }

    public function index(): void
    {
        $this->requireLogin();
        $this->render('changelog/index', []);
    }
}
