<?php
require_once 'config.php';

$error = '';
$success = '';

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $captcha = strtoupper(cleanInput($_POST['captcha'] ?? ''));
    
    // 验证验证码
    if (!isset($_SESSION['captcha_code']) || 
        $captcha !== $_SESSION['captcha_code'] ||
        (time() - $_SESSION['captcha_time']) > 300) {
        $error = '验证码错误或已过期';
    } else {
        // 查询用户
        $db = getDB();
        $stmt = $db->prepare("SELECT id, username, password, email, is_admin, status FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] == 0) {
                $error = '账号已被禁用';
            } else {
                // 登录成功
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'];
                $_SESSION['last_activity'] = time();
                
                // 记录登录日志
                $stmt = $db->prepare("INSERT INTO login_logs (user_id, username, ip, user_agent, status) VALUES (?, ?, ?, ?, 1)");
                $stmt->execute([$user['id'], $user['username'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
                
                // 跳转到相应页面
                if ($user['is_admin'] == 1) {
                    header('Location: admin/index.php');
                } else {
                    header('Location: user/index.php');
                }
                exit;
            }
        } else {
            $error = '用户名或密码错误';
            
            // 记录失败日志
            $stmt = $db->prepare("INSERT INTO login_logs (username, ip, user_agent, status) VALUES (?, ?, ?, 0)");
            $stmt->execute([$username, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
        }
    }
    
    // 清除验证码
    unset($_SESSION['captcha_code']);
}

// 检查消息
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'timeout':
            $error = '会话已超时，请重新登录';
            break;
        case 'registered':
            $success = '注册成功，请登录';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h2 {
            color: #333;
            font-weight: 600;
        }
        .login-header p {
            color: #666;
            margin-top: 10px;
        }
        .captcha-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .captcha-img {
            cursor: pointer;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px;
            font-size: 16px;
            font-weight: 500;
            border-radius: 8px;
            width: 100%;
            transition: transform 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2><i class="fas fa-cube"></i> <?php echo SITE_NAME; ?></h2>
            <p>请登录您的账号</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">
                    <i class="fas fa-user"></i> 用户名/邮箱
                </label>
                <input type="text" class="form-control" id="username" name="username" 
                       placeholder="请输入用户名或邮箱" required>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">
                    <i class="fas fa-lock"></i> 密码
                </label>
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="请输入密码" required>
            </div>
            
            <div class="mb-3">
                <label for="captcha" class="form-label">
                    <i class="fas fa-shield-alt"></i> 验证码
                </label>
                <div class="captcha-group">
                    <input type="text" class="form-control" id="captcha" name="captcha" 
                           placeholder="请输入验证码" required maxlength="4">
                    <img src="captcha.php" alt="验证码" class="captcha-img" 
                         onclick="this.src='captcha.php?t='+Math.random()" 
                         title="点击刷新验证码">
                </div>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember">
                <label class="form-check-label" for="remember">
                    记住我
                </label>
            </div>
            
            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt"></i> 登录
            </button>
            
            <div class="text-center mt-3">
                <a href="register.php" class="text-decoration-none">
                    还没有账号？立即注册
                </a>
            </div>
        </form>
    </div>
    
    <script src="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>