<?php
require_once 'config.php';

$error = '';
$success = '';

// 处理注册请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $captcha = strtoupper(cleanInput($_POST['captcha'] ?? ''));
    
    // 验证验证码
    if (!isset($_SESSION['captcha_code']) || 
        $captcha !== $_SESSION['captcha_code'] ||
        (time() - $_SESSION['captcha_time']) > 300) {
        $error = '验证码错误或已过期';
    }
    // 验证用户名
    elseif (strlen($username) < 3 || strlen($username) > 20) {
        $error = '用户名长度必须在3-20个字符之间';
    }
    elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = '用户名只能包含字母、数字和下划线';
    }
    // 验证邮箱
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '邮箱格式不正确';
    }
    // 验证密码
    elseif (strlen($password) < 6) {
        $error = '密码长度至少6个字符';
    }
    elseif ($password !== $password2) {
        $error = '两次输入的密码不一致';
    }
    else {
        $db = getDB();
        
        // 检查用户名是否已存在
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $error = '用户名已存在';
        } else {
            // 检查邮箱是否已存在
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $error = '邮箱已被注册';
            } else {
                // 创建用户
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
                
                try {
                    $stmt->execute([$username, $passwordHash, $email]);
                    header('Location: login.php?msg=registered');
                    exit;
                } catch (PDOException $e) {
                    $error = '注册失败，请稍后重试';
                }
            }
        }
    }
    
    // 清除验证码
    unset($_SESSION['captcha_code']);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注册 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .register-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
        }
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-header h2 {
            color: #333;
            font-weight: 600;
        }
        .register-header p {
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
        .btn-register {
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
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            transition: all 0.3s;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h2><i class="fas fa-user-plus"></i> 用户注册</h2>
            <p>创建您的<?php echo SITE_NAME; ?>账号</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="registerForm">
            <div class="mb-3">
                <label for="username" class="form-label">
                    <i class="fas fa-user"></i> 用户名
                </label>
                <input type="text" class="form-control" id="username" name="username" 
                       placeholder="3-20个字符，字母、数字、下划线" required 
                       pattern="[a-zA-Z0-9_]{3,20}" value="<?php echo htmlspecialchars($username ?? ''); ?>">
                <small class="form-text text-muted">用户名将用于登录和显示</small>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope"></i> 邮箱
                </label>
                <input type="email" class="form-control" id="email" name="email" 
                       placeholder="请输入有效的邮箱地址" required 
                       value="<?php echo htmlspecialchars($email ?? ''); ?>">
                <small class="form-text text-muted">用于找回密码和接收通知</small>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">
                    <i class="fas fa-lock"></i> 密码
                </label>
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="至少6个字符" required minlength="6">
                <div class="password-strength" id="passwordStrength"></div>
            </div>
            
            <div class="mb-3">
                <label for="password2" class="form-label">
                    <i class="fas fa-lock"></i> 确认密码
                </label>
                <input type="password" class="form-control" id="password2" name="password2" 
                       placeholder="请再次输入密码" required>
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
                <input type="checkbox" class="form-check-input" id="agree" required>
                <label class="form-check-label" for="agree">
                    我已阅读并同意<a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">服务条款</a>
                </label>
            </div>
            
            <button type="submit" class="btn btn-register">
                <i class="fas fa-user-plus"></i> 立即注册
            </button>
            
            <div class="text-center mt-3">
                <a href="login.php" class="text-decoration-none">
                    已有账号？立即登录
                </a>
            </div>
        </form>
    </div>
    
    <!-- 服务条款模态框 -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">服务条款</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>1. 服务说明</h6>
                    <p>本平台提供XMRig矿工配置管理服务。</p>
                    
                    <h6>2. 用户责任</h6>
                    <p>用户应遵守当地法律法规，合法使用本服务。</p>
                    
                    <h6>3. 隐私保护</h6>
                    <p>我们承诺保护用户隐私，不会泄露用户信息。</p>
                    
                    <h6>4. 免责声明</h6>
                    <p>本平台不对因使用本服务造成的任何损失负责。</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // 密码强度检测
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strength = document.getElementById('passwordStrength');
            let score = 0;
            
            if (password.length >= 8) score++;
            if (password.match(/[a-z]/)) score++;
            if (password.match(/[A-Z]/)) score++;
            if (password.match(/[0-9]/)) score++;
            if (password.match(/[^a-zA-Z0-9]/)) score++;
            
            strength.style.width = (score * 20) + '%';
            
            if (score <= 2) {
                strength.style.background = '#dc3545';
            } else if (score <= 3) {
                strength.style.background = '#ffc107';
            } else {
                strength.style.background = '#28a745';
            }
        });
        
        // 密码确认验证
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const password2 = document.getElementById('password2').value;
            
            if (password !== password2) {
                e.preventDefault();
                alert('两次输入的密码不一致！');
                return false;
            }
        });
    </script>
</body>
</html>