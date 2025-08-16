<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $db = Database::getInstance();
    $stmt = $db->query("SELECT * FROM users WHERE username = :username AND is_admin = 1", ['username' => $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = '用户名或密码错误';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录 - <?php echo SITE_NAME; ?></title>
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
        
        .login-container {
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 1;
        }
        
        .login-card {
            background: rgba(21, 25, 53, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
        }
        
        .login-card::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color), var(--primary-color));
            border-radius: 20px;
            opacity: 0;
            z-index: -1;
            transition: opacity 0.3s;
        }
        
        .login-card:hover::before {
            opacity: 1;
            animation: border-glow 3s linear infinite;
        }
        
        @keyframes border-glow {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .login-header h2 {
            color: var(--primary-color);
            font-size: 32px;
            font-weight: 300;
            letter-spacing: 3px;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #8892b0;
            font-size: 14px;
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
        
        .btn-login {
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
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 212, 255, 0.4);
        }
        
        .alert {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.5);
            color: #ff6b6b;
            border-radius: 10px;
        }
        
        .logo-icon {
            font-size: 60px;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="bi bi-currency-bitcoin logo-icon"></i>
                <h2>CRYPTO ADMIN</h2>
                <p>管理员登录</p>
            </div>
            
            <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="input-group">
                    <i class="bi bi-person input-icon"></i>
                    <input type="text" class="form-control" name="username" placeholder="用户名" required autofocus>
                </div>
                
                <div class="input-group">
                    <i class="bi bi-lock input-icon"></i>
                    <input type="password" class="form-control" name="password" placeholder="密码" required>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="bi bi-shield-lock"></i> 安全登录
                </button>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>