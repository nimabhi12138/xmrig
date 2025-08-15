-- 币种管理系统数据库设计
-- PHP 7.4+ / MySQL 5.7+

CREATE DATABASE IF NOT EXISTS `coin_management` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `coin_management`;

-- 管理员表
CREATE TABLE `admins` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL UNIQUE,
    `password` varchar(255) NOT NULL,
    `email` varchar(100) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 币种表
CREATE TABLE `coins` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `symbol` varchar(20) NOT NULL,
    `icon_url` varchar(255) DEFAULT NULL,
    `global_template` longtext DEFAULT NULL COMMENT 'JSON格式的全局模板，包含占位符',
    `status` tinyint(1) DEFAULT 1 COMMENT '1:启用 0:禁用',
    `sort_order` int(11) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`),
    KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 自定义字段表
CREATE TABLE `custom_fields` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `coin_id` int(11) NOT NULL,
    `title` varchar(100) NOT NULL COMMENT '用户可见的字段名称',
    `field_type` enum('text','textarea','select','number','email','url') NOT NULL DEFAULT 'text',
    `placeholder_key` varchar(50) NOT NULL COMMENT '模板中的占位符变量名',
    `is_required` tinyint(1) DEFAULT 0 COMMENT '是否必填',
    `options` text DEFAULT NULL COMMENT '下拉选择的选项(JSON格式)',
    `sort_order` int(11) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`coin_id`) REFERENCES `coins`(`id`) ON DELETE CASCADE,
    KEY `idx_coin_id` (`coin_id`),
    KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 用户表
CREATE TABLE `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL UNIQUE,
    `email` varchar(100) NOT NULL UNIQUE,
    `password` varchar(255) NOT NULL,
    `api_token` varchar(64) NOT NULL UNIQUE COMMENT 'API访问令牌',
    `status` tinyint(1) DEFAULT 1 COMMENT '1:启用 0:禁用',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_username` (`username`),
    KEY `idx_email` (`email`),
    KEY `idx_api_token` (`api_token`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 用户配置表
CREATE TABLE `user_configs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `coin_id` int(11) NOT NULL,
    `field_values` longtext NOT NULL COMMENT 'JSON格式存储字段值',
    `generated_config` longtext DEFAULT NULL COMMENT '生成的完整配置(替换占位符后)',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`coin_id`) REFERENCES `coins`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_user_coin` (`user_id`, `coin_id`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 系统配置表
CREATE TABLE `system_config` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `config_key` varchar(100) NOT NULL UNIQUE,
    `config_value` text DEFAULT NULL,
    `description` varchar(255) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 插入默认管理员账户 (admin/admin123)
INSERT INTO `admins` (`username`, `password`) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- 插入默认系统配置
INSERT INTO `system_config` (`config_key`, `config_value`, `description`) VALUES 
('site_name', '币种管理系统', '网站名称'),
('site_description', '专业的数字货币参数配置管理平台', '网站描述'),
('api_rate_limit', '1000', 'API每日请求限制'),
('registration_enabled', '1', '是否允许用户注册');