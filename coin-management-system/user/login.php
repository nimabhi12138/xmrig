<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// 如果已登录，重定向到主页
if (Session::isLoggedIn('user')) {
    header('Location: index.php');
    exit;
}

$error = '';
$is_register = isset($_GET['register']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($is_register) {
        // 注册逻辑
        $username = Security::sanitizeInput($_POST['username'] ?? '');
        $email = Security::sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        $validator = new Validator();
        $validator->required('username', $username)
                 ->minLength('username', $username, 3)
                 ->maxLength('username', $username, 50)
                 ->required('email', $email)
                 ->email('email', $email)
                 ->required('password', $password)
                 ->minLength('password', $password, 6);
        
        if ($password !== $confirm_password) {
            $validator->getErrors()['confirm_password'] = '密码确认不匹配';
        }
        
        if (!$validator->hasErrors()) {
            $validator->unique('username', $username, 'users')
                     ->unique('email', $email, 'users');
        }
        
        if ($validator->hasErrors()) {
            $error = '注册失败: ' . implode(', ', array_values($validator->getErrors()));
        } else {
            try {
                $db = Database::getInstance();
                $hashedPassword = Security::hashPassword($password);
                $apiToken = Security::generateToken();
                
                $userId = $db->insert('users', [
                    'username' => $username,
                    'email' => $email,
                    'password' => $hashedPassword,
                    'api_token' => $apiToken
                ]);
                
                // 自动登录
                Session::set('user_id', $userId);
                Session::set('username', $username);
                
                Logger::info("新用户注册并登录: {$username}");
                header('Location: index.php');
                exit;
                
            } catch (Exception $e) {
                Logger::error("用户注册失败: " . $e->getMessage());
                $error = '注册失败，请稍后重试';
            }
        }
    } else {
        // 登录逻辑
        $username = Security::sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = '请输入用户名和密码';
        } else {
            $db = Database::getInstance();
            $user = $db->fetchOne(
                "SELECT id, username, email, password, status FROM users WHERE username = ? OR email = ?",
                [$username, $username]
            );
            
            if ($user && Security::verifyPassword($password, $user['password'])) {
                if ($user['status'] != 1) {
                    $error = '账户已被禁用';
                } else {
                    Session::set('user_id', $user['id']);
                    Session::set('username', $user['username']);
                    
                    Logger::info("用户登录: {$user['username']}");
                    header('Location: index.php');
                    exit;
                }
            } else {
                $error = '用户名或密码错误';
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
    <title><?= $is_register ? '用户注册' : '用户登录' ?> - 币种管理系统</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/user.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
        }
        
        .login-container {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 3rem 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: var(--shadow-lg);
            backdrop-filter: blur(10px);
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
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
            color: var(--text-primary);
        }
        
        .logo i {
            font-size: 2rem;
            color: var(--accent-blue);
            text-shadow: 0 0 20px var(--accent-blue);
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
            padding: 0.875rem;
            background: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            color: var(--text-primary);
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.1);
        }
        
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 0.5rem;
            padding: 0.875rem;
            margin-bottom: 1.5rem;
            color: var(--accent-red);
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .auth-switch {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-secondary);
        }
        
        .auth-switch a {
            color: var(--accent-blue);
            text-decoration: none;
            font-weight: 500;
        }
        
        .auth-switch a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-coins"></i>
                <span>币种管理</span>
            </div>
            <h1><?= $is_register ? '用户注册' : '用户登录' ?></h1>
            <p><?= $is_register ? '创建您的账户' : '请登录您的账户' ?></p>
        </div>

        <?php if ($error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">用户名</label>
                <input type="text" id="username" name="username" class="form-input" 
                       placeholder="请输入用户名" required 
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>

            <?php if ($is_register): ?>
            <div class="form-group">
                <label for="email">邮箱地址</label>
                <input type="email" id="email" name="email" class="form-input" 
                       placeholder="请输入邮箱地址" required 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" class="form-input" 
                       placeholder="请输入密码" required>
            </div>

            <?php if ($is_register): ?>
            <div class="form-group">
                <label for="confirm_password">确认密码</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                       placeholder="请再次输入密码" required>
            </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                <i class="fas fa-<?= $is_register ? 'user-plus' : 'sign-in-alt' ?>"></i>
                <?= $is_register ? '注册账户' : '登录' ?>
            </button>
        </form>

        <div class="auth-switch">
            <?php if ($is_register): ?>
                已有账户？<a href="login.php">立即登录</a>
            <?php else: ?>
                没有账户？<a href="login.php?register=1">立即注册</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>