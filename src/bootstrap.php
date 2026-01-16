<?php
declare(strict_types=1);

// Composer autoload（如果已安装依赖）
$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
	require $autoload;
}

// 简单 PSR-4 自动加载，用于在未执行 composer install 时加载 App 命名空间下的类
spl_autoload_register(function (string $class): void {
	$prefix = 'App\\';
	$len = strlen($prefix);
	if (strncmp($prefix, $class, $len) !== 0) {
		return; // 非 App 命名空间，忽略
	}
	$relative = substr($class, $len);
	$file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
	if (file_exists($file)) {
		require $file;
	}
});

use App\Service\Config;
use App\Service\LicenseClient;

// 加载配置
Config::init(__DIR__ . '/../config/config.php');

// 打包版默认启用授权客户端，每次请求入口执行授权自检
if (Config::get('license.client_enabled', false)) {
	LicenseClient::enforce();
}
