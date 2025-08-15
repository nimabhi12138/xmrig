<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// 如果已登录，重定向到仪表盘
if (Session::isLoggedIn('admin')) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = Security::sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = '请输入用户名和密码';
    } else {
        $db = Database::getInstance();
        $admin = $db->fetchOne(
            "SELECT id, username, email, password FROM admins WHERE username = ?",
            [$username]
        );
        
        if ($admin && Security::verifyPassword($password, $admin['password'])) {
            Session::set('admin_id', $admin['id']);
            Session::set('admin_username', $admin['username']);
            
            Logger::info("管理员登录: {$admin['username']}");
            header('Location: index.php');
            exit;
        } else {
            $error = '用户名或密码错误';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录 - 币种管理系统</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-bg: #0a0e27;
            --secondary-bg: #1a1f3a;
            --card-bg: #252b4a;
            --accent-blue: #00d4ff;
            --accent-purple: #6366f1;
            --text-primary: #ffffff;
            --text-secondary: #94a3b8;
            --border-color: #334155;
            --gradient-primary: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
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
            position: relative;
            overflow: hidden;
        }

        /* 动态背景 */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(0, 212, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(99, 102, 241, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(16, 185, 129, 0.05) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
            animation: gradientShift 15s ease infinite;
        }

        @keyframes gradientShift {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        .login-container {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 3rem 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: var(--shadow-lg);
            position: relative;
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

        .login-title {
            font-size: 1.25rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            font-size: 0.875rem;
            color: var(--text-secondary);
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

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            transition: color 0.3s ease;
        }

        .form-input {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 3rem;
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

        .form-input:focus + i {
            color: var(--accent-blue);
        }

        .login-btn {
            width: 100%;
            padding: 0.875rem;
            background: var(--gradient-primary);
            color: var(--text-primary);
            border: none;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 25px rgba(0, 212, 255, 0.4);
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 0.5rem;
            padding: 0.875rem;
            margin-bottom: 1.5rem;
            color: #ef4444;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .footer-text {
            text-align: center;
            margin-top: 2rem;
            color: var(--text-secondary);
            font-size: 0.75rem;
        }

        /* 响应式 */
        @media (max-width: 480px) {
            .login-container {
                margin: 1rem;
                padding: 2rem 1.5rem;
            }
        }

        /* 加载动画 */
        .loading {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .login-btn.loading .loading {
            display: inline-block;
        }

        .login-btn.loading .btn-text {
            display: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-shield-alt"></i>
                <span>管理后台</span>
            </div>
            <h1 class="login-title">管理员登录</h1>
            <p class="login-subtitle">请输入您的管理员凭据以继续</p>
        </div>

        <?php if ($error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <label for="username">用户名</label>
                <div class="input-group">
                    <input type="text" id="username" name="username" class="form-input" 
                           placeholder="请输入管理员用户名" required 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    <i class="fas fa-user"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="password">密码</label>
                <div class="input-group">
                    <input type="password" id="password" name="password" class="form-input" 
                           placeholder="请输入密码" required>
                    <i class="fas fa-lock"></i>
                </div>
            </div>

            <button type="submit" class="login-btn" id="loginBtn">
                <span class="btn-text">
                    <i class="fas fa-sign-in-alt"></i>
                    登录
                </span>
                <div class="loading"></div>
            </button>
        </form>

        <div class="footer-text">
            <p>&copy; 2024 币种管理系统. 保留所有权利.</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const loginBtn = document.getElementById('loginBtn');
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');

            // 表单提交处理
            form.addEventListener('submit', function(e) {
                const username = usernameInput.value.trim();
                const password = passwordInput.value.trim();

                if (!username || !password) {
                    e.preventDefault();
                    showError('请输入用户名和密码');
                    return;
                }

                // 显示加载状态
                loginBtn.classList.add('loading');
                loginBtn.disabled = true;
            });

            // 输入框焦点效果
            [usernameInput, passwordInput].forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                });

                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
            });

            // 键盘事件
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    if (document.activeElement === usernameInput) {
                        passwordInput.focus();
                    } else if (document.activeElement === passwordInput) {
                        form.submit();
                    }
                }
            });

            // 错误提示函数
            function showError(message) {
                // 移除现有错误消息
                const existingError = document.querySelector('.error-message');
                if (existingError) {
                    existingError.remove();
                }

                // 创建新的错误消息
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.innerHTML = `
                    <i class="fas fa-exclamation-circle"></i>
                    ${message}
                `;

                // 插入到表单前面
                form.parentNode.insertBefore(errorDiv, form);

                // 3秒后自动消失
                setTimeout(() => {
                    if (errorDiv.parentNode) {
                        errorDiv.remove();
                    }
                }, 3000);
            }

            // 自动聚焦到用户名输入框
            usernameInput.focus();
        });
    </script>
</body>
</html>