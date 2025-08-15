<?php
require_once '../config.php';
checkAdmin();

$db = getDB();

// 获取统计数据
$stats = [];

// 用户总数
$stmt = $db->query("SELECT COUNT(*) FROM users");
$stats['total_users'] = $stmt->fetchColumn();

// 币种总数
$stmt = $db->query("SELECT COUNT(*) FROM coins");
$stats['total_coins'] = $stmt->fetchColumn();

// 配置总数
$stmt = $db->query("SELECT COUNT(*) FROM user_configs");
$stats['total_configs'] = $stmt->fetchColumn();

// 今日登录
$stmt = $db->query("SELECT COUNT(*) FROM login_logs WHERE DATE(created_at) = CURDATE() AND status = 1");
$stats['today_logins'] = $stmt->fetchColumn();

// 最近登录记录
$stmt = $db->query("
    SELECT l.*, u.username as real_username 
    FROM login_logs l 
    LEFT JOIN users u ON l.user_id = u.id 
    ORDER BY l.created_at DESC 
    LIMIT 10
");
$recent_logins = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 15px 20px;
            border-radius: 10px;
            margin: 5px 10px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        .main-content {
            padding: 30px;
        }
        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .user-info {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- 侧边栏 -->
            <div class="col-md-2 p-0 sidebar">
                <div class="user-info">
                    <h5><i class="fas fa-user-shield"></i> 管理员</h5>
                    <p class="mb-0"><?php echo $_SESSION['username']; ?></p>
                </div>
                
                <nav class="nav flex-column">
                    <a class="nav-link active" href="index.php">
                        <i class="fas fa-dashboard"></i> 控制台
                    </a>
                    <a class="nav-link" href="coins.php">
                        <i class="fas fa-coins"></i> 币种管理
                    </a>
                    <a class="nav-link" href="users.php">
                        <i class="fas fa-users"></i> 用户管理
                    </a>
                    <a class="nav-link" href="configs.php">
                        <i class="fas fa-cog"></i> 配置管理
                    </a>
                    <a class="nav-link" href="logs.php">
                        <i class="fas fa-history"></i> 登录日志
                    </a>
                    <a class="nav-link" href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i> 退出登录
                    </a>
                </nav>
            </div>
            
            <!-- 主内容区 -->
            <div class="col-md-10 main-content">
                <h2 class="mb-4">控制台</h2>
                
                <!-- 统计卡片 -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body d-flex align-items-center">
                                <div class="stat-icon bg-primary bg-gradient text-white">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="ms-3">
                                    <h3 class="mb-0"><?php echo $stats['total_users']; ?></h3>
                                    <p class="text-muted mb-0">用户总数</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body d-flex align-items-center">
                                <div class="stat-icon bg-success bg-gradient text-white">
                                    <i class="fas fa-coins"></i>
                                </div>
                                <div class="ms-3">
                                    <h3 class="mb-0"><?php echo $stats['total_coins']; ?></h3>
                                    <p class="text-muted mb-0">币种总数</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body d-flex align-items-center">
                                <div class="stat-icon bg-warning bg-gradient text-white">
                                    <i class="fas fa-cog"></i>
                                </div>
                                <div class="ms-3">
                                    <h3 class="mb-0"><?php echo $stats['total_configs']; ?></h3>
                                    <p class="text-muted mb-0">配置总数</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body d-flex align-items-center">
                                <div class="stat-icon bg-info bg-gradient text-white">
                                    <i class="fas fa-sign-in-alt"></i>
                                </div>
                                <div class="ms-3">
                                    <h3 class="mb-0"><?php echo $stats['today_logins']; ?></h3>
                                    <p class="text-muted mb-0">今日登录</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 最近登录记录 -->
                <div class="table-container">
                    <h4 class="mb-3">最近登录记录</h4>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>用户名</th>
                                    <th>IP地址</th>
                                    <th>状态</th>
                                    <th>时间</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_logins as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['real_username'] ?? $log['username']); ?></td>
                                    <td><?php echo htmlspecialchars($log['ip']); ?></td>
                                    <td>
                                        <?php if ($log['status'] == 1): ?>
                                            <span class="badge bg-success">成功</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">失败</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $log['created_at']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>