<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// 检查管理员登录状态
if (!Session::isLoggedIn('admin')) {
    header('Location: login.php');
    exit;
}

$admin = Session::getCurrentUser('admin');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>币种管理系统 - 管理后台</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <!-- 侧边栏 -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-coins"></i>
                    <span>币种管理</span>
                </div>
            </div>
            
            <div class="sidebar-menu">
                <a href="index.php" class="menu-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>仪表盘</span>
                </a>
                <a href="coins.php" class="menu-item">
                    <i class="fas fa-coins"></i>
                    <span>币种管理</span>
                </a>
                <a href="users.php" class="menu-item">
                    <i class="fas fa-users"></i>
                    <span>用户管理</span>
                </a>
                <a href="configs.php" class="menu-item">
                    <i class="fas fa-cog"></i>
                    <span>系统配置</span>
                </a>
                <a href="logs.php" class="menu-item">
                    <i class="fas fa-file-alt"></i>
                    <span>系统日志</span>
                </a>
            </div>
            
            <div class="sidebar-footer">
                <div class="admin-info">
                    <i class="fas fa-user-shield"></i>
                    <span><?php echo htmlspecialchars($admin['username']); ?></span>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>退出</span>
                </a>
            </div>
        </nav>
        
        <!-- 主内容区 -->
        <main class="main-content">
            <div class="content-header">
                <h1>
                    <i class="fas fa-tachometer-alt"></i>
                    仪表盘
                </h1>
                <div class="header-actions">
                    <button class="btn btn-refresh" onclick="refreshData()">
                        <i class="fas fa-sync-alt"></i>
                        刷新数据
                    </button>
                </div>
            </div>
            
            <!-- 统计卡片 -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number" id="totalCoins">-</div>
                        <div class="stat-label">币种总数</div>
                    </div>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up"></i>
                        <span>活跃</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number" id="totalUsers">-</div>
                        <div class="stat-label">注册用户</div>
                    </div>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up"></i>
                        <span>增长</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number" id="totalConfigs">-</div>
                        <div class="stat-label">用户配置</div>
                    </div>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up"></i>
                        <span>活跃</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number" id="todayApiCalls">-</div>
                        <div class="stat-label">今日API调用</div>
                    </div>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up"></i>
                        <span>实时</span>
                    </div>
                </div>
            </div>
            
            <!-- 图表区域 -->
            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>用户注册趋势</h3>
                        <div class="chart-actions">
                            <select id="chartPeriod">
                                <option value="7">最近7天</option>
                                <option value="30">最近30天</option>
                                <option value="90">最近90天</option>
                            </select>
                        </div>
                    </div>
                    <div class="chart-content">
                        <canvas id="userChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>API调用统计</h3>
                        <div class="chart-actions">
                            <button class="btn btn-small">查看详情</button>
                        </div>
                    </div>
                    <div class="chart-content">
                        <canvas id="apiChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- 最近活动 -->
            <div class="activity-section">
                <div class="section-header">
                    <h3>最近活动</h3>
                    <a href="logs.php" class="view-all">查看全部</a>
                </div>
                <div class="activity-list" id="recentActivity">
                    <div class="loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        加载中...
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- 加载动画 -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <div class="loading-text">加载中...</div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>