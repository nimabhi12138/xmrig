<?php
// 数据库配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'crypto_config');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// 系统配置
define('SITE_URL', 'http://localhost/crypto-config-system');
define('SITE_NAME', '币种配置管理系统');
define('SECRET_KEY', 'your-secret-key-here-' . bin2hex(random_bytes(16)));

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session配置
session_start();