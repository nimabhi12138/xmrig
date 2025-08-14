<?php
/**
 * XMRig Web管理系统配置文件
 * 请根据您的实际环境修改配置
 */

// 数据库配置
define('DB_HOST', 'localhost');        // 数据库主机
define('DB_PORT', 3306);               // 数据库端口
define('DB_NAME', 'xmrig_manager');    // 数据库名称
define('DB_USER', 'root');              // 数据库用户名
define('DB_PASS', '');                  // 数据库密码
define('DB_CHARSET', 'utf8mb4');        // 数据库字符集

// 系统配置
define('SITE_NAME', 'XMRig管理系统');   // 网站名称
define('SITE_URL', 'http://localhost'); // 网站URL（不要以/结尾）
define('SESSION_NAME', 'XMRIG_SESSION'); // Session名称
define('SESSION_TIMEOUT', 7200);        // Session超时时间（秒）

// 安全配置
define('PASSWORD_SALT', 'xmrig_2024_salt_key'); // 密码加密盐值
define('API_SECRET', 'your_api_secret_key_here'); // API密钥

// 验证码配置
define('CAPTCHA_LENGTH', 4);            // 验证码长度
define('CAPTCHA_WIDTH', 120);           // 验证码宽度
define('CAPTCHA_HEIGHT', 40);           // 验证码高度

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 错误报告（生产环境请设置为0）
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 开启会话
session_name(SESSION_NAME);
session_start();

/**
 * 数据库连接函数
 */
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ]);
        } catch (PDOException $e) {
            die('数据库连接失败：' . $e->getMessage());
        }
    }
    
    return $pdo;
}

/**
 * 检查是否已登录
 */
function checkLogin() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
        header('Location: login.php');
        exit;
    }
    
    // 检查会话超时
    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_destroy();
        header('Location: login.php?msg=timeout');
        exit;
    }
    
    $_SESSION['last_activity'] = time();
}

/**
 * 检查是否为管理员
 */
function checkAdmin() {
    checkLogin();
    
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
        header('Location: index.php');
        exit;
    }
}

/**
 * 生成CSRF令牌
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * 验证CSRF令牌
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die('CSRF验证失败');
    }
}

/**
 * 清理输入
 */
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * 生成随机字符串
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
?>