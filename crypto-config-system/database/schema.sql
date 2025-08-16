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

-- 插入默认管理员 (密码: admin123)
INSERT INTO users (username, email, password, is_admin) 
VALUES ('admin', 'admin@example.com', '$2y$10$8Kp7.CYgdP0x7YD7xKqKKO3xEBgPBXrqhGYFQDHqW8V5sYKfwJEZe', 1)
ON DUPLICATE KEY UPDATE id=id;

-- 插入示例币种
INSERT INTO currencies (name, symbol, icon, template_params) VALUES 
('Bitcoin', 'BTC', 'https://cryptologos.cc/logos/bitcoin-btc-logo.png', '{
    "network": "mainnet",
    "wallet_address": "{{WALLET_ADDRESS}}",
    "private_key": "{{PRIVATE_KEY}}",
    "api_endpoint": "https://api.bitcoin.com",
    "confirmations": 6,
    "gas_limit": null
}'),
('Ethereum', 'ETH', 'https://cryptologos.cc/logos/ethereum-eth-logo.png', '{
    "network": "{{NETWORK}}",
    "wallet_address": "{{WALLET_ADDRESS}}",
    "private_key": "{{PRIVATE_KEY}}",
    "infura_key": "{{INFURA_KEY}}",
    "gas_limit": "{{GAS_LIMIT}}",
    "gas_price": "{{GAS_PRICE}}"
}'),
('Binance Smart Chain', 'BSC', 'https://cryptologos.cc/logos/bnb-bnb-logo.png', '{
    "network": "{{NETWORK}}",
    "wallet_address": "{{WALLET_ADDRESS}}",
    "private_key": "{{PRIVATE_KEY}}",
    "rpc_url": "{{RPC_URL}}",
    "chain_id": 56,
    "gas_limit": "{{GAS_LIMIT}}"
}')
ON DUPLICATE KEY UPDATE id=id;

-- 为Bitcoin添加示例字段
INSERT INTO custom_fields (currency_id, field_title, field_type, field_placeholder, is_required, sort_order) 
SELECT id, '钱包地址', 'text', '{{WALLET_ADDRESS}}', 1, 1 FROM currencies WHERE symbol = 'BTC'
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO custom_fields (currency_id, field_title, field_type, field_placeholder, is_required, sort_order) 
SELECT id, '私钥', 'textarea', '{{PRIVATE_KEY}}', 1, 2 FROM currencies WHERE symbol = 'BTC'
ON DUPLICATE KEY UPDATE id=id;

-- 为Ethereum添加示例字段
INSERT INTO custom_fields (currency_id, field_title, field_type, field_placeholder, field_options, is_required, sort_order) 
SELECT id, '网络', 'select', '{{NETWORK}}', '["mainnet", "ropsten", "rinkeby", "goerli"]', 1, 1 FROM currencies WHERE symbol = 'ETH'
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO custom_fields (currency_id, field_title, field_type, field_placeholder, is_required, sort_order) 
SELECT id, '钱包地址', 'text', '{{WALLET_ADDRESS}}', 1, 2 FROM currencies WHERE symbol = 'ETH'
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO custom_fields (currency_id, field_title, field_type, field_placeholder, is_required, sort_order) 
SELECT id, '私钥', 'textarea', '{{PRIVATE_KEY}}', 1, 3 FROM currencies WHERE symbol = 'ETH'
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO custom_fields (currency_id, field_title, field_type, field_placeholder, is_required, sort_order) 
SELECT id, 'Infura API Key', 'text', '{{INFURA_KEY}}', 0, 4 FROM currencies WHERE symbol = 'ETH'
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO custom_fields (currency_id, field_title, field_type, field_placeholder, is_required, sort_order) 
SELECT id, 'Gas Limit', 'number', '{{GAS_LIMIT}}', 0, 5 FROM currencies WHERE symbol = 'ETH'
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO custom_fields (currency_id, field_title, field_type, field_placeholder, is_required, sort_order) 
SELECT id, 'Gas Price (Gwei)', 'number', '{{GAS_PRICE}}', 0, 6 FROM currencies WHERE symbol = 'ETH'
ON DUPLICATE KEY UPDATE id=id;