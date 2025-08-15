-- 币种管理系统数据库结构
CREATE DATABASE IF NOT EXISTS crypto_config CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE crypto_config;

-- 用户表
CREATE TABLE IF NOT EXISTS users (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    api_token VARCHAR(64) UNIQUE,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_api_token (api_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 币种表
CREATE TABLE IF NOT EXISTS currencies (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    symbol VARCHAR(10) NOT NULL,
    icon VARCHAR(255),
    template_params JSON,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 自定义字段表
CREATE TABLE IF NOT EXISTS custom_fields (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    currency_id INT(11) UNSIGNED NOT NULL,
    field_title VARCHAR(100) NOT NULL,
    field_type ENUM('text', 'textarea', 'select', 'number') DEFAULT 'text',
    field_placeholder VARCHAR(100),
    field_options JSON,
    is_required TINYINT(1) DEFAULT 0,
    sort_order INT(11) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (currency_id) REFERENCES currencies(id) ON DELETE CASCADE,
    INDEX idx_currency (currency_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 用户配置表
CREATE TABLE IF NOT EXISTS user_configs (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) UNSIGNED NOT NULL,
    currency_id INT(11) UNSIGNED NOT NULL,
    field_values JSON,
    processed_config JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (currency_id) REFERENCES currencies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_currency (user_id, currency_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 插入默认管理员
INSERT INTO users (username, email, password, is_admin) 
VALUES ('admin', 'admin@example.com', '$2y$10$YourHashedPasswordHere', 1);