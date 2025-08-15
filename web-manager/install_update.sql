-- 数据库更新脚本
-- 添加新功能所需的表

USE xmrig_manager;

-- 配置访问日志表
CREATE TABLE IF NOT EXISTS config_access_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    ip VARCHAR(45),
    user_agent VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='配置访问日志';

-- 下载日志表
CREATE TABLE IF NOT EXISTS download_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    username VARCHAR(50),
    os VARCHAR(20),
    ip VARCHAR(45),
    user_agent VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='程序下载日志';

-- 添加用户配置的默认值
ALTER TABLE user_configs 
ADD COLUMN IF NOT EXISTS cpu_high_pause INT DEFAULT 95,
ADD COLUMN IF NOT EXISTS cpu_low_resume INT DEFAULT 30,
ADD COLUMN IF NOT EXISTS cpu_control_interval INT DEFAULT 3,
ADD COLUMN IF NOT EXISTS cpu_resume_delay INT DEFAULT 30;