<?php
/**
 * 币种管理系统 - 一键安装脚本
 * 适用于宝塔面板/小皮面板环境
 * PHP 7.4+ / MySQL 5.7+
 */

// 检查PHP版本
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('错误: PHP版本必须 >= 7.4.0，当前版本: ' . PHP_VERSION);
}

// 检查必要扩展
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'openssl', 'session'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

if (!empty($missing_extensions)) {
    die('错误: 缺少必要的PHP扩展: ' . implode(', ', $missing_extensions));
}

$step = $_GET['step'] ?? 1;
$error = '';
$success = $_GET['success'] ?? '';

// 处理安装步骤
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 2:
            // 数据库连接测试
            $host = trim($_POST['host'] ?? 'localhost');
            $port = trim($_POST['port'] ?? '3306');
            $database = trim($_POST['database'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? ''; // 密码可能包含空格，不trim
            
            // 增强验证
            if (empty($database)) {
                $error = '数据库名不能为空！';
            } elseif (empty($username)) {
                $error = '数据库用户名不能为空！';
            } elseif (empty($host)) {
                $error = '数据库主机不能为空！';
            } else {
                try {
                    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
                    $pdo = new PDO($dsn, $username, $password, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);
                    
                    // 检查数据库是否存在，不存在则创建
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $pdo->exec("USE `{$database}`");
                    
                    // 保存数据库配置
                    saveConfig($host, $port, $database, $username, $password);
                    $success = '数据库连接成功！';
                    
                    // 重定向到下一步
                    header('Location: ?step=3&success=' . urlencode($success));
                    exit;
                    
                } catch (PDOException $e) {
                    $error_code = $e->getCode();
                    $error_msg = $e->getMessage();
                    
                    // 提供更友好的错误信息
                    if (strpos($error_msg, 'Access denied') !== false) {
                        $error = "数据库连接失败：用户名或密码错误！<br>
                        请检查：<br>
                        • 用户名：'{$username}' 是否正确<br>
                        • 密码是否正确<br>
                        • 该用户是否有数据库权限<br>
                        原始错误：{$error_msg}";
                    } elseif (strpos($error_msg, 'Unknown database') !== false) {
                        $error = "数据库 '{$database}' 不存在，系统将尝试创建。<br>原始错误：{$error_msg}";
                    } elseif (strpos($error_msg, "Can't connect") !== false) {
                        $error = "无法连接到数据库服务器！<br>
                        请检查：<br>
                        • 数据库服务是否启动<br>
                        • 主机地址：'{$host}' 是否正确<br>
                        • 端口：{$port} 是否正确<br>
                        原始错误：{$error_msg}";
                    } else {
                        $error = "数据库连接失败：{$error_msg}";
                    }
                }
            }
            break;
            
        case 3:
            // 安装数据库
            try {
                $config = getConfig();
                if (!$config) {
                    throw new Exception('配置文件读取失败');
                }
                
                installDatabase($config);
                $success = '数据库安装成功！';
                
                // 重定向到下一步
                header('Location: ?step=4&success=' . urlencode($success));
                exit;
                
            } catch (Exception $e) {
                $error = '数据库安装失败: ' . $e->getMessage();
            }
            break;
            
        case 4:
            // 设置管理员账户
            $admin_user = $_POST['admin_user'] ?? '';
            $admin_pass = $_POST['admin_pass'] ?? '';
            $admin_email = $_POST['admin_email'] ?? '';
            
            if (empty($admin_user) || empty($admin_pass)) {
                $error = '请填写管理员用户名和密码';
            } else {
                try {
                    $config = getConfig();
                    createAdmin($config, $admin_user, $admin_pass, $admin_email);
                    $success = '管理员账户创建成功！';
                    
                    // 重定向到完成页面
                    header('Location: ?step=5&success=' . urlencode($success) . '&admin_user=' . urlencode($admin_user));
                    exit;
                    
                } catch (Exception $e) {
                    $error = '管理员账户创建失败: ' . $e->getMessage();
                }
            }
            break;
    }
}

function saveConfig($host, $port, $database, $username, $password) {
    $config = "<?php
// 数据库配置
define('DB_HOST', '{$host}');
define('DB_PORT', '{$port}');
define('DB_NAME', '{$database}');
define('DB_USER', '{$username}');
define('DB_PASS', '{$password}');
define('DB_CHARSET', 'utf8mb4');
?>";
    
    file_put_contents(__DIR__ . '/config/install_config.php', $config);
}

function getConfig() {
    $config_file = __DIR__ . '/config/install_config.php';
    if (!file_exists($config_file)) {
        return false;
    }
    
    include $config_file;
    return [
        'host' => DB_HOST,
        'port' => DB_PORT,
        'database' => DB_NAME,
        'username' => DB_USER,
        'password' => DB_PASS
    ];
}

function installDatabase($config) {
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // 读取SQL文件
    $sql = file_get_contents(__DIR__ . '/database/schema.sql');
    
    // 移除CREATE DATABASE和USE语句，因为我们已经连接到指定数据库
    $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
    $sql = preg_replace('/USE\s+.*?;/i', '', $sql);
    
    // 执行SQL语句
    $statements = explode(';', $sql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && !preg_match('/^\s*--/', $statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // 忽略表已存在的错误
                if (strpos($e->getMessage(), 'already exists') === false) {
                    throw $e;
                }
            }
        }
    }
    
    // 更新数据库配置文件
    $new_config = "<?php
// 数据库配置
define('DB_HOST', '{$config['host']}');
define('DB_NAME', '{$config['database']}');
define('DB_USER', '{$config['username']}');
define('DB_PASS', '{$config['password']}');
define('DB_CHARSET', 'utf8mb4');

// 数据库连接类
class Database {
    private static \$instance = null;
    private \$connection;
    
    private function __construct() {
        try {
            \$dsn = \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=\" . DB_CHARSET;
            \$this->connection = new PDO(\$dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException \$e) {
            die(\"数据库连接失败: \" . \$e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::\$instance === null) {
            self::\$instance = new self();
        }
        return self::\$instance;
    }
    
    public function getConnection() {
        return \$this->connection;
    }
    
    public function query(\$sql, \$params = []) {
        try {
            \$stmt = \$this->connection->prepare(\$sql);
            \$stmt->execute(\$params);
            return \$stmt;
        } catch (PDOException \$e) {
            throw new Exception(\"查询执行失败: \" . \$e->getMessage());
        }
    }
    
    public function fetchAll(\$sql, \$params = []) {
        return \$this->query(\$sql, \$params)->fetchAll();
    }
    
    public function fetchOne(\$sql, \$params = []) {
        return \$this->query(\$sql, \$params)->fetch();
    }
    
    public function insert(\$table, \$data) {
        \$fields = array_keys(\$data);
        \$placeholders = ':' . implode(', :', \$fields);
        \$sql = \"INSERT INTO {\$table} (\" . implode(', ', \$fields) . \") VALUES ({\$placeholders})\";
        
        \$this->query(\$sql, \$data);
        return \$this->connection->lastInsertId();
    }
    
    public function update(\$table, \$data, \$where, \$whereParams = []) {
        \$set = [];
        foreach (\$data as \$field => \$value) {
            \$set[] = \"{\$field} = :{\$field}\";
        }
        \$sql = \"UPDATE {\$table} SET \" . implode(', ', \$set) . \" WHERE {\$where}\";
        
        return \$this->query(\$sql, array_merge(\$data, \$whereParams));
    }
    
    public function delete(\$table, \$where, \$params = []) {
        \$sql = \"DELETE FROM {\$table} WHERE {\$where}\";
        return \$this->query(\$sql, \$params);
    }
}
?>";
    
    file_put_contents(__DIR__ . '/config/database.php', $new_config);
    
    // 清理临时配置文件
    $temp_config = __DIR__ . '/config/install_config.php';
    if (file_exists($temp_config)) {
        unlink($temp_config);
    }
}

function createAdmin($config, $username, $password, $email) {
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO admins (username, password, email) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE password = ?, email = ?");
    $stmt->execute([$username, $hashedPassword, $email, $hashedPassword, $email]);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>币种管理系统 - 安装向导</title>
    <style>
        :root {
            --primary-bg: #0a0e27;
            --secondary-bg: #1a1f3a;
            --card-bg: #252b4a;
            --accent-blue: #00d4ff;
            --accent-green: #10b981;
            --accent-red: #ef4444;
            --text-primary: #ffffff;
            --text-secondary: #94a3b8;
            --border-color: #334155;
            --gradient-primary: linear-gradient(135deg, var(--accent-blue), #6366f1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--primary-bg);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .install-container {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 2rem;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
        }

        .install-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .logo i {
            font-size: 2rem;
            color: var(--accent-blue);
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--secondary-bg);
            border: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 0.5rem;
            font-weight: 600;
            position: relative;
        }

        .step.active {
            background: var(--gradient-primary);
            border-color: var(--accent-blue);
        }

        .step.completed {
            background: var(--accent-green);
            border-color: var(--accent-green);
        }

        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 100%;
            width: 30px;
            height: 2px;
            background: var(--border-color);
            transform: translateY(-50%);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            background: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--accent-blue);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: var(--text-primary);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--secondary-bg);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }

        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: var(--accent-green);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--accent-red);
        }

        .info-box {
            background: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .info-box h4 {
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .info-box p {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .button-group {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        .requirements {
            margin-bottom: 2rem;
        }

        .req-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .req-item:last-child {
            border-bottom: none;
        }

        .req-status {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
        }

        .req-status.pass {
            background: var(--accent-green);
        }

        .req-status.fail {
            background: var(--accent-red);
        }
    </style>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <div class="logo">
                <i class="fas fa-coins"></i>
                <span>币种管理系统</span>
            </div>
            <p>安装向导</p>
        </div>

        <div class="step-indicator">
            <div class="step <?= $step >= 1 ? ($step > 1 ? 'completed' : 'active') : '' ?>">1</div>
            <div class="step <?= $step >= 2 ? ($step > 2 ? 'completed' : 'active') : '' ?>">2</div>
            <div class="step <?= $step >= 3 ? ($step > 3 ? 'completed' : 'active') : '' ?>">3</div>
            <div class="step <?= $step >= 4 ? ($step > 4 ? 'completed' : 'active') : '' ?>">4</div>
            <div class="step <?= $step >= 5 ? 'active' : '' ?>">5</div>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <?php switch ($step): case 1: ?>
            <h2>环境检查</h2>
            
            <div class="requirements">
                <div class="req-item">
                    <div class="req-status pass">
                        <i class="fas fa-check"></i>
                    </div>
                    <span>PHP版本 (>= 7.4): <?= PHP_VERSION ?></span>
                </div>
                
                <?php foreach ($required_extensions as $ext): ?>
                <div class="req-item">
                    <div class="req-status <?= extension_loaded($ext) ? 'pass' : 'fail' ?>">
                        <i class="fas fa-<?= extension_loaded($ext) ? 'check' : 'times' ?>"></i>
                    </div>
                    <span>PHP扩展 <?= $ext ?>: <?= extension_loaded($ext) ? '已安装' : '未安装' ?></span>
                </div>
                <?php endforeach; ?>
                
                <div class="req-item">
                    <div class="req-status <?= is_writable(__DIR__ . '/config') ? 'pass' : 'fail' ?>">
                        <i class="fas fa-<?= is_writable(__DIR__ . '/config') ? 'check' : 'times' ?>"></i>
                    </div>
                    <span>配置目录可写: <?= is_writable(__DIR__ . '/config') ? '是' : '否' ?></span>
                </div>
            </div>

            <div class="info-box">
                <h4>系统要求</h4>
                <p>• PHP 7.4 或更高版本<br>
                • MySQL 5.7 或更高版本<br>
                • 支持的Web服务器（Apache/Nginx）<br>
                • 足够的磁盘空间（至少50MB）</p>
            </div>

            <div class="button-group">
                <a href="?step=2" class="btn btn-primary">
                    <i class="fas fa-arrow-right"></i>
                    下一步
                </a>
            </div>
            
        <?php break; case 2: ?>
            <h2>数据库配置</h2>
            
            <form method="POST">
                <div class="form-group">
                    <label for="host">数据库主机</label>
                    <input type="text" id="host" name="host" class="form-input" 
                           value="<?= htmlspecialchars($_POST['host'] ?? 'localhost') ?>" required>
                </div>

                <div class="form-group">
                    <label for="port">端口</label>
                    <input type="number" id="port" name="port" class="form-input" 
                           value="<?= htmlspecialchars($_POST['port'] ?? '3306') ?>" required>
                </div>

                <div class="form-group">
                    <label for="database">数据库名</label>
                    <input type="text" id="database" name="database" class="form-input" 
                           value="<?= htmlspecialchars($_POST['database'] ?? 'coin_management') ?>" required>
                </div>

                <div class="form-group">
                    <label for="username">用户名</label>
                    <input type="text" id="username" name="username" class="form-input" 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">密码</label>
                    <input type="password" id="password" name="password" class="form-input" 
                           value="<?= htmlspecialchars($_POST['password'] ?? '') ?>">
                </div>

                <div class="info-box">
                    <h4>注意事项</h4>
                    <p>• 请确保数据库用户具有创建数据库和表的权限<br>
                    • 如果数据库不存在，系统将自动创建<br>
                    • 建议使用独立的数据库用户</p>
                </div>

                <div class="button-group">
                    <a href="?step=1" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        上一步
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-database"></i>
                        测试连接
                    </button>
                </div>
            </form>
            
        <?php break; case 3: ?>
            <h2>安装数据库</h2>
            
            <div class="info-box">
                <h4>即将执行以下操作</h4>
                <p>• 创建数据库表结构<br>
                • 插入默认系统配置<br>
                • 生成配置文件</p>
            </div>

            <form method="POST" id="installForm">
                <div class="button-group">
                    <a href="?step=2" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        上一步
                    </a>
                    <button type="submit" class="btn btn-primary" id="installBtn">
                        <i class="fas fa-cog"></i>
                        开始安装
                    </button>
                </div>
            </form>
            
            <script>
            document.getElementById('installForm').addEventListener('submit', function() {
                const btn = document.getElementById('installBtn');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 安装中...';
                btn.disabled = true;
            });
            </script>
            
        <?php break; case 4: ?>
            <h2>创建管理员账户</h2>
            
            <form method="POST">
                <div class="form-group">
                    <label for="admin_user">管理员用户名</label>
                    <input type="text" id="admin_user" name="admin_user" class="form-input" 
                           value="<?= htmlspecialchars($_POST['admin_user'] ?? 'admin') ?>" required>
                </div>

                <div class="form-group">
                    <label for="admin_pass">管理员密码</label>
                    <input type="password" id="admin_pass" name="admin_pass" class="form-input" 
                           value="<?= htmlspecialchars($_POST['admin_pass'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="admin_email">管理员邮箱</label>
                    <input type="email" id="admin_email" name="admin_email" class="form-input" 
                           value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>">
                </div>

                <div class="info-box">
                    <h4>安全提示</h4>
                    <p>• 请使用强密码（至少8个字符）<br>
                    • 建议包含大小写字母、数字和特殊字符<br>
                    • 安装完成后请删除此安装文件</p>
                </div>

                <div class="button-group">
                    <a href="?step=3" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        上一步
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-shield"></i>
                        创建账户
                    </button>
                </div>
            </form>
            
        <?php break; case 5: ?>
            <h2>安装完成</h2>
            
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                恭喜！币种管理系统安装成功！
            </div>

            <div class="info-box">
                <h4>接下来的步骤</h4>
                <p>• 删除 install.php 文件<br>
                • 访问管理后台: <a href="admin/login.php" style="color: var(--accent-blue);">admin/login.php</a><br>
                • 登录用户端: <a href="user/login.php" style="color: var(--accent-blue);">user/login.php</a><br>
                • 查看API文档了解如何集成</p>
            </div>

            <div class="info-box">
                <h4>默认账户信息</h4>
                <p>管理员账户: <?= htmlspecialchars($_GET['admin_user'] ?? 'admin') ?><br>
                初始密码: (您刚才设置的密码)</p>
            </div>

            <div class="button-group">
                <a href="admin/login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    进入管理后台
                </a>
                <a href="user/login.php" class="btn btn-secondary">
                    <i class="fas fa-user"></i>
                    用户登录
                </a>
            </div>
            
        <?php break; endswitch; ?>
    </div>
</body>
</html>