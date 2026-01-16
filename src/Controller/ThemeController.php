<?php
namespace App\Controller;

use App\Model\User;

class ThemeController
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

    public function toggle(): void
    {
        $userId = $this->requireLogin();

        $current = $_SESSION['theme_mode'] ?? 'light';
        $new = ($current === 'dark') ? 'light' : 'dark';

        User::updateThemeMode($userId, $new);
        $_SESSION['theme_mode'] = $new;

        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'mode' => $new,
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? '/public/index.php';
        header('Location: ' . $referer);
    }
}
