-- XMRig Web管理系统数据库初始化脚本
-- 请在MySQL/MariaDB中执行此脚本

CREATE DATABASE IF NOT EXISTS xmrig_manager DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE xmrig_manager;

-- 用户表
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL COMMENT '用户名',
    password VARCHAR(255) NOT NULL COMMENT '密码哈希',
    email VARCHAR(100) UNIQUE NOT NULL COMMENT '邮箱',
    is_admin TINYINT DEFAULT 0 COMMENT '是否管理员',
    status TINYINT DEFAULT 1 COMMENT '状态：1启用，0禁用',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- 币种表
CREATE TABLE IF NOT EXISTS coins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL COMMENT '币种名称',
    display_name VARCHAR(100) NOT NULL COMMENT '显示名称',
    algorithm VARCHAR(50) NOT NULL COMMENT '算法',
    public_config JSON COMMENT '公共配置JSON',
    status TINYINT DEFAULT 1 COMMENT '状态：1启用，0禁用',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='币种表';

-- 币种字段配置表
CREATE TABLE IF NOT EXISTS coin_fields (
    id INT PRIMARY KEY AUTO_INCREMENT,
    coin_id INT NOT NULL COMMENT '币种ID',
    field_name VARCHAR(50) NOT NULL COMMENT '字段名称',
    field_label VARCHAR(100) NOT NULL COMMENT '字段标签',
    field_type VARCHAR(20) NOT NULL COMMENT '字段类型：text,number,select,textarea',
    field_options TEXT COMMENT '字段选项（用于select类型）',
    is_required TINYINT DEFAULT 1 COMMENT '是否必填',
    default_value VARCHAR(500) COMMENT '默认值',
    placeholder VARCHAR(200) COMMENT '占位符提示',
    sort_order INT DEFAULT 0 COMMENT '排序',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coin_id) REFERENCES coins(id) ON DELETE CASCADE,
    INDEX idx_coin_id (coin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='币种字段配置表';

-- 用户配置表
CREATE TABLE IF NOT EXISTS user_configs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL COMMENT '用户ID',
    coin_id INT NOT NULL COMMENT '币种ID',
    config_data JSON COMMENT '用户配置数据',
    worker_name VARCHAR(100) COMMENT '矿工名称',
    status TINYINT DEFAULT 1 COMMENT '状态：1启用，0禁用',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (coin_id) REFERENCES coins(id) ON DELETE CASCADE,
    UNIQUE KEY uk_user_coin_worker (user_id, coin_id, worker_name),
    INDEX idx_user_id (user_id),
    INDEX idx_coin_id (coin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户配置表';

-- 登录日志表
CREATE TABLE IF NOT EXISTS login_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT COMMENT '用户ID',
    username VARCHAR(50) COMMENT '尝试登录的用户名',
    ip VARCHAR(45) COMMENT 'IP地址',
    user_agent VARCHAR(500) COMMENT '用户代理',
    status TINYINT COMMENT '状态：1成功，0失败',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='登录日志表';

-- 插入默认管理员账号（密码：admin123）
INSERT INTO users (username, password, email, is_admin) VALUES 
('admin', '$2y$10$YKxLqB4NwE5d0Tk5kUqfLO5Xh8kM6QxXzP0GyLMxXbJwRzT0K6Ihm', 'admin@xmrig.com', 1);

-- 插入默认XMR币种配置
INSERT INTO coins (name, display_name, algorithm, public_config) VALUES 
('xmr', 'Monero (XMR)', 'rx/0', '{
    "cpu": {
        "enabled": true,
        "max-threads-hint": 70,
        "yield": true
    },
    "randomx": {
        "1gb-pages": false,
        "wrmsr": true,
        "rdmsr": true
    },
    "pools": [
        {
            "url": "pool.supportxmr.com:3333",
            "keepalive": true,
            "tls": false
        }
    ],
    "donate-level": 1,
    "cpu-high-pause": 95,
    "cpu-low-resume": 30,
    "cpu-control-interval": 3,
    "cpu-resume-delay": 30
}');

-- 为XMR添加默认字段配置
INSERT INTO coin_fields (coin_id, field_name, field_label, field_type, is_required, placeholder, sort_order) VALUES 
(1, 'wallet', '钱包地址', 'text', 1, '请输入您的XMR钱包地址', 1),
(1, 'worker_name', '矿工名称', 'text', 1, '请输入矿工名称（英文字母和数字）', 2),
(1, 'process_pause_names', '进程暂停列表', 'textarea', 0, '输入要暂停的进程名，用逗号分隔', 3),
(1, 'window_pause_names', '窗口暂停列表', 'textarea', 0, '输入要暂停的窗口关键词，用逗号分隔', 4);

-- 创建会话表（用于验证码）
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    data TEXT,
    expires INT,
    INDEX idx_expires (expires)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会话表';