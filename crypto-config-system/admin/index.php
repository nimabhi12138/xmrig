<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

// 检查管理员权限
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台 - <?php echo SITE_NAME; ?></title>
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
        }
        
        .sidebar {
            background: rgba(21, 25, 53, 0.95);
            backdrop-filter: blur(10px);
            border-right: 1px solid var(--border-color);
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .sidebar .nav-link {
            color: #8892b0;
            padding: 12px 20px;
            transition: all 0.3s;
            border-left: 3px solid transparent;
            margin: 5px 0;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: var(--primary-color);
            background: rgba(0, 212, 255, 0.1);
            border-left-color: var(--primary-color);
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        .main-content {
            padding: 30px;
        }
        
        .tech-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .tech-card::before {
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
        
        .tech-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 212, 255, 0.2);
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            border-radius: 15px;
            padding: 20px;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card .icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 40px;
            opacity: 0.3;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .page-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .page-header h1 {
            color: var(--primary-color);
            font-weight: 300;
            letter-spacing: 2px;
        }
        
        .btn-tech {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            border: none;
            color: #fff;
            padding: 10px 25px;
            border-radius: 25px;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
        }
        
        .btn-tech:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 212, 255, 0.4);
            color: #fff;
        }
        
        .logo {
            text-align: center;
            padding: 20px;
            margin-bottom: 30px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .logo h2 {
            color: var(--primary-color);
            font-size: 24px;
            font-weight: 300;
            letter-spacing: 3px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- 侧边栏 -->
            <div class="col-md-2 sidebar">
                <div class="logo">
                    <h2><i class="bi bi-currency-bitcoin"></i> CRYPTO</h2>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link active" href="index.php">
                        <i class="bi bi-speedometer2"></i> 仪表板
                    </a>
                    <a class="nav-link" href="currencies.php">
                        <i class="bi bi-coin"></i> 币种管理
                    </a>
                    <a class="nav-link" href="fields.php">
                        <i class="bi bi-input-cursor-text"></i> 字段管理
                    </a>
                    <a class="nav-link" href="users.php">
                        <i class="bi bi-people"></i> 用户管理
                    </a>
                    <a class="nav-link" href="configs.php">
                        <i class="bi bi-gear"></i> 配置查看
                    </a>
                    <a class="nav-link" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i> 退出登录
                    </a>
                </nav>
            </div>
            
            <!-- 主内容区 -->
            <div class="col-md-10 main-content">
                <div class="page-header">
                    <h1><i class="bi bi-speedometer2"></i> 仪表板</h1>
                </div>
                
                <!-- 统计卡片 -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="bi bi-coin icon"></i>
                            <h6>币种总数</h6>
                            <div class="stat-number">
                                <?php
                                $stmt = $db->query("SELECT COUNT(*) as count FROM currencies");
                                echo $stmt->fetch()['count'];
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="bi bi-people icon"></i>
                            <h6>用户总数</h6>
                            <div class="stat-number">
                                <?php
                                $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 0");
                                echo $stmt->fetch()['count'];
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="bi bi-file-text icon"></i>
                            <h6>配置总数</h6>
                            <div class="stat-number">
                                <?php
                                $stmt = $db->query("SELECT COUNT(*) as count FROM user_configs");
                                echo $stmt->fetch()['count'];
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="bi bi-input-cursor-text icon"></i>
                            <h6>字段总数</h6>
                            <div class="stat-number">
                                <?php
                                $stmt = $db->query("SELECT COUNT(*) as count FROM custom_fields");
                                echo $stmt->fetch()['count'];
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 最近活动 -->
                <div class="tech-card">
                    <h4 class="mb-4"><i class="bi bi-activity"></i> 最近配置</h4>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover">
                            <thead>
                                <tr>
                                    <th>用户</th>
                                    <th>币种</th>
                                    <th>配置时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $db->query("
                                    SELECT uc.*, u.username, c.name as currency_name 
                                    FROM user_configs uc
                                    JOIN users u ON uc.user_id = u.id
                                    JOIN currencies c ON uc.currency_id = c.id
                                    ORDER BY uc.created_at DESC
                                    LIMIT 10
                                ");
                                while($row = $stmt->fetch()):
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['currency_name']); ?></td>
                                    <td><?php echo $row['created_at']; ?></td>
                                    <td>
                                        <a href="view-config.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-tech">
                                            查看
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>