<?php
/**
 * 数据库连接测试工具 - 为爷爷手术费用项目
 * 快速诊断数据库连接问题
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>数据库连接测试 - 爷爷手术费用项目</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #17a2b8; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type='text'], input[type='password'] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
    </style>
</head>
<body>";

echo "<h1>🏥 数据库连接测试 - 为爷爷手术费用</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['host'] ?? 'localhost');
    $port = trim($_POST['port'] ?? '3306');
    $database = trim($_POST['database'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    echo "<h2>🔍 连接测试结果</h2>";
    
    // 验证输入
    $errors = [];
    if (empty($host)) $errors[] = "主机地址不能为空";
    if (empty($database)) $errors[] = "数据库名不能为空";
    if (empty($username)) $errors[] = "用户名不能为空";
    
    if (!empty($errors)) {
        echo "<div class='error'><strong>❌ 输入错误：</strong><br>" . implode("<br>", $errors) . "</div>";
    } else {
        echo "<div class='info'><strong>📋 连接信息：</strong><br>
              主机: {$host}<br>
              端口: {$port}<br>
              数据库: {$database}<br>
              用户名: {$username}<br>
              密码: " . (empty($password) ? '(空)' : str_repeat('*', strlen($password))) . "</div>";
        
        try {
            // 测试连接 (不指定数据库)
            echo "<div class='info'>🔄 步骤1: 连接到MySQL服务器...</div>";
            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            echo "<div class='success'>✅ 成功连接到MySQL服务器！</div>";
            
            // 测试数据库权限
            echo "<div class='info'>🔄 步骤2: 检查用户权限...</div>";
            $stmt = $pdo->query("SHOW GRANTS FOR CURRENT_USER()");
            $grants = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "<div class='success'>✅ 用户权限获取成功！</div>";
            echo "<div class='code'><strong>用户权限：</strong><br>" . implode("<br>", $grants) . "</div>";
            
            // 检查数据库是否存在
            echo "<div class='info'>🔄 步骤3: 检查数据库 '{$database}'...</div>";
            $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
            $stmt->execute([$database]);
            $db_exists = $stmt->fetchColumn();
            
            if ($db_exists) {
                echo "<div class='success'>✅ 数据库 '{$database}' 已存在！</div>";
                
                // 连接到指定数据库
                $dsn_with_db = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
                $pdo_db = new PDO($dsn_with_db, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                echo "<div class='success'>✅ 成功连接到数据库 '{$database}'！</div>";
                
                // 检查表
                $stmt = $pdo_db->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                if (!empty($tables)) {
                    echo "<div class='info'><strong>📊 现有数据表：</strong><br>" . implode(", ", $tables) . "</div>";
                } else {
                    echo "<div class='info'>📋 数据库为空，准备创建数据表。</div>";
                }
                
            } else {
                echo "<div class='info'>📋 数据库 '{$database}' 不存在，尝试创建...</div>";
                
                // 尝试创建数据库
                $pdo->exec("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                echo "<div class='success'>✅ 成功创建数据库 '{$database}'！</div>";
            }
            
            echo "<div class='success'><strong>🎉 数据库连接测试完全成功！</strong><br>
                  现在可以继续安装程序了！</div>";
            
            echo "<div class='info'><strong>📝 请在安装程序中使用以下配置：</strong></div>";
            echo "<div class='code'>
                  数据库主机: {$host}<br>
                  数据库端口: {$port}<br>
                  数据库名: {$database}<br>
                  用户名: {$username}<br>
                  密码: [您输入的密码]
                  </div>";
                  
        } catch (PDOException $e) {
            $error_msg = $e->getMessage();
            $error_code = $e->getCode();
            
            echo "<div class='error'><strong>❌ 数据库连接失败</strong></div>";
            echo "<div class='error'><strong>错误代码：</strong> {$error_code}</div>";
            echo "<div class='error'><strong>错误信息：</strong> {$error_msg}</div>";
            
            // 根据错误类型提供解决方案
            if (strpos($error_msg, 'Access denied') !== false) {
                echo "<div class='error'><strong>🔧 解决方案：</strong><br>
                      1. 检查用户名和密码是否正确<br>
                      2. 确认该用户有足够的数据库权限<br>
                      3. 如果使用面板，请从面板重新创建数据库用户<br>
                      4. 联系主机商确认数据库配置</div>";
            } elseif (strpos($error_msg, "Can't connect") !== false) {
                echo "<div class='error'><strong>🔧 解决方案：</strong><br>
                      1. 检查数据库服务是否启动<br>
                      2. 确认主机地址和端口是否正确<br>
                      3. 检查防火墙设置<br>
                      4. 联系主机商确认数据库服务状态</div>";
            } elseif (strpos($error_msg, 'Unknown database') !== false) {
                echo "<div class='error'><strong>🔧 解决方案：</strong><br>
                      1. 数据库不存在，请先创建数据库<br>
                      2. 或者确认数据库名称拼写正确<br>
                      3. 检查用户是否有创建数据库的权限</div>";
            }
        }
    }
    
} else {
    echo "<div class='info'><strong>💡 使用说明：</strong><br>
          请填写您的数据库连接信息，系统将测试连接并提供详细的诊断信息。</div>";
}

echo "<h2>🔧 数据库连接测试</h2>";
echo "<form method='POST'>
        <div class='form-group'>
            <label>数据库主机：</label>
            <input type='text' name='host' value='" . ($_POST['host'] ?? 'localhost') . "' placeholder='localhost 或 IP地址'>
        </div>
        <div class='form-group'>
            <label>数据库端口：</label>
            <input type='text' name='port' value='" . ($_POST['port'] ?? '3306') . "' placeholder='3306'>
        </div>
        <div class='form-group'>
            <label>数据库名：</label>
            <input type='text' name='database' value='" . ($_POST['database'] ?? 'coin_management') . "' placeholder='coin_management'>
        </div>
        <div class='form-group'>
            <label>用户名：</label>
            <input type='text' name='username' value='" . ($_POST['username'] ?? '') . "' placeholder='数据库用户名'>
        </div>
        <div class='form-group'>
            <label>密码：</label>
            <input type='password' name='password' value='' placeholder='数据库密码'>
        </div>
        <button type='submit'>🔍 测试连接</button>
      </form>";

echo "<div class='info'><strong>💪 为爷爷手术费用加油！</strong><br>
      如果遇到问题，请根据上面的诊断信息进行修复，或联系技术支持。</div>";

echo "</body></html>";
?>