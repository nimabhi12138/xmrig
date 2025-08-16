<?php
// 安装向导
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($step == 2) {
        // 保存数据库配置
        $db_host = $_POST['db_host'];
        $db_name = $_POST['db_name'];
        $db_user = $_POST['db_user'];
        $db_pass = $_POST['db_pass'];
        $site_url = $_POST['site_url'];
        
        // 测试数据库连接
        try {
            $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // 创建配置文件
            $config_content = "<?php
// 数据库配置
define('DB_HOST', '$db_host');
define('DB_NAME', '$db_name');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');
define('DB_CHARSET', 'utf8mb4');

// 系统配置
define('SITE_URL', '$site_url');
define('SITE_NAME', '币种配置管理系统');
define('SECRET_KEY', '" . bin2hex(random_bytes(32)) . "');

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session配置
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}";
            
            file_put_contents('config/config.php', $config_content);
            
            // 导入数据库
            $sql = file_get_contents('database/schema.sql');
            $pdo->exec("CREATE DATABASE IF NOT EXISTS $db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE $db_name");
            
            // 分割并执行SQL语句
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $pdo->exec($statement);
                }
            }
            
            header('Location: install.php?step=3');
            exit;
        } catch (Exception $e) {
            $error = '数据库连接失败：' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装向导 - 币种配置管理系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #00d4ff;
            --secondary-color: #0099cc;
            --dark-bg: #0a0e27;
            --card-bg: #151935;
            --border-color: #2a3f5f;
        }
        
        body {
            background: linear-gradient(135deg, #0a0e27 0%, #151935 100%);
            color: #fff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .install-container {
            max-width: 600px;
            width: 100%;
            padding: 20px;
        }
        
        .install-card {
            background: rgba(21, 25, 53, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        
        .install-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .install-header h2 {
            color: var(--primary-color);
            font-size: 32px;
            font-weight: 300;
            letter-spacing: 3px;
        }
        
        .progress {
            height: 30px;
            background: rgba(42, 63, 95, 0.5);
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .progress-bar {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            border-radius: 15px;
            font-size: 14px;
            line-height: 30px;
        }
        
        .form-control {
            background: rgba(42, 63, 95, 0.5);
            border: 1px solid var(--border-color);
            color: #fff;
            border-radius: 10px;
            padding: 12px;
        }
        
        .form-control:focus {
            background: rgba(42, 63, 95, 0.7);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 212, 255, 0.25);
            color: #fff;
        }
        
        .btn-install {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            border: none;
            color: #fff;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: all 0.3s;
        }
        
        .btn-install:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 212, 255, 0.4);
            color: #fff;
        }
        
        .alert {
            border-radius: 10px;
        }
        
        .requirement-item {
            padding: 10px;
            margin-bottom: 10px;
            background: rgba(42, 63, 95, 0.3);
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .check-ok {
            color: #00ff00;
        }
        
        .check-fail {
            color: #ff0000;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-card">
            <div class="install-header">
                <i class="bi bi-currency-bitcoin" style="font-size: 60px; color: var(--primary-color);"></i>
                <h2>安装向导</h2>
                <p>币种配置管理系统</p>
            </div>
            
            <!-- 进度条 -->
            <div class="progress">
                <div class="progress-bar" style="width: <?php echo $step * 33.33; ?>%;">
                    步骤 <?php echo $step; ?> / 3
                </div>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($step == 1): ?>
            <!-- 步骤1：环境检查 -->
            <h4 class="mb-4">环境检查</h4>
            
            <?php
            $requirements = [
                'PHP版本 >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
                'PDO扩展' => extension_loaded('pdo'),
                'PDO MySQL扩展' => extension_loaded('pdo_mysql'),
                'JSON扩展' => extension_loaded('json'),
                'Session扩展' => extension_loaded('session'),
                'GD库扩展' => extension_loaded('gd'),
                'config目录可写' => is_writable('config')
            ];
            
            $all_pass = true;
            foreach ($requirements as $name => $pass):
                if (!$pass) $all_pass = false;
            ?>
            <div class="requirement-item">
                <span><?php echo $name; ?></span>
                <span class="<?php echo $pass ? 'check-ok' : 'check-fail'; ?>">
                    <i class="bi bi-<?php echo $pass ? 'check-circle' : 'x-circle'; ?>"></i>
                    <?php echo $pass ? '通过' : '失败'; ?>
                </span>
            </div>
            <?php endforeach; ?>
            
            <?php if ($all_pass): ?>
            <div class="text-center mt-4">
                <a href="install.php?step=2" class="btn btn-install">
                    下一步 <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <?php else: ?>
            <div class="alert alert-warning mt-4">
                请先解决以上问题后再继续安装
            </div>
            <?php endif; ?>
            
            <?php elseif ($step == 2): ?>
            <!-- 步骤2：数据库配置 -->
            <h4 class="mb-4">数据库配置</h4>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">数据库主机</label>
                    <input type="text" class="form-control" name="db_host" value="localhost" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">数据库名称</label>
                    <input type="text" class="form-control" name="db_name" value="crypto_config" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">数据库用户名</label>
                    <input type="text" class="form-control" name="db_user" value="root" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">数据库密码</label>
                    <input type="password" class="form-control" name="db_pass">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">网站URL</label>
                    <input type="text" class="form-control" name="site_url" 
                           value="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']); ?>" required>
                    <small class="text-muted">请确保URL正确，不要以/结尾</small>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="install.php?step=1" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> 上一步
                    </a>
                    <button type="submit" class="btn btn-install">
                        安装 <i class="bi bi-database"></i>
                    </button>
                </div>
            </form>
            
            <?php elseif ($step == 3): ?>
            <!-- 步骤3：安装完成 -->
            <div class="text-center">
                <i class="bi bi-check-circle" style="font-size: 80px; color: #00ff00;"></i>
                <h4 class="mt-4 mb-4">安装完成！</h4>
                
                <div class="alert alert-success">
                    系统已成功安装，您可以使用以下信息登录
                </div>
                
                <div class="requirement-item">
                    <span>管理员账号：</span>
                    <span>admin</span>
                </div>
                
                <div class="requirement-item">
                    <span>管理员密码：</span>
                    <span>admin123</span>
                </div>
                
                <div class="alert alert-warning mt-4">
                    <i class="bi bi-exclamation-triangle"></i> 
                    请立即登录后台修改默认密码！
                </div>
                
                <div class="mt-4">
                    <a href="index.php" class="btn btn-install me-2">
                        访问首页 <i class="bi bi-house"></i>
                    </a>
                    <a href="admin/login.php" class="btn btn-install">
                        管理后台 <i class="bi bi-shield-lock"></i>
                    </a>
                </div>
                
                <div class="alert alert-danger mt-4">
                    <i class="bi bi-trash"></i> 
                    安装完成后，请删除 install.php 文件以确保安全！
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>