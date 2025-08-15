<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

// 检查管理员权限
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$message = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = $_POST['name'];
                $symbol = $_POST['symbol'];
                $icon = $_POST['icon'];
                $template = $_POST['template_params'];
                
                // 验证JSON格式
                $json_test = json_decode($template);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $message = '<div class="alert alert-danger">模板参数必须是有效的JSON格式</div>';
                } else {
                    $db->insert('currencies', [
                        'name' => $name,
                        'symbol' => $symbol,
                        'icon' => $icon,
                        'template_params' => $template
                    ]);
                    $message = '<div class="alert alert-success">币种添加成功</div>';
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $name = $_POST['name'];
                $symbol = $_POST['symbol'];
                $icon = $_POST['icon'];
                $template = $_POST['template_params'];
                
                $json_test = json_decode($template);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $message = '<div class="alert alert-danger">模板参数必须是有效的JSON格式</div>';
                } else {
                    $db->update('currencies', [
                        'name' => $name,
                        'symbol' => $symbol,
                        'icon' => $icon,
                        'template_params' => $template
                    ], 'id = :id', ['id' => $id]);
                    $message = '<div class="alert alert-success">币种更新成功</div>';
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                $db->delete('currencies', 'id = :id', ['id' => $id]);
                $message = '<div class="alert alert-success">币种删除成功</div>';
                break;
        }
    }
}

// 获取所有币种
$currencies = $db->query("SELECT * FROM currencies ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>币种管理 - <?php echo SITE_NAME; ?></title>
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
        
        .currency-icon {
            width: 30px;
            height: 30px;
            object-fit: contain;
        }
        
        .code-editor {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.5;
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
                    <a class="nav-link" href="index.php">
                        <i class="bi bi-speedometer2"></i> 仪表板
                    </a>
                    <a class="nav-link active" href="currencies.php">
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
                <div class="page-header d-flex justify-content-between align-items-center">
                    <h1><i class="bi bi-coin"></i> 币种管理</h1>
                    <button class="btn btn-tech" data-bs-toggle="modal" data-bs-target="#addCurrencyModal">
                        <i class="bi bi-plus-circle"></i> 添加币种
                    </button>
                </div>
                
                <?php echo $message; ?>
                
                <div class="tech-card">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>图标</th>
                                    <th>名称</th>
                                    <th>符号</th>
                                    <th>模板参数</th>
                                    <th>状态</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($currencies as $currency): ?>
                                <tr>
                                    <td><?php echo $currency['id']; ?></td>
                                    <td>
                                        <?php if($currency['icon']): ?>
                                        <img src="<?php echo htmlspecialchars($currency['icon']); ?>" class="currency-icon">
                                        <?php else: ?>
                                        <i class="bi bi-coin" style="font-size: 24px;"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($currency['name']); ?></td>
                                    <td><?php echo htmlspecialchars($currency['symbol']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info" onclick="viewTemplate(<?php echo htmlspecialchars(json_encode($currency['template_params'])); ?>)">
                                            <i class="bi bi-eye"></i> 查看
                                        </button>
                                    </td>
                                    <td>
                                        <?php if($currency['is_active']): ?>
                                        <span class="badge bg-success">启用</span>
                                        <?php else: ?>
                                        <span class="badge bg-danger">禁用</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick="editCurrency(<?php echo htmlspecialchars(json_encode($currency)); ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="fields.php?currency_id=<?php echo $currency['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="bi bi-list"></i>
                                        </a>
                                        <button class="btn btn-sm btn-danger" onclick="deleteCurrency(<?php echo $currency['id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 添加币种模态框 -->
    <div class="modal fade" id="addCurrencyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">添加币种</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">币种名称</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">币种符号</label>
                            <input type="text" class="form-control" name="symbol" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">图标URL（可选）</label>
                            <input type="text" class="form-control" name="icon">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">全局模板参数（JSON格式）</label>
                            <textarea class="form-control code-editor" name="template_params" rows="10" required>{
    "network": "mainnet",
    "wallet": "{{WALLET}}",
    "private_key": "{{PRIVATE_KEY}}",
    "api_endpoint": "https://api.example.com",
    "gas_limit": 21000,
    "gas_price": "{{GAS_PRICE}}"
}</textarea>
                            <small class="text-muted">使用 {{变量名}} 作为占位符，用户填写的字段值将替换这些占位符</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-tech">保存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- 编辑币种模态框 -->
    <div class="modal fade" id="editCurrencyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">编辑币种</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">币种名称</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">币种符号</label>
                            <input type="text" class="form-control" name="symbol" id="edit_symbol" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">图标URL（可选）</label>
                            <input type="text" class="form-control" name="icon" id="edit_icon">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">全局模板参数（JSON格式）</label>
                            <textarea class="form-control code-editor" name="template_params" id="edit_template" rows="10" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-tech">更新</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- 查看模板模态框 -->
    <div class="modal fade" id="viewTemplateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">模板参数</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <pre class="code-editor" id="template_view" style="color: #00d4ff;"></pre>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCurrency(currency) {
            document.getElementById('edit_id').value = currency.id;
            document.getElementById('edit_name').value = currency.name;
            document.getElementById('edit_symbol').value = currency.symbol;
            document.getElementById('edit_icon').value = currency.icon || '';
            document.getElementById('edit_template').value = currency.template_params || '{}';
            
            new bootstrap.Modal(document.getElementById('editCurrencyModal')).show();
        }
        
        function deleteCurrency(id) {
            if(confirm('确定要删除这个币种吗？相关的字段和用户配置也会被删除。')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function viewTemplate(template) {
            try {
                const formatted = JSON.stringify(JSON.parse(template), null, 4);
                document.getElementById('template_view').textContent = formatted;
            } catch(e) {
                document.getElementById('template_view').textContent = template;
            }
            new bootstrap.Modal(document.getElementById('viewTemplateModal')).show();
        }
    </script>
</body>
</html>