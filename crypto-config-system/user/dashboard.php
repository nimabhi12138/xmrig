<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

// 检查用户登录
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin']) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$user_id = $_SESSION['user_id'];
$message = '';

// 处理配置保存
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'save_config') {
        $currency_id = $_POST['currency_id'];
        
        // 获取币种的模板
        $stmt = $db->query("SELECT template_params FROM currencies WHERE id = :id", ['id' => $currency_id]);
        $currency = $stmt->fetch();
        
        if ($currency) {
            $template = $currency['template_params'];
            $field_values = [];
            
            // 获取该币种的所有字段
            $stmt = $db->query("SELECT * FROM custom_fields WHERE currency_id = :currency_id ORDER BY sort_order", 
                              ['currency_id' => $currency_id]);
            $fields = $stmt->fetchAll();
            
            // 收集用户填写的值
            foreach ($fields as $field) {
                $field_name = 'field_' . $field['id'];
                if (isset($_POST[$field_name])) {
                    $field_values[$field['field_placeholder']] = $_POST[$field_name];
                }
            }
            
            // 替换模板中的占位符
            $processed_config = $template;
            foreach ($field_values as $placeholder => $value) {
                $processed_config = str_replace($placeholder, $value, $processed_config);
            }
            
            // 检查是否已有配置
            $stmt = $db->query("SELECT id FROM user_configs WHERE user_id = :user_id AND currency_id = :currency_id", 
                              ['user_id' => $user_id, 'currency_id' => $currency_id]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // 更新配置
                $db->update('user_configs', [
                    'field_values' => json_encode($field_values),
                    'processed_config' => $processed_config
                ], 'id = :id', ['id' => $existing['id']]);
                $message = '<div class="alert alert-success">配置已更新</div>';
            } else {
                // 创建新配置
                try {
                    $db->insert('user_configs', [
                        'user_id' => $user_id,
                        'currency_id' => $currency_id,
                        'field_values' => json_encode($field_values),
                        'processed_config' => $processed_config
                    ]);
                    $message = '<div class="alert alert-success">配置已保存</div>';
                } catch (Exception $e) {
                    $message = '<div class="alert alert-danger">保存失败：' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            }
        }
    } elseif ($_POST['action'] == 'delete_config') {
        $config_id = $_POST['config_id'];
        $db->delete('user_configs', 'id = :id AND user_id = :user_id', 
                   ['id' => $config_id, 'user_id' => $user_id]);
        $message = '<div class="alert alert-success">配置已删除</div>';
    }
}

// 获取所有币种
$currencies = $db->query("SELECT * FROM currencies WHERE is_active = 1 ORDER BY name")->fetchAll();

// 获取用户的所有配置
$stmt = $db->query("
    SELECT uc.*, c.name as currency_name, c.symbol as currency_symbol, c.icon as currency_icon
    FROM user_configs uc
    JOIN currencies c ON uc.currency_id = c.id
    WHERE uc.user_id = :user_id
    ORDER BY uc.created_at DESC
", ['user_id' => $user_id]);
$user_configs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户仪表板 - <?php echo SITE_NAME; ?></title>
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
        
        .navbar-top {
            background: rgba(21, 25, 53, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            padding: 15px 0;
        }
        
        .navbar-brand {
            color: var(--primary-color) !important;
            font-size: 24px;
            font-weight: 300;
            letter-spacing: 2px;
            text-decoration: none;
        }
        
        .user-info {
            color: #8892b0;
        }
        
        .user-info span {
            color: var(--primary-color);
            margin: 0 10px;
        }
        
        .main-content {
            padding: 30px 0;
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
        
        .section-title {
            color: var(--primary-color);
            font-size: 24px;
            margin-bottom: 20px;
            font-weight: 300;
            letter-spacing: 1px;
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
        
        .form-control, .form-select {
            background: rgba(42, 63, 95, 0.5);
            border: 1px solid var(--border-color);
            color: #fff;
            border-radius: 10px;
            padding: 12px;
        }
        
        .form-control:focus, .form-select:focus {
            background: rgba(42, 63, 95, 0.7);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 212, 255, 0.25);
            color: #fff;
        }
        
        .form-control option, .form-select option {
            background: var(--card-bg);
            color: #fff;
        }
        
        .config-item {
            background: rgba(42, 63, 95, 0.3);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .config-item:hover {
            background: rgba(42, 63, 95, 0.5);
            border-color: var(--primary-color);
        }
        
        .currency-icon {
            width: 40px;
            height: 40px;
            object-fit: contain;
            margin-right: 15px;
        }
        
        .api-endpoint {
            background: rgba(0, 212, 255, 0.1);
            border: 1px solid var(--primary-color);
            border-radius: 10px;
            padding: 15px;
            margin-top: 10px;
            font-family: 'Courier New', monospace;
            word-break: break-all;
        }
        
        .copy-btn {
            background: var(--primary-color);
            border: none;
            color: #fff;
            padding: 5px 15px;
            border-radius: 5px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .copy-btn:hover {
            background: var(--secondary-color);
        }
        
        .modal-content {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
        }
        
        .modal-header {
            border-bottom: 1px solid var(--border-color);
        }
        
        .modal-footer {
            border-top: 1px solid var(--border-color);
        }
        
        .nav-tabs {
            border-bottom: 1px solid var(--border-color);
        }
        
        .nav-tabs .nav-link {
            color: #8892b0;
            border: none;
            padding: 10px 20px;
            transition: all 0.3s;
        }
        
        .nav-tabs .nav-link:hover {
            color: var(--primary-color);
            background: rgba(0, 212, 255, 0.1);
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            background: rgba(0, 212, 255, 0.1);
            border-bottom: 2px solid var(--primary-color);
        }
        
        .stat-box {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <!-- 顶部导航 -->
    <nav class="navbar-top">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a href="#" class="navbar-brand">
                    <i class="bi bi-currency-bitcoin"></i> CRYPTO CONFIG
                </a>
                <div class="user-info">
                    <i class="bi bi-person-circle"></i>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    |
                    <a href="logout.php" class="text-danger text-decoration-none">
                        <i class="bi bi-box-arrow-right"></i> 退出
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- 主内容区 -->
    <div class="main-content">
        <div class="container">
            <?php echo $message; ?>
            
            <!-- 统计信息 -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stat-box">
                        <div class="stat-number"><?php echo count($user_configs); ?></div>
                        <div class="stat-label">已配置币种</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box">
                        <div class="stat-number"><?php echo count($currencies); ?></div>
                        <div class="stat-label">可用币种</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box">
                        <div class="stat-number">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="stat-label">API状态正常</div>
                    </div>
                </div>
            </div>
            
            <!-- 选项卡 -->
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#configs">我的配置</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#new-config">新建配置</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#api-info">API信息</a>
                </li>
            </ul>
            
            <!-- 选项卡内容 -->
            <div class="tab-content">
                <!-- 我的配置 -->
                <div class="tab-pane fade show active" id="configs">
                    <div class="tech-card">
                        <h3 class="section-title">
                            <i class="bi bi-gear"></i> 我的配置列表
                        </h3>
                        
                        <?php if (empty($user_configs)): ?>
                        <p class="text-muted">您还没有配置任何币种，请点击"新建配置"开始。</p>
                        <?php else: ?>
                        <?php foreach ($user_configs as $config): ?>
                        <div class="config-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <?php if ($config['currency_icon']): ?>
                                    <img src="<?php echo htmlspecialchars($config['currency_icon']); ?>" class="currency-icon">
                                    <?php else: ?>
                                    <i class="bi bi-coin" style="font-size: 40px; margin-right: 15px;"></i>
                                    <?php endif; ?>
                                    <div>
                                        <h5><?php echo htmlspecialchars($config['currency_name']); ?> 
                                            <small class="text-muted">(<?php echo htmlspecialchars($config['currency_symbol']); ?>)</small>
                                        </h5>
                                        <small class="text-muted">
                                            创建时间：<?php echo $config['created_at']; ?>
                                        </small>
                                    </div>
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-info" onclick="viewConfig(<?php echo htmlspecialchars(json_encode($config['processed_config'])); ?>)">
                                        <i class="bi bi-eye"></i> 查看
                                    </button>
                                    <button class="btn btn-sm btn-warning" onclick="editConfig(<?php echo $config['currency_id']; ?>)">
                                        <i class="bi bi-pencil"></i> 编辑
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_config">
                                        <input type="hidden" name="config_id" value="<?php echo $config['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('确定要删除这个配置吗？')">
                                            <i class="bi bi-trash"></i> 删除
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- 新建配置 -->
                <div class="tab-pane fade" id="new-config">
                    <div class="tech-card">
                        <h3 class="section-title">
                            <i class="bi bi-plus-circle"></i> 新建币种配置
                        </h3>
                        
                        <div class="mb-4">
                            <label class="form-label">选择币种</label>
                            <select class="form-select" id="currency_select" onchange="loadFields(this.value)">
                                <option value="">-- 请选择币种 --</option>
                                <?php foreach ($currencies as $currency): ?>
                                <option value="<?php echo $currency['id']; ?>">
                                    <?php echo htmlspecialchars($currency['name']); ?> (<?php echo htmlspecialchars($currency['symbol']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div id="fields_container" style="display: none;">
                            <form method="POST" id="config_form">
                                <input type="hidden" name="action" value="save_config">
                                <input type="hidden" name="currency_id" id="form_currency_id">
                                <div id="dynamic_fields"></div>
                                <button type="submit" class="btn btn-tech">
                                    <i class="bi bi-save"></i> 保存配置
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- API信息 -->
                <div class="tab-pane fade" id="api-info">
                    <div class="tech-card">
                        <h3 class="section-title">
                            <i class="bi bi-cloud"></i> API接口信息
                        </h3>
                        
                        <div class="mb-4">
                            <h5>API端点</h5>
                            <div class="api-endpoint">
                                <?php 
                                $api_url = SITE_URL . "/api/config.php?user_id=" . $user_id . "&token=" . $_SESSION['api_token'];
                                echo htmlspecialchars($api_url);
                                ?>
                                <button class="copy-btn float-end" onclick="copyToClipboard('<?php echo htmlspecialchars($api_url); ?>')">
                                    <i class="bi bi-clipboard"></i> 复制
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h5>请求方式</h5>
                            <p>GET</p>
                        </div>
                        
                        <div class="mb-4">
                            <h5>请求参数</h5>
                            <table class="table table-dark">
                                <tr>
                                    <th>参数名</th>
                                    <th>值</th>
                                </tr>
                                <tr>
                                    <td>user_id</td>
                                    <td><?php echo $user_id; ?></td>
                                </tr>
                                <tr>
                                    <td>token</td>
                                    <td>
                                        <span id="token_display">••••••••</span>
                                        <button class="btn btn-sm btn-outline-info ms-2" onclick="toggleToken()">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <span id="token_full" style="display: none;"><?php echo $_SESSION['api_token']; ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div>
                            <h5>使用示例</h5>
                            <pre class="api-endpoint">
curl "<?php echo htmlspecialchars($api_url); ?>"

# 或使用 Authorization header
curl -H "Authorization: Bearer <?php echo $_SESSION['api_token']; ?>" \
     "<?php echo SITE_URL; ?>/api/config.php?user_id=<?php echo $user_id; ?>"
                            </pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 查看配置模态框 -->
    <div class="modal fade" id="viewConfigModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">配置详情</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <pre id="config_content" style="color: #00d4ff; background: rgba(0, 0, 0, 0.3); padding: 15px; border-radius: 10px;"></pre>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function loadFields(currencyId) {
            if (!currencyId) {
                document.getElementById('fields_container').style.display = 'none';
                return;
            }
            
            document.getElementById('form_currency_id').value = currencyId;
            
            // AJAX加载字段
            fetch('get_fields.php?currency_id=' + currencyId)
                .then(response => response.json())
                .then(data => {
                    let html = '';
                    data.forEach(field => {
                        html += '<div class="mb-3">';
                        html += `<label class="form-label">${field.field_title}`;
                        if (field.is_required == 1) {
                            html += ' <span class="text-danger">*</span>';
                        }
                        html += '</label>';
                        
                        if (field.field_type == 'textarea') {
                            html += `<textarea class="form-control" name="field_${field.id}" ${field.is_required == 1 ? 'required' : ''}></textarea>`;
                        } else if (field.field_type == 'select' && field.field_options) {
                            html += `<select class="form-select" name="field_${field.id}" ${field.is_required == 1 ? 'required' : ''}>`;
                            html += '<option value="">请选择</option>';
                            JSON.parse(field.field_options).forEach(option => {
                                html += `<option value="${option}">${option}</option>`;
                            });
                            html += '</select>';
                        } else if (field.field_type == 'number') {
                            html += `<input type="number" class="form-control" name="field_${field.id}" ${field.is_required == 1 ? 'required' : ''}>`;
                        } else {
                            html += `<input type="text" class="form-control" name="field_${field.id}" ${field.is_required == 1 ? 'required' : ''}>`;
                        }
                        
                        html += `<small class="text-muted">占位符: ${field.field_placeholder}</small>`;
                        html += '</div>';
                    });
                    
                    document.getElementById('dynamic_fields').innerHTML = html;
                    document.getElementById('fields_container').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('加载字段失败');
                });
        }
        
        function viewConfig(config) {
            try {
                const formatted = JSON.stringify(JSON.parse(config), null, 4);
                document.getElementById('config_content').textContent = formatted;
            } catch(e) {
                document.getElementById('config_content').textContent = config;
            }
            new bootstrap.Modal(document.getElementById('viewConfigModal')).show();
        }
        
        function editConfig(currencyId) {
            // 切换到新建配置标签并加载对应币种
            const tab = new bootstrap.Tab(document.querySelector('a[href="#new-config"]'));
            tab.show();
            document.getElementById('currency_select').value = currencyId;
            loadFields(currencyId);
        }
        
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('已复制到剪贴板');
            });
        }
        
        function toggleToken() {
            const display = document.getElementById('token_display');
            const full = document.getElementById('token_full');
            if (full.style.display === 'none') {
                display.style.display = 'none';
                full.style.display = 'inline';
            } else {
                display.style.display = 'inline';
                full.style.display = 'none';
            }
        }
    </script>
</body>
</html>