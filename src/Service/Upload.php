<?php
namespace App\Service;

use App\Service\Config;

class Upload
{
    public static function saveAttachment(int $userId, array $file): ?string
    {
        if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        if ($file['size'] > 10 * 1024 * 1024) { // 10MB
            return null;
        }

        $baseDir = Config::get('app.upload_dir');
        if (!$baseDir) {
            return null;
        }

        $date = new \DateTime();
        $subPath = $userId . '/' . $date->format('Y') . '/' . $date->format('m') . '/' . $date->format('d');
        $targetDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $subPath;

        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                return null;
            }
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safeName = uniqid('att_', true) . ($ext ? ('.' . $ext) : '');
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return null;
        }

        // 返回相对路径，供前端访问时拼接 /uploads/
        return $subPath . '/' . $safeName;
    }

    /**
     * 为用户保存头像文件（来自表单上传）。
     */
    public static function saveAvatar(int $userId, array $file): ?string
    {
        if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        // 头像限制为 5MB 以内
        if ($file['size'] > 5 * 1024 * 1024) {
            return null;
        }

        $baseDir = Config::get('app.upload_dir');
        if (!$baseDir) {
            return null;
        }

        $date = new \DateTime();
        $subPath = $userId . '/' . $date->format('Y') . '/' . $date->format('m') . '/' . $date->format('d');
        $targetDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $subPath;

        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                return null;
            }
        }

        $ext = strtolower((string)pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            $ext = 'jpg';
        }
        $safeName = uniqid('avatar_', true) . '.' . $ext;
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return null;
        }

        return $subPath . '/' . $safeName;
    }

    /**
     * 从远程 URL 下载头像并保存到 uploads 目录。
     */
    public static function saveAvatarFromUrl(int $userId, string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        $baseDir = Config::get('app.upload_dir');
        if (!$baseDir) {
            return null;
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
            ],
            'https' => [
                'timeout' => 5,
            ],
        ]);

        $data = @file_get_contents($url, false, $context);
        if ($data === false) {
            return null;
        }

        // 简单限制：不超过 5MB
        if (strlen($data) > 5 * 1024 * 1024) {
            return null;
        }

        if (!function_exists('getimagesizefromstring')) {
            return null;
        }
        $info = @getimagesizefromstring($data);
        if ($info === false) {
            return null;
        }

        $mime = (string)($info['mime'] ?? '');
        $ext = 'jpg';
        if ($mime === 'image/png') {
            $ext = 'png';
        } elseif ($mime === 'image/gif') {
            $ext = 'gif';
        } elseif ($mime === 'image/webp') {
            $ext = 'webp';
        }

        $date = new \DateTime();
        $subPath = $userId . '/' . $date->format('Y') . '/' . $date->format('m') . '/' . $date->format('d');
        $targetDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $subPath;

        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                return null;
            }
        }

        $safeName = uniqid('avatar_', true) . '.' . $ext;
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $safeName;

        if (@file_put_contents($targetPath, $data) === false) {
            return null;
        }

        return $subPath . '/' . $safeName;
    }

    /**
     * 删除 uploads 目录下的相对路径文件（头像或附件）。
     */
    public static function deleteByRelativePath(?string $relativePath): void
    {
        if ($relativePath === null || $relativePath === '') {
            return;
        }
        $baseDir = Config::get('app.upload_dir');
        if (!$baseDir) {
            return;
        }
        $relativePath = ltrim($relativePath, '/\\');
        $fullPath = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
