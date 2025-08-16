<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>币种配置管理系统</title>
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
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #0a0e27 0%, #151935 100%);
            color: #fff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* 动画背景 */
        .bg-animation {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 0;
            opacity: 0.5;
        }
        
        .bg-animation::before {
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
        
        /* 导航栏 */
        .navbar {
            background: rgba(21, 25, 53, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            padding: 20px 0;
            position: relative;
            z-index: 1000;
        }
        
        .navbar-brand {
            color: var(--primary-color) !important;
            font-size: 24px;
            font-weight: 300;
            letter-spacing: 3px;
        }
        
        .navbar-brand i {
            margin-right: 10px;
        }
        
        /* 主要内容 */
        .hero-section {
            min-height: calc(100vh - 80px);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 1;
        }
        
        .hero-content {
            text-align: center;
            max-width: 800px;
            padding: 0 20px;
        }
        
        .hero-title {
            font-size: 48px;
            font-weight: 300;
            margin-bottom: 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: glow 2s ease-in-out infinite alternate;
        }
        
        @keyframes glow {
            from { filter: drop-shadow(0 0 10px rgba(0, 212, 255, 0.5)); }
            to { filter: drop-shadow(0 0 20px rgba(0, 212, 255, 0.8)); }
        }
        
        .hero-subtitle {
            color: #8892b0;
            font-size: 20px;
            margin-bottom: 50px;
        }
        
        .action-cards {
            display: flex;
            gap: 30px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 50px;
        }
        
        .action-card {
            background: rgba(21, 25, 53, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 40px 30px;
            width: 300px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--primary-color), transparent);
            animation: scan 3s linear infinite;
        }
        
        @keyframes scan {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .action-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 212, 255, 0.3);
            border-color: var(--primary-color);
        }
        
        .action-card-icon {
            font-size: 48px;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .action-card-title {
            font-size: 24px;
            margin-bottom: 15px;
            color: #fff;
        }
        
        .action-card-desc {
            color: #8892b0;
            margin-bottom: 25px;
        }
        
        .btn-action {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            border: none;
            color: #fff;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 212, 255, 0.4);
            color: #fff;
        }
        
        /* 特性展示 */
        .features-section {
            padding: 80px 0;
            position: relative;
            z-index: 1;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .feature-item {
            text-align: center;
            padding: 30px;
            background: rgba(21, 25, 53, 0.5);
            border-radius: 15px;
            border: 1px solid var(--border-color);
            transition: all 0.3s;
        }
        
        .feature-item:hover {
            background: rgba(21, 25, 53, 0.8);
            transform: translateY(-5px);
        }
        
        .feature-icon {
            font-size: 36px;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .feature-title {
            font-size: 18px;
            margin-bottom: 10px;
            color: #fff;
        }
        
        .feature-desc {
            color: #8892b0;
            font-size: 14px;
        }
        
        /* 页脚 */
        .footer {
            background: rgba(10, 14, 39, 0.95);
            border-top: 1px solid var(--border-color);
            padding: 30px 0;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        
        .footer p {
            color: #8892b0;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="bg-animation"></div>
    
    <!-- 导航栏 -->
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-currency-bitcoin"></i>
                CRYPTO CONFIG
            </a>
        </div>
    </nav>
    
    <!-- 主要内容 -->
    <section class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">币种配置管理系统</h1>
            <p class="hero-subtitle">现代化的加密货币配置管理平台，灵活、安全、高效</p>
            
            <div class="action-cards">
                <!-- 管理员入口 -->
                <div class="action-card">
                    <i class="bi bi-shield-lock action-card-icon"></i>
                    <h3 class="action-card-title">管理后台</h3>
                    <p class="action-card-desc">管理币种、配置字段、查看用户数据</p>
                    <a href="admin/login.php" class="btn-action">
                        <i class="bi bi-box-arrow-in-right"></i> 管理员登录
                    </a>
                </div>
                
                <!-- 用户入口 -->
                <div class="action-card">
                    <i class="bi bi-person-circle action-card-icon"></i>
                    <h3 class="action-card-title">用户中心</h3>
                    <p class="action-card-desc">配置币种参数、获取API接口</p>
                    <a href="user/login.php" class="btn-action">
                        <i class="bi bi-box-arrow-in-right"></i> 用户登录
                    </a>
                </div>
                
                <!-- 注册入口 -->
                <div class="action-card">
                    <i class="bi bi-person-plus action-card-icon"></i>
                    <h3 class="action-card-title">新用户注册</h3>
                    <p class="action-card-desc">创建账户，开始使用系统</p>
                    <a href="user/register.php" class="btn-action">
                        <i class="bi bi-pencil-square"></i> 立即注册
                    </a>
                </div>
            </div>
        </div>
    </section>
    
    <!-- 特性展示 -->
    <section class="features-section">
        <div class="container">
            <h2 class="text-center mb-5" style="color: var(--primary-color);">系统特性</h2>
            <div class="features-grid">
                <div class="feature-item">
                    <i class="bi bi-palette feature-icon"></i>
                    <h4 class="feature-title">科技感UI</h4>
                    <p class="feature-desc">深色主题、流光动画、现代化设计</p>
                </div>
                <div class="feature-item">
                    <i class="bi bi-gear feature-icon"></i>
                    <h4 class="feature-title">灵活配置</h4>
                    <p class="feature-desc">自定义币种模板和动态字段</p>
                </div>
                <div class="feature-item">
                    <i class="bi bi-shield-check feature-icon"></i>
                    <h4 class="feature-title">安全可靠</h4>
                    <p class="feature-desc">令牌验证、密码加密、权限控制</p>
                </div>
                <div class="feature-item">
                    <i class="bi bi-cloud feature-icon"></i>
                    <h4 class="feature-title">API接口</h4>
                    <p class="feature-desc">RESTful API提供配置数据</p>
                </div>
                <div class="feature-item">
                    <i class="bi bi-phone feature-icon"></i>
                    <h4 class="feature-title">响应式设计</h4>
                    <p class="feature-desc">支持各种设备完美访问</p>
                </div>
                <div class="feature-item">
                    <i class="bi bi-lightning feature-icon"></i>
                    <h4 class="feature-title">高性能</h4>
                    <p class="feature-desc">优化的数据库查询和缓存机制</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- 页脚 -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Crypto Config System. All rights reserved.</p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>