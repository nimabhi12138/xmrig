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

// 获取币种ID
$currency_id = isset($_GET['currency_id']) ? intval($_GET['currency_id']) : 0;

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $db->insert('custom_fields', [
                    'currency_id' => $_POST['currency_id'],
                    'field_title' => $_POST['field_title'],
                    'field_type' => $_POST['field_type'],
                    'field_placeholder' => $_POST['field_placeholder'],
                    'field_options' => $_POST['field_type'] == 'select' ? json_encode(explode("\n", trim($_POST['field_options']))) : null,
                    'is_required' => isset($_POST['is_required']) ? 1 : 0,
                    'sort_order' => $_POST['sort_order']
                ]);
                $message = '<div class="alert alert-success">字段添加成功</div>';
                break;
                
            case 'edit':
                $data = [
                    'field_title' => $_POST['field_title'],
                    'field_type' => $_POST['field_type'],
                    'field_placeholder' => $_POST['field_placeholder'],
                    'field_options' => $_POST['field_type'] == 'select' ? json_encode(explode("\n", trim($_POST['field_options']))) : null,
                    'is_required' => isset($_POST['is_required']) ? 1 : 0,
                    'sort_order' => $_POST['sort_order']
                ];
                $db->update('custom_fields', $data, 'id = :id', ['id' => $_POST['id']]);
                $message = '<div class="alert alert-success">字段更新成功</div>';
                break;
                
            case 'delete':
                $db->delete('custom_fields', 'id = :id', ['id' => $_POST['id']]);
                $message = '<div class="alert alert-success">字段删除成功</div>';
                break;
        }
    }
}

// 获取币种列表
$currencies = $db->query("SELECT * FROM currencies ORDER BY name")->fetchAll();

// 获取字段列表
$sql = "SELECT cf.*, c.name as currency_name 
        FROM custom_fields cf
        JOIN currencies c ON cf.currency_id = c.id";
$params = [];

if ($currency_id > 0) {
    $sql .= " WHERE cf.currency_id = :currency_id";
    $params['currency_id'] = $currency_id;
}

$sql .= " ORDER BY cf.currency_id, cf.sort_order";
$fields = $db->query($sql, $params)->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>字段管理 - <?php echo SITE_NAME; ?></title>
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
        
        .btn-tech {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            border: none;
            color: #fff;
            padding: 10px 25px;
            border-radius: 25px;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
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
        
        .field-type-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .field-type-text { background: #4CAF50; }
        .field-type-textarea { background: #2196F3; }
        .field-type-select { background: #FF9800; }
        .field-type-number { background: #9C27B0; }
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
                    <a class="nav-link" href="currencies.php">
                        <i class="bi bi-coin"></i> 币种管理
                    </a>
                    <a class="nav-link active" href="fields.php">
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
                    <h1><i class="bi bi-input-cursor-text"></i> 字段管理</h1>
                    <div>
                        <select class="form-select d-inline-block w-auto me-2" onchange="filterByCurrency(this.value)">
                            <option value="0">所有币种</option>
                            <?php foreach($currencies as $currency): ?>
                            <option value="<?php echo $currency['id']; ?>" <?php echo $currency_id == $currency['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($currency['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-tech" data-bs-toggle="modal" data-bs-target="#addFieldModal">
                            <i class="bi bi-plus-circle"></i> 添加字段
                        </button>
                    </div>
                </div>
                
                <?php echo $message; ?>
                
                <div class="tech-card">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover">
                            <thead>
                                <tr>
                                    <th>币种</th>
                                    <th>字段标题</th>
                                    <th>类型</th>
                                    <th>占位符</th>
                                    <th>必填</th>
                                    <th>排序</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($fields as $field): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($field['currency_name']); ?></td>
                                    <td><?php echo htmlspecialchars($field['field_title']); ?></td>
                                    <td>
                                        <span class="field-type-badge field-type-<?php echo $field['field_type']; ?>">
                                            <?php echo $field['field_type']; ?>
                                        </span>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($field['field_placeholder']); ?></code></td>
                                    <td>
                                        <?php if($field['is_required']): ?>
                                        <span class="badge bg-danger">必填</span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary">可选</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $field['sort_order']; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick='editField(<?php echo json_encode($field); ?>)'>
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteField(<?php echo $field['id']; ?>)">
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
    
    <!-- 添加字段模态框 -->
    <div class="modal fade" id="addFieldModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">添加字段</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">选择币种</label>
                            <select class="form-select" name="currency_id" required>
                                <option value="">请选择币种</option>
                                <?php foreach($currencies as $currency): ?>
                                <option value="<?php echo $currency['id']; ?>">
                                    <?php echo htmlspecialchars($currency['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">字段标题</label>
                            <input type="text" class="form-control" name="field_title" placeholder="例：钱包地址" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">字段类型</label>
                            <select class="form-select" name="field_type" id="add_field_type" onchange="toggleOptionsField('add')" required>
                                <option value="text">单行文本</option>
                                <option value="textarea">多行文本</option>
                                <option value="select">下拉选择</option>
                                <option value="number">数字</option>
                            </select>
                        </div>
                        <div class="mb-3" id="add_options_group" style="display: none;">
                            <label class="form-label">选项（每行一个）</label>
                            <textarea class="form-control" name="field_options" rows="4" placeholder="选项1&#10;选项2&#10;选项3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">占位符变量</label>
                            <input type="text" class="form-control" name="field_placeholder" placeholder="例：{{WALLET}}" required>
                            <small class="text-muted">这个变量将在模板中被替换为用户填写的值</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">排序</label>
                            <input type="number" class="form-control" name="sort_order" value="0" required>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_required" id="add_required">
                            <label class="form-check-label" for="add_required">
                                设为必填项
                            </label>
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
    
    <!-- 编辑字段模态框 -->
    <div class="modal fade" id="editFieldModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">编辑字段</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">字段标题</label>
                            <input type="text" class="form-control" name="field_title" id="edit_title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">字段类型</label>
                            <select class="form-select" name="field_type" id="edit_field_type" onchange="toggleOptionsField('edit')" required>
                                <option value="text">单行文本</option>
                                <option value="textarea">多行文本</option>
                                <option value="select">下拉选择</option>
                                <option value="number">数字</option>
                            </select>
                        </div>
                        <div class="mb-3" id="edit_options_group" style="display: none;">
                            <label class="form-label">选项（每行一个）</label>
                            <textarea class="form-control" name="field_options" id="edit_options" rows="4"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">占位符变量</label>
                            <input type="text" class="form-control" name="field_placeholder" id="edit_placeholder" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">排序</label>
                            <input type="number" class="form-control" name="sort_order" id="edit_sort" required>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_required" id="edit_required">
                            <label class="form-check-label" for="edit_required">
                                设为必填项
                            </label>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterByCurrency(currencyId) {
            window.location.href = 'fields.php' + (currencyId > 0 ? '?currency_id=' + currencyId : '');
        }
        
        function toggleOptionsField(prefix) {
            const fieldType = document.getElementById(prefix + '_field_type').value;
            const optionsGroup = document.getElementById(prefix + '_options_group');
            optionsGroup.style.display = fieldType === 'select' ? 'block' : 'none';
        }
        
        function editField(field) {
            document.getElementById('edit_id').value = field.id;
            document.getElementById('edit_title').value = field.field_title;
            document.getElementById('edit_field_type').value = field.field_type;
            document.getElementById('edit_placeholder').value = field.field_placeholder;
            document.getElementById('edit_sort').value = field.sort_order;
            document.getElementById('edit_required').checked = field.is_required == 1;
            
            if (field.field_type === 'select' && field.field_options) {
                const options = JSON.parse(field.field_options);
                document.getElementById('edit_options').value = options.join('\n');
                document.getElementById('edit_options_group').style.display = 'block';
            } else {
                document.getElementById('edit_options_group').style.display = 'none';
            }
            
            new bootstrap.Modal(document.getElementById('editFieldModal')).show();
        }
        
        function deleteField(id) {
            if(confirm('确定要删除这个字段吗？')) {
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
    </script>
</body>
</html>