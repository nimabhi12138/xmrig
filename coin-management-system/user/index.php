<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// 检查用户登录状态
if (!Session::isLoggedIn('user')) {
    header('Location: login.php');
    exit;
}

$user = Session::getCurrentUser('user');
$db = Database::getInstance();

// 获取用户的币种配置
$userConfigs = $db->fetchAll(
    "SELECT uc.*, c.name as coin_name, c.symbol as coin_symbol, c.icon_url
     FROM user_configs uc 
     JOIN coins c ON uc.coin_id = c.id 
     WHERE uc.user_id = ? AND c.status = 1
     ORDER BY c.sort_order ASC, c.name ASC",
    [$user['id']]
);

// 获取可用币种（用户尚未配置的）
$availableCoins = $db->fetchAll(
    "SELECT c.* FROM coins c 
     WHERE c.status = 1 
     AND c.id NOT IN (
         SELECT coin_id FROM user_configs WHERE user_id = ?
     )
     ORDER BY c.sort_order ASC, c.name ASC",
    [$user['id']]
);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的配置 - 币种管理系统</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/user.css" rel="stylesheet">
</head>
<body>
    <div class="app-container">
        <!-- 顶部导航 -->
        <header class="top-nav">
            <div class="nav-content">
                <div class="nav-left">
                    <div class="logo">
                        <i class="fas fa-coins"></i>
                        <span>币种配置</span>
                    </div>
                </div>
                
                <div class="nav-right">
                    <div class="user-menu">
                        <div class="user-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($user['username']); ?></div>
                            <div class="user-role">用户</div>
                        </div>
                        <div class="user-actions">
                            <a href="profile.php" class="action-btn" title="个人资料">
                                <i class="fas fa-cog"></i>
                            </a>
                            <a href="logout.php" class="action-btn" title="退出登录">
                                <i class="fas fa-sign-out-alt"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- 主内容区 -->
        <main class="main-content">
            <!-- 状态栏 -->
            <div class="status-bar">
                <div class="status-item">
                    <div class="status-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="status-content">
                        <div class="status-number"><?php echo count($userConfigs); ?></div>
                        <div class="status-label">已配置币种</div>
                    </div>
                </div>
                
                <div class="status-item">
                    <div class="status-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div class="status-content">
                        <div class="status-number"><?php echo count($availableCoins); ?></div>
                        <div class="status-label">可添加币种</div>
                    </div>
                </div>
                
                <div class="status-item">
                    <div class="status-icon">
                        <i class="fas fa-key"></i>
                    </div>
                    <div class="status-content">
                        <div class="status-number">API令牌</div>
                        <div class="status-label">
                            <code><?php echo substr($user['api_token'], 0, 8); ?>...</code>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 操作按钮 -->
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="showAddCoinModal()">
                    <i class="fas fa-plus"></i>
                    添加币种配置
                </button>
                
                <button class="btn btn-secondary" onclick="showApiInfoModal()">
                    <i class="fas fa-code"></i>
                    API文档
                </button>
                
                <button class="btn btn-secondary" onclick="refreshConfigs()">
                    <i class="fas fa-sync-alt"></i>
                    刷新配置
                </button>
            </div>

            <!-- 币种配置列表 -->
            <div class="configs-section">
                <h2 class="section-title">
                    <i class="fas fa-list"></i>
                    我的币种配置
                </h2>
                
                <?php if (empty($userConfigs)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="empty-title">暂无币种配置</div>
                    <div class="empty-description">
                        点击"添加币种配置"开始配置您的第一个币种
                    </div>
                    <button class="btn btn-primary" onclick="showAddCoinModal()">
                        <i class="fas fa-plus"></i>
                        立即添加
                    </button>
                </div>
                <?php else: ?>
                <div class="config-grid">
                    <?php foreach ($userConfigs as $config): ?>
                    <div class="config-card" data-config-id="<?php echo $config['id']; ?>">
                        <div class="config-header">
                            <div class="coin-info">
                                <div class="coin-icon">
                                    <?php if ($config['icon_url']): ?>
                                    <img src="<?php echo htmlspecialchars($config['icon_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($config['coin_name']); ?>">
                                    <?php else: ?>
                                    <i class="fas fa-coins"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="coin-details">
                                    <div class="coin-name"><?php echo htmlspecialchars($config['coin_name']); ?></div>
                                    <div class="coin-symbol"><?php echo htmlspecialchars($config['coin_symbol']); ?></div>
                                </div>
                            </div>
                            
                            <div class="config-actions">
                                <button class="action-btn" onclick="editConfig(<?php echo $config['id']; ?>)" title="编辑">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn" onclick="deleteConfig(<?php echo $config['id']; ?>)" title="删除">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="config-status">
                            <div class="status-indicator <?php echo $config['generated_config'] ? 'active' : 'inactive'; ?>">
                                <?php echo $config['generated_config'] ? '已生成' : '待配置'; ?>
                            </div>
                            <div class="update-time">
                                更新于 <?php echo date('m-d H:i', strtotime($config['updated_at'])); ?>
                            </div>
                        </div>
                        
                        <div class="config-preview">
                            <?php if ($config['generated_config']): ?>
                            <button class="btn btn-small btn-outline" onclick="viewConfig(<?php echo $config['id']; ?>)">
                                <i class="fas fa-eye"></i>
                                查看配置
                            </button>
                            <?php else: ?>
                            <button class="btn btn-small btn-primary" onclick="editConfig(<?php echo $config['id']; ?>)">
                                <i class="fas fa-cog"></i>
                                完成配置
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- 添加币种模态框 -->
    <div class="modal" id="addCoinModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>添加币种配置</h3>
                <button class="modal-close" onclick="closeModal('addCoinModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <?php if (empty($availableCoins)): ?>
                <div class="empty-state small">
                    <div class="empty-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="empty-title">所有币种已配置</div>
                    <div class="empty-description">您已经配置了所有可用的币种</div>
                </div>
                <?php else: ?>
                <div class="coin-list">
                    <?php foreach ($availableCoins as $coin): ?>
                    <div class="coin-item" onclick="selectCoin(<?php echo $coin['id']; ?>)">
                        <div class="coin-icon">
                            <?php if ($coin['icon_url']): ?>
                            <img src="<?php echo htmlspecialchars($coin['icon_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($coin['name']); ?>">
                            <?php else: ?>
                            <i class="fas fa-coins"></i>
                            <?php endif; ?>
                        </div>
                        <div class="coin-details">
                            <div class="coin-name"><?php echo htmlspecialchars($coin['name']); ?></div>
                            <div class="coin-symbol"><?php echo htmlspecialchars($coin['symbol']); ?></div>
                        </div>
                        <div class="coin-action">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- API信息模态框 -->
    <div class="modal" id="apiInfoModal">
        <div class="modal-content large">
            <div class="modal-header">
                <h3>API使用文档</h3>
                <button class="modal-close" onclick="closeModal('apiInfoModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="api-section">
                    <h4>API端点</h4>
                    <div class="api-endpoint">
                        <code>GET <?php echo 'https://' . $_SERVER['HTTP_HOST']; ?>/api/config/<?php echo $user['id']; ?>?token=YOUR_TOKEN</code>
                        <button class="copy-btn" onclick="copyToClipboard(this)" 
                                data-text="<?php echo 'https://' . $_SERVER['HTTP_HOST']; ?>/api/config/<?php echo $user['id']; ?>?token=<?php echo $user['api_token']; ?>">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                
                <div class="api-section">
                    <h4>您的API令牌</h4>
                    <div class="api-token">
                        <code><?php echo $user['api_token']; ?></code>
                        <button class="copy-btn" onclick="copyToClipboard(this)" data-text="<?php echo $user['api_token']; ?>">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                
                <div class="api-section">
                    <h4>响应示例</h4>
                    <pre class="api-response"><code>{
  "status": "success",
  "message": "配置获取成功",
  "data": {
    "user_id": "<?php echo $user['id']; ?>",
    "configs": [
      {
        "coin": {
          "id": 1,
          "name": "Bitcoin",
          "symbol": "BTC"
        },
        "config": {
          "wallet_address": "your_wallet_address",
          "pool_url": "your_pool_url"
        },
        "updated_at": "2024-01-01 12:00:00"
      }
    ],
    "total": 1,
    "generated_at": "2024-01-01 12:00:00"
  }
}</code></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- 加载动画 -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <div class="loading-text">加载中...</div>
        </div>
    </div>

    <script src="../assets/js/user.js"></script>
</body>
</html>