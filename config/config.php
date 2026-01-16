<?php
return [
    'db' => [
        'host' => 'localhost',
        'dbname' => 'ssjizhang_cn',
        'user' => '', // 请填写数据库用户名
        'pass' => '', // 请填写数据库密码
        'charset' => 'utf8mb4',
    ],
    'mail' => [
        // 驱动：mail 使用 PHP 内置 mail()，smtp 使用外部 SMTP（需安装 PHPMailer）
        'driver' => 'smtp', // 建议使用 smtp，具体参数请根据实际邮箱填写
        'host' => '请填写SMTP服务器地址',
        'port' => 465,
        'encryption' => 'ssl',
        'username' => '请填写发件邮箱账号',
        'password' => '请填写发件邮箱密码或授权码',
        'from_email' => '请填写发件邮箱',
        'from_name' => '三石记账系统',
    ],
    'app' => [
        'name' => 'SanS三石记账系统',
        'base_url' => '/',
        // 对外访问的完整站点地址，用于在邮件中生成完整链接
        'site_url' => 'https://你的域名',
        'allow_register' => true,
        'upload_dir' => __DIR__ . '/../uploads',
        // 系统版本号
        'version' => 'v1.13.2',
        // 是否启用未登录首页（给客户打包为纯 PC 端时可关闭）
        'landing_enabled' => true,
        // 是否启用授权管理后台入口（打包给最终用户时默认关闭）
        'license_admin_enabled' => false,
    ],
    'license' => [
        // 是否启用授权客户端联机校验（打包版建议开启）
        'client_enabled' => true,
        // 授权主站地址（请替换为你的实际授权主站 API 域名，如 https://ssjizhang.cn）
        'server_url' => 'https://ssjizhang.cn',
        // 可选：直接在此处写死部署版授权码，留空则使用系统参数中的授权码
        'fixed_code' => '',
        // 授权联机校验的时间间隔（小时），24 小时联机一次
        'check_interval_hours' => 24,
        // 允许的最长离线天数，超过后本地视为授权失效
        'offline_max_days' => 7,
    ],
    'wechat' => [
        // 微信小程序相关配置，如不使用可留空
        'miniapp_appid' => '',
        'miniapp_secret' => '',
        // 小程序分享签名密钥（仅服务器与小程序之间约定，不对外暴露）
        'share_secret' => '自定义一个随机字符串',
        // 是否启用小程序相关功能（默认关闭，纯 PC 端部署无需开启）
        'enable_miniapp' => false,
    ],
];
