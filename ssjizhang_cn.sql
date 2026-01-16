-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2026-01-13 04:19:40
-- 服务器版本： 10.11.11-MariaDB
-- PHP 版本： 8.2.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `ssjizhang_cn`
--

-- --------------------------------------------------------

--
-- 表的结构 `accounts`
--

CREATE TABLE `accounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `group_id` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `account_no` varchar(100) DEFAULT NULL,
  `initial_balance` decimal(18,2) NOT NULL DEFAULT 0.00,
  `current_balance` decimal(18,2) NOT NULL DEFAULT 0.00,
  `is_default` tinyint(4) NOT NULL DEFAULT 0,
  `icon_type` varchar(20) DEFAULT NULL COMMENT '图标类型：file/svg',
  `icon_value` text DEFAULT NULL COMMENT '图标值：文件相对路径或 SVG 代码',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `account_groups`
--

CREATE TABLE `account_groups` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `account_groups`
--

INSERT INTO `account_groups` (`id`, `code`, `name`) VALUES
(1, 'financial', '金融账户'),
(2, 'saving', '储蓄账户'),
(3, 'debt', '应付账款'),
(4, 'other', '其它账户'),
(5, 'receivable', '应收账款');

-- --------------------------------------------------------

--
-- 表的结构 `announcements`
--

CREATE TABLE `announcements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `scheduled_at` datetime NOT NULL COMMENT '计划推送时间（开始对用户可见的时间）',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `announcement_reads`
--

CREATE TABLE `announcement_reads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `announcement_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `client` enum('pc','miniapp') NOT NULL DEFAULT 'pc' COMMENT '查看来源：PC 端或小程序',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `api_tokens`
--

CREATE TABLE `api_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `token` varchar(100) NOT NULL,
  `client_type` varchar(32) NOT NULL COMMENT 'miniapp/web 等客户端类型',
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_used_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `budgets`
--

CREATE TABLE `budgets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `type` enum('expense','income') NOT NULL,
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('expense','income') NOT NULL,
  `name` varchar(100) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `icon_type` varchar(20) DEFAULT NULL COMMENT '图标类型：file/svg',
  `icon_value` text DEFAULT NULL COMMENT '图标值：文件相对路径或 SVG 代码',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `email_pushes`
--

CREATE TABLE `email_pushes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` mediumtext NOT NULL,
  `scope` enum('all','selected') NOT NULL DEFAULT 'all' COMMENT 'all=全量推送，selected=指定用户',
  `scheduled_at` datetime NOT NULL COMMENT '计划发送时间',
  `sent_at` datetime DEFAULT NULL COMMENT '最近一次实际发送时间',
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `email_push_recipients`
--

CREATE TABLE `email_push_recipients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `push_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(100) NOT NULL,
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `error_message` varchar(255) DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `email_tokens`
--

CREATE TABLE `email_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(100) NOT NULL,
  `type` enum('register','reset_password') NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `feedbacks`
--

CREATE TABLE `feedbacks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `category` varchar(20) NOT NULL COMMENT 'suggest / bug / other',
  `content` text NOT NULL,
  `images` text DEFAULT NULL COMMENT 'JSON 数组，存储相对路径',
  `status` enum('pending','resolved','closed') NOT NULL DEFAULT 'pending',
  `admin_reply` text DEFAULT NULL,
  `admin_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `admin_reply_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `icon_library`
--

CREATE TABLE `icon_library` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `items`
--

CREATE TABLE `items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `icon_type` varchar(20) DEFAULT NULL COMMENT '图标类型：file/svg',
  `icon_value` text DEFAULT NULL COMMENT '图标值：文件相对路径或 SVG 代码',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `license_messages`
--

CREATE TABLE `license_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL COMMENT '留言邮箱',
  `nickname` varchar(100) NOT NULL COMMENT '留言昵称',
  `content` text NOT NULL COMMENT '留言内容',
  `created_at` datetime NOT NULL COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='部署授权留言';

-- --------------------------------------------------------

--
-- 表的结构 `license_pricing`
--

CREATE TABLE `license_pricing` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `first_month_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `first_month_price_promo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `first_year_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `first_year_price_promo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `first_lifetime_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `first_lifetime_price_promo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `change_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `change_price_promo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_promo_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `license_pricing`
--

INSERT INTO `license_pricing` (`id`, `first_month_price`, `first_month_price_promo`, `first_year_price`, `first_year_price_promo`, `first_lifetime_price`, `first_lifetime_price_promo`, `change_price`, `change_price_promo`, `is_promo_active`, `created_at`, `updated_at`) VALUES
(1, 20.00, 19.00, 199.00, 99.00, 399.00, 299.00, 99.00, 69.00, 1, '2026-01-12 00:22:13', '2026-01-12 01:47:33');

-- --------------------------------------------------------

--
-- 表的结构 `license_requests`
--

CREATE TABLE `license_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `domain` varchar(255) NOT NULL,
  `request_type` enum('first','change') NOT NULL,
  `period` enum('month','year','lifetime') DEFAULT NULL,
  `status` enum('pending','processed','rejected') NOT NULL DEFAULT 'pending',
  `note` varchar(255) DEFAULT NULL,
  `pay_proof_path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `processed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `license_users`
--

CREATE TABLE `license_users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `domain` varchar(255) NOT NULL,
  `pay_method` varchar(64) DEFAULT NULL,
  `license_code` varchar(32) NOT NULL,
  `license_type` enum('first','change') NOT NULL DEFAULT 'first',
  `license_period` enum('month','year','lifetime') DEFAULT NULL,
  `price_plan` enum('normal','promo') DEFAULT 'normal',
  `price_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `license_status` enum('unused','normal','expired') NOT NULL DEFAULT 'unused',
  `deploy_status` enum('online','offline') NOT NULL DEFAULT 'offline',
  `domain_change_quota` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `domain_change_used` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `activated_at` datetime DEFAULT NULL,
  `license_expire_at` datetime DEFAULT NULL,
  `last_online_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `login_tokens`
--

CREATE TABLE `login_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `token` varchar(64) NOT NULL,
  `status` enum('pending','confirmed','expired') NOT NULL DEFAULT 'pending',
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `system_settings`
--

CREATE TABLE `system_settings` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `site_name` varchar(100) NOT NULL,
  `site_url` varchar(255) DEFAULT NULL,
  `site_icon_svg` mediumtext DEFAULT NULL,
  `allow_register` tinyint(4) NOT NULL DEFAULT 1,
  `session_timeout_hours` smallint(5) UNSIGNED NOT NULL DEFAULT 24,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `bind_qr_text` text DEFAULT NULL,
  `icp_number` varchar(128) DEFAULT NULL,
  `license_email` varchar(255) DEFAULT NULL COMMENT '本地授权邮箱',
  `license_code` varchar(255) DEFAULT NULL COMMENT '本地授权码',
  `license_status` varchar(50) DEFAULT NULL COMMENT '最近一次授权校验状态',
  `license_last_check_at` datetime DEFAULT NULL COMMENT '最近一次授权联机校验时间',
  `license_source_path` varchar(512) DEFAULT NULL COMMENT '授权部署包下载地址'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `system_settings`
--

INSERT INTO `system_settings` (`id`, `site_name`, `site_url`, `site_icon_svg`, `allow_register`, `session_timeout_hours`, `created_at`, `updated_at`, `bind_qr_text`, `icp_number`, `license_email`, `license_code`, `license_status`, `license_last_check_at`, `license_source_path`) VALUES
(1, '记账系统', 'https://example.com', '<svg t=\"1767089244919\" class=\"icon\" viewBox=\"0 0 1024 1024\" version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" p-id=\"5063\" width=\"200\" height=\"200\"><path d=\"M170.666667 186.181818c330.984727 101.562182 496.484848 203.931152 496.484848 307.13794C667.151515 596.992 501.651394 701.47103 170.666667 806.787879V186.181818z\" fill=\"#F8D02D\" p-id=\"5064\"></path><path d=\"M804.445091 522.100364a25.615515 25.615515 0 0 0-18.090667-7.431758c-6.826667 0-13.34303 2.684121-18.090666 7.447273l-54.147879 52.79806-158.875152 154.841213a27.368727 27.368727 0 0 0-6.547394 10.627878l-28.315151 93.975273-1.830788 6.268121a25.088 25.088 0 0 0 4.530424 22.062546 26.453333 26.453333 0 0 0 27.973818 9.014303l102.648243-29.540849a22.652121 22.652121 0 0 0 10.860606-6.392242l1.458424-1.396364 21.240243-20.588606 17.423515-17.780364 9.448727-9.200484 39.315394-38.36897 104.882424-102.291394 18.075152-17.780364c4.980364-4.608 7.850667-10.969212 7.959272-17.671757a24.498424 24.498424 0 0 0-7.447272-17.873455L804.460606 522.084848zM645.833697 818.269091L589.575758 834.358303l-45.878303 12.8 4.064969-12.8 26.220606-86.946909 22.155637-22.000485 72.347151 70.593939-22.667636 22.248728z m107.613091-104.727273l-66.715152 65.086061-72.610909-70.842182 136.052364-132.732121 3.273697 3.584 69.213091 67.64606-69.197576 67.258182z m87.303757-85.286788l-72.486787-70.842182 18.090666-17.144242 71.959273 70.206061-17.563152 17.780363zM323.971879 287.030303l47.445333 92.454788 47.460849-92.454788h44.683636l-60.291879 106.775273h40.246303v20.324848h-52.441212v25.584485h52.441212v20.44897h-52.441212v46.033454h-39.315394V460.179394h-52.441212v-20.200727h52.441212V414.409697h-52.037818v-20.588606h39.982545L279.272727 287.030303h44.699152zM254.991515 562.424242h320.325818c13.34303 0 20.014545 7.757576 20.014546 23.272728s-6.671515 23.272727-20.014546 23.272727H254.991515c-13.34303 0-20.014545-7.757576-20.014545-23.272727s6.671515-23.272727 20.014545-23.272728zM231.548121 663.272727h151.00897c13.715394 0 20.588606 7.757576 20.588606 23.272728s-6.873212 23.272727-20.588606 23.272727h-151.00897c-13.730909 0-20.588606-7.757576-20.588606-23.272727s6.857697-23.272727 20.588606-23.272728zM784.135758 286.673455a14.801455 14.801455 0 0 0-9.712485-3.52194 14.801455 14.801455 0 0 0-9.728 3.52194l-96.830061 84.092121-48.345212-41.984a14.801455 14.801455 0 0 0-9.728-3.52194 14.801455 14.801455 0 0 0-9.728 3.52194 10.814061 10.814061 0 0 0 0 16.771879l58.212848 50.563878c1.551515 1.272242 3.428848 2.203152 5.476849 2.730667 3.025455 0.884364 6.299152 0.884364 9.309091 0 1.675636-0.651636 3.196121-1.536 4.530424-2.622061l106.558061-92.547878a11.170909 11.170909 0 0 0 4.111515-8.502303 11.170909 11.170909 0 0 0-4.111515-8.502303z\" fill=\"#333333\" p-id=\"5065\"></path><path d=\"M884.363636 201.138424v255.596606c0 10.317576-8.936727 18.695758-19.952484 18.695758-11.015758 0-19.93697-8.378182-19.93697-18.711273V201.153939c0-6.888727-5.957818-12.458667-13.312-12.458666H192.853333c-7.354182 0-13.312 5.585455-13.312 12.458666v598.450425c0 6.888727 5.957818 12.458667 13.312 12.458666H465.454545c11.015758 0 19.952485 8.378182 19.952485 18.711273 0 10.317576-8.936727 18.695758-19.952485 18.695758H192.837818C163.452121 849.454545 139.636364 827.112727 139.636364 799.588848V201.138424c0-27.539394 23.815758-49.865697 53.201454-49.865697H831.146667c29.385697 0 53.201455 22.341818 53.201454 49.865697z\" fill=\"#333333\" p-id=\"5066\"></path></svg>', 1, 24, '2025-12-30 04:21:02', '2026-01-13 04:17:32', NULL, NULL, NULL, NULL, '0', NULL, NULL);

-- --------------------------------------------------------

--
-- 表的结构 `transactions`
--

CREATE TABLE `transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('expense','income') NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `from_account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `to_account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL,
  `trans_time` datetime NOT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `nickname` varchar(50) NOT NULL,
  `avatar_path` varchar(255) DEFAULT NULL COMMENT '用户头像文件相对路径（uploads 下）',
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `email_verified` tinyint(4) NOT NULL DEFAULT 0,
  `failed_login_count` int(11) NOT NULL DEFAULT 0,
  `login_lock_until` datetime DEFAULT NULL,
  `theme_mode` enum('light','dark') NOT NULL DEFAULT 'light',
  `budget_reminder_enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否开启预算接近上限/超支提醒',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `users`
--

INSERT INTO `users` (`id`, `username`, `nickname`, `avatar_path`, `email`, `password_hash`, `role`, `status`, `email_verified`, `failed_login_count`, `login_lock_until`, `theme_mode`, `budget_reminder_enabled`, `created_at`, `updated_at`) VALUES
(7, 'demo', 'ceshi', NULL, 'demo@ssjizhang.cn', '$2y$10$D53jEnu4/eI5b2PTA7JTGuy7tq8od1OoAfrig8skrNSMEiVaMBEuK', 'admin', 1, 1, 0, NULL, 'light', 1, '2026-01-06 02:36:37', '2026-01-12 11:10:26');

-- --------------------------------------------------------

--
-- 表的结构 `user_wechat_bindings`
--

CREATE TABLE `user_wechat_bindings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `openid` varchar(64) NOT NULL,
  `unionid` varchar(64) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_login_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转储表的索引
--

--
-- 表的索引 `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_accounts_user` (`user_id`),
  ADD KEY `fk_accounts_group` (`group_id`);

--
-- 表的索引 `account_groups`
--
ALTER TABLE `account_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- 表的索引 `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_announcements_scheduled_at` (`scheduled_at`);

--
-- 表的索引 `announcement_reads`
--
ALTER TABLE `announcement_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_announcement_user` (`announcement_id`,`user_id`),
  ADD KEY `idx_announcement_reads_announcement` (`announcement_id`),
  ADD KEY `idx_announcement_reads_user` (`user_id`);

--
-- 表的索引 `api_tokens`
--
ALTER TABLE `api_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_token` (`token`),
  ADD KEY `fk_token_user` (`user_id`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- 表的索引 `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_budget` (`user_id`,`year`,`month`,`type`,`category_id`,`item_id`),
  ADD KEY `fk_budget_category` (`category_id`),
  ADD KEY `fk_budget_item` (`item_id`);

--
-- 表的索引 `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_user_type_name` (`user_id`,`type`,`name`);

--
-- 表的索引 `email_pushes`
--
ALTER TABLE `email_pushes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_pushes_status_scheduled` (`status`,`scheduled_at`);

--
-- 表的索引 `email_push_recipients`
--
ALTER TABLE `email_push_recipients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_push_recipients_push` (`push_id`),
  ADD KEY `idx_email_push_recipients_user` (`user_id`);

--
-- 表的索引 `email_tokens`
--
ALTER TABLE `email_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_email_tokens_user` (`user_id`);

--
-- 表的索引 `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_feedbacks_user` (`user_id`),
  ADD KEY `idx_feedbacks_status` (`status`);

--
-- 表的索引 `icon_library`
--
ALTER TABLE `icon_library`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_icon_library_user` (`user_id`);

--
-- 表的索引 `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_user_cat_name` (`user_id`,`category_id`,`name`),
  ADD KEY `fk_items_category` (`category_id`);

--
-- 表的索引 `license_messages`
--
ALTER TABLE `license_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- 表的索引 `license_pricing`
--
ALTER TABLE `license_pricing`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `license_requests`
--
ALTER TABLE `license_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`);

--
-- 表的索引 `license_users`
--
ALTER TABLE `license_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_license_code` (`license_code`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_domain` (`domain`);

--
-- 表的索引 `login_tokens`
--
ALTER TABLE `login_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_token` (`token`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- 表的索引 `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tx_user` (`user_id`),
  ADD KEY `fk_tx_category` (`category_id`),
  ADD KEY `fk_tx_item` (`item_id`),
  ADD KEY `fk_tx_from_account` (`from_account_id`),
  ADD KEY `fk_tx_to_account` (`to_account_id`);

--
-- 表的索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- 表的索引 `user_wechat_bindings`
--
ALTER TABLE `user_wechat_bindings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_wechat_openid` (`openid`),
  ADD KEY `fk_wechat_user` (`user_id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `account_groups`
--
ALTER TABLE `account_groups`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用表AUTO_INCREMENT `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `announcement_reads`
--
ALTER TABLE `announcement_reads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `api_tokens`
--
ALTER TABLE `api_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `budgets`
--
ALTER TABLE `budgets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `email_pushes`
--
ALTER TABLE `email_pushes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `email_push_recipients`
--
ALTER TABLE `email_push_recipients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `email_tokens`
--
ALTER TABLE `email_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `icon_library`
--
ALTER TABLE `icon_library`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `items`
--
ALTER TABLE `items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `license_messages`
--
ALTER TABLE `license_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `license_requests`
--
ALTER TABLE `license_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `license_users`
--
ALTER TABLE `license_users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `login_tokens`
--
ALTER TABLE `login_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- 使用表AUTO_INCREMENT `user_wechat_bindings`
--
ALTER TABLE `user_wechat_bindings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 限制导出的表
--

--
-- 限制表 `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `fk_accounts_group` FOREIGN KEY (`group_id`) REFERENCES `account_groups` (`id`),
  ADD CONSTRAINT `fk_accounts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 限制表 `announcement_reads`
--
ALTER TABLE `announcement_reads`
  ADD CONSTRAINT `fk_announcement_reads_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_announcement_reads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 限制表 `api_tokens`
--
ALTER TABLE `api_tokens`
  ADD CONSTRAINT `fk_token_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 限制表 `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `fk_budget_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_budget_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_budget_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 限制表 `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_categories_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 限制表 `email_push_recipients`
--
ALTER TABLE `email_push_recipients`
  ADD CONSTRAINT `fk_email_push_recipients_push` FOREIGN KEY (`push_id`) REFERENCES `email_pushes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_email_push_recipients_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 限制表 `email_tokens`
--
ALTER TABLE `email_tokens`
  ADD CONSTRAINT `fk_email_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 限制表 `icon_library`
--
ALTER TABLE `icon_library`
  ADD CONSTRAINT `fk_icon_library_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 限制表 `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `fk_items_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `fk_items_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 限制表 `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_tx_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `fk_tx_from_account` FOREIGN KEY (`from_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tx_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tx_to_account` FOREIGN KEY (`to_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tx_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 限制表 `user_wechat_bindings`
--
ALTER TABLE `user_wechat_bindings`
  ADD CONSTRAINT `fk_wechat_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
