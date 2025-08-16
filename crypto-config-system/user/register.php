<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // 验证
    if (strlen($username) < 3) {
        $error = '用户名至少需要3个字符';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '请输入有效的邮箱地址';
    } elseif (strlen($password) < 6) {
        $error = '密码至少需要6个字符';
    } elseif ($password !== $confirm_password) {
        $error = '两次输入的密码不一致';
    } else {
        $db = Database::getInstance();
        
        // 检查用户名是否存在
        $stmt = $db->query("SELECT id FROM users WHERE username = :username", ['username' => $username]);
        if ($stmt->fetch()) {
            $error = '用户名已存在';
        } else {
            // 检查邮箱是否存在
            $stmt = $db->query("SELECT id FROM users WHERE email = :email", ['email' => $email]);
            if ($stmt->fetch()) {
                $error = '邮箱已被注册';
            } else {
                // 创建用户
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $api_token = bin2hex(random_bytes(32));
                
                try {
                    $user_id = $db->insert('users', [
                        'username' => $username,
                        'email' => $email,
                        'password' => $hashed_password,
                        'api_token' => $api_token,
                        'is_admin' => 0
                    ]);
                    
                    $success = '注册成功！正在跳转到登录页面...';
                    header('refresh:2;url=login.php');
                } catch (Exception $e) {
                    $error = '注册失败，请稍后重试';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户注册 - <?php echo SITE_NAME; ?></title>
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
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(0, 212, 255, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: grid-move 20s linear infinite;
        }
        
        @keyframes grid-move {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }
        
        .register-container {
            width: 100%;
            max-width: 500px;
            position: relative;
            z-index: 1;
            margin: 20px;
        }
        
        .register-card {
            background: rgba(21, 25, 53, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .register-header h2 {
            color: var(--primary-color);
            font-size: 32px;
            font-weight: 300;
            letter-spacing: 3px;
            margin-bottom: 10px;
        }
        
        .form-control {
            background: rgba(42, 63, 95, 0.5);
            border: 1px solid var(--border-color);
            color: #fff;
            border-radius: 10px;
            padding: 15px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            background: rgba(42, 63, 95, 0.7);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 212, 255, 0.25);
            color: #fff;
        }
        
        .form-control::placeholder {
            color: #8892b0;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 25px;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            z-index: 1;
        }
        
        .input-group .form-control {
            padding-left: 45px;
        }
        
        .btn-register {
            width: 100%;
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            border: none;
            color: #fff;
            padding: 15px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 212, 255, 0.4);
        }
        
        .alert {
            border-radius: 10px;
            padding: 15px;
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.5);
            color: #ff6b6b;
        }
        
        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid rgba(40, 167, 69, 0.5);
            color: #28a745;
        }
        
        .logo-icon {
            font-size: 60px;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #8892b0;
        }
        
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .login-link a:hover {
            color: #fff;
        }
        
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            transition: all 0.3s;
        }
        
        .strength-weak { background: #ff4444; width: 33%; }
        .strength-medium { background: #ffaa00; width: 66%; }
        .strength-strong { background: #00ff00; width: 100%; }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <i class="bi bi-currency-bitcoin logo-icon"></i>
                <h2>创建账户</h2>
                <p>加入币种配置管理系统</p>
            </div>
            
            <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" id="registerForm">
                <div class="input-group">
                    <i class="bi bi-person input-icon"></i>
                    <input type="text" class="form-control" name="username" placeholder="用户名" required>
                </div>
                
                <div class="input-group">
                    <i class="bi bi-envelope input-icon"></i>
                    <input type="email" class="form-control" name="email" placeholder="邮箱地址" required>
                </div>
                
                <div class="input-group">
                    <i class="bi bi-lock input-icon"></i>
                    <input type="password" class="form-control" name="password" id="password" placeholder="密码" required>
                    <div class="password-strength" id="passwordStrength" style="display: none;"></div>
                </div>
                
                <div class="input-group">
                    <i class="bi bi-lock-fill input-icon"></i>
                    <input type="password" class="form-control" name="confirm_password" placeholder="确认密码" required>
                </div>
                
                <button type="submit" class="btn btn-register">
                    <i class="bi bi-person-plus"></i> 注册账户
                </button>
            </form>
            
            <div class="login-link">
                已有账户？ <a href="login.php">立即登录</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 密码强度检测
        document.getElementById('password').addEventListener('input', function(e) {
            const password = e.target.value;
            const strengthBar = document.getElementById('passwordStrength');
            
            if (password.length > 0) {
                strengthBar.style.display = 'block';
                
                if (password.length < 6) {
                    strengthBar.className = 'password-strength strength-weak';
                } else if (password.length < 10 || !/[A-Z]/.test(password) || !/[0-9]/.test(password)) {
                    strengthBar.className = 'password-strength strength-medium';
                } else {
                    strengthBar.className = 'password-strength strength-strong';
                }
            } else {
                strengthBar.style.display = 'none';
            }
        });
    </script>
</body>
</html>