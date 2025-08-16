<?php
/**
 * 币种配置管理系统 - 部署前检查脚本
 * 请在部署前运行此脚本，确保环境满足所有要求
 */

header('Content-Type: text/html; charset=UTF-8');

$checks = [];
$errors = [];
$warnings = [];
$success = [];

// PHP版本检查
$phpVersion = phpversion();
if (version_compare($phpVersion, '7.4.0', '>=')) {
    $success[] = "✅ PHP版本 ($phpVersion) 满足要求 (需要 >= 7.4)";
} else {
    $errors[] = "❌ PHP版本 ($phpVersion) 不满足要求 (需要 >= 7.4)";
}

// 必需的PHP扩展
$requiredExtensions = [
    'pdo' => 'PDO扩展',
    'pdo_mysql' => 'PDO MySQL驱动',
    'json' => 'JSON扩展',
    'session' => 'Session扩展',
    'mbstring' => '多字节字符串扩展',
    'openssl' => 'OpenSSL扩展'
];

foreach ($requiredExtensions as $ext => $name) {
    if (extension_loaded($ext)) {
        $success[] = "✅ $name 已安装";
    } else {
        $errors[] = "❌ $name 未安装";
    }
}

// 可选但推荐的扩展
$optionalExtensions = [
    'gd' => 'GD库 (验证码功能)',
    'opcache' => 'OPcache (性能优化)'
];

foreach ($optionalExtensions as $ext => $name) {
    if (extension_loaded($ext)) {
        $success[] = "✅ $name 已安装";
    } else {
        $warnings[] = "⚠️ $name 未安装 (可选，但推荐安装)";
    }
}

// 目录权限检查
$writableDirectories = [
    'config' => '配置目录',
    'uploads' => '上传目录'
];

foreach ($writableDirectories as $dir => $name) {
    if (is_writable(__DIR__ . '/' . $dir)) {
        $success[] = "✅ $name 可写";
    } else {
        $errors[] = "❌ $name 不可写 (请设置权限为 755 或 777)";
    }
}

// 文件完整性检查
$criticalFiles = [
    'install.php' => '安装程序',
    'config/config.php' => '配置文件',
    'database/schema.sql' => '数据库结构',
    'includes/Database.php' => '数据库类',
    'admin/login.php' => '管理员登录',
    'user/login.php' => '用户登录',
    'api/config.php' => 'API接口'
];

foreach ($criticalFiles as $file => $name) {
    if (file_exists(__DIR__ . '/' . $file)) {
        $success[] = "✅ $name 文件存在";
    } else {
        if ($file === 'config/config.php') {
            $warnings[] = "⚠️ $name 不存在 (将在安装时创建)";
        } else {
            $errors[] = "❌ $name 文件缺失";
        }
    }
}

// 数据库连接测试（如果配置文件存在）
if (file_exists(__DIR__ . '/config/config.php')) {
    try {
        require_once __DIR__ . '/config/config.php';
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASS
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $success[] = "✅ 数据库连接成功";
        
        // 检查数据表
        $tables = ['users', 'currencies', 'custom_fields', 'user_configs'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->fetch()) {
                $success[] = "✅ 数据表 $table 存在";
            } else {
                $warnings[] = "⚠️ 数据表 $table 不存在 (需要导入数据库)";
            }
        }
    } catch (Exception $e) {
        $warnings[] = "⚠️ 数据库连接失败: " . $e->getMessage();
    }
}

// 安全检查
$securityChecks = [];

// 检查是否在生产环境
if (ini_get('display_errors') == 1) {
    $warnings[] = "⚠️ display_errors 已开启 (生产环境建议关闭)";
}

// 检查是否使用HTTPS
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    $warnings[] = "⚠️ 未使用HTTPS (强烈建议启用)";
}

// 检查install.php是否存在（部署后应删除）
if (file_exists(__DIR__ . '/install.php') && file_exists(__DIR__ . '/config/config.php')) {
    $warnings[] = "⚠️ install.php 仍然存在 (安装完成后请删除)";
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统部署检查</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
        }
        
        .content {
            padding: 30px;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .check-item {
            padding: 10px;
            margin-bottom: 8px;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        
        .summary {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .summary h3 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .stats {
            display: flex;
            justify-content: space-around;
            margin-top: 15px;
        }
        
        .stat {
            text-align: center;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .action-buttons {
            text-align: center;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            margin: 0 10px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .result-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .result-pass { color: #28a745; }
        .result-warning { color: #ffc107; }
        .result-fail { color: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 币种配置管理系统 - 部署检查</h1>
            <p>系统环境检测与部署准备状态</p>
        </div>
        
        <div class="content">
            <div class="summary">
                <h3>检查结果总览</h3>
                <?php
                $totalChecks = count($success) + count($errors) + count($warnings);
                $canDeploy = empty($errors);
                ?>
                
                <div style="text-align: center;">
                    <?php if ($canDeploy): ?>
                        <div class="result-icon result-pass">✅</div>
                        <h2 style="color: #28a745;">系统可以部署</h2>
                        <p style="color: #666; margin-top: 10px;">所有关键检查已通过，可以继续安装</p>
                    <?php else: ?>
                        <div class="result-icon result-fail">❌</div>
                        <h2 style="color: #dc3545;">系统尚未准备就绪</h2>
                        <p style="color: #666; margin-top: 10px;">请先解决下面的错误后再继续</p>
                    <?php endif; ?>
                </div>
                
                <div class="stats">
                    <div class="stat">
                        <div class="stat-number" style="color: #28a745;"><?php echo count($success); ?></div>
                        <div class="stat-label">通过</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number" style="color: #ffc107;"><?php echo count($warnings); ?></div>
                        <div class="stat-label">警告</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number" style="color: #dc3545;"><?php echo count($errors); ?></div>
                        <div class="stat-label">错误</div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($errors)): ?>
            <div class="section">
                <h2>❌ 必须解决的问题</h2>
                <?php foreach ($errors as $error): ?>
                    <div class="check-item error"><?php echo $error; ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($warnings)): ?>
            <div class="section">
                <h2>⚠️ 建议改进的项目</h2>
                <?php foreach ($warnings as $warning): ?>
                    <div class="check-item warning"><?php echo $warning; ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
            <div class="section">
                <h2>✅ 检查通过的项目</h2>
                <?php foreach ($success as $item): ?>
                    <div class="check-item success"><?php echo $item; ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <div class="action-buttons">
                <?php if ($canDeploy): ?>
                    <?php if (!file_exists(__DIR__ . '/config/config.php')): ?>
                        <a href="install.php" class="btn btn-primary">开始安装</a>
                    <?php else: ?>
                        <a href="index.php" class="btn btn-primary">访问系统</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="deploy_check.php" class="btn btn-secondary">重新检查</a>
                <?php endif; ?>
            </div>
            
            <div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
                <h4 style="color: #333; margin-bottom: 15px;">📋 快速修复指南</h4>
                <ul style="color: #666; line-height: 1.8;">
                    <li><strong>PHP扩展安装：</strong><code>apt-get install php-pdo php-mysql php-mbstring php-gd</code></li>
                    <li><strong>目录权限设置：</strong><code>chmod -R 755 config uploads</code></li>
                    <li><strong>删除安装文件：</strong><code>rm install.php deploy_check.php</code></li>
                    <li><strong>启用HTTPS：</strong>使用Let's Encrypt或其他SSL证书</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>