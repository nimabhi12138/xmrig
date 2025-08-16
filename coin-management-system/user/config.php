<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// 检查用户登录
if (!Session::isLoggedIn('user')) {
    header('Location: login.php');
    exit;
}

$user = Session::getCurrentUser('user');
$db = Database::getInstance();

$error = '';
$success = '';
$coin_id = $_GET['coin_id'] ?? null;
$config_id = $_GET['config_id'] ?? null;

// 编辑模式
if ($config_id) {
    $config = $db->fetchOne(
        "SELECT uc.*, c.* FROM user_configs uc 
         JOIN coins c ON uc.coin_id = c.id 
         WHERE uc.id = ? AND uc.user_id = ?",
        [$config_id, $user['id']]
    );
    
    if (!$config) {
        header('Location: index.php?error=' . urlencode('配置不存在'));
        exit;
    }
    
    $coin_id = $config['coin_id'];
    $field_values = json_decode($config['field_values'], true) ?: [];
}

// 新建模式
if ($coin_id && !$config_id) {
    $coin = $db->fetchOne("SELECT * FROM coins WHERE id = ? AND status = 1", [$coin_id]);
    if (!$coin) {
        header('Location: index.php?error=' . urlencode('币种不存在'));
        exit;
    }
    
    // 检查是否已有配置
    $existing = $db->fetchOne(
        "SELECT id FROM user_configs WHERE user_id = ? AND coin_id = ?",
        [$user['id'], $coin_id]
    );
    
    if ($existing) {
        header('Location: config.php?config_id=' . $existing['id']);
        exit;
    }
    
    $field_values = [];
}

if (!$coin_id) {
    header('Location: index.php?error=' . urlencode('缺少币种参数'));
    exit;
}

// 获取币种信息
$coin = isset($config) ? $config : $db->fetchOne("SELECT * FROM coins WHERE id = ?", [$coin_id]);

// 获取自定义字段
$fields = $db->fetchAll(
    "SELECT * FROM custom_fields WHERE coin_id = ? ORDER BY sort_order ASC, id ASC",
    [$coin_id]
);

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_values = [];
    
    // 验证必填字段
    foreach ($fields as $field) {
        $value = Security::sanitizeInput($_POST[$field['placeholder_key']] ?? '');
        
        if ($field['is_required'] && empty($value)) {
            $error = "字段 {$field['title']} 是必填的";
            break;
        }
        
        if (!empty($value)) {
            $form_values[$field['placeholder_key']] = $value;
        }
    }
    
    if (!$error) {
        try {
            // 生成配置
            $generated_config = '';
            if (!empty($coin['global_template'])) {
                $generated_config = TemplateEngine::replacePlaceholders(
                    $coin['global_template'], 
                    $form_values
                );
            }
            
            if ($config_id) {
                // 更新配置
                $db->update('user_configs', [
                    'field_values' => json_encode($form_values),
                    'generated_config' => $generated_config
                ], 'id = ?', [$config_id]);
                
                $success = '配置更新成功！';
                Logger::info("用户更新配置: {$user['username']} - {$coin['name']}");
            } else {
                // 创建新配置
                $new_config_id = $db->insert('user_configs', [
                    'user_id' => $user['id'],
                    'coin_id' => $coin_id,
                    'field_values' => json_encode($form_values),
                    'generated_config' => $generated_config
                ]);
                
                $success = '配置创建成功！';
                Logger::info("用户创建配置: {$user['username']} - {$coin['name']}");
                
                // 重定向到编辑页面
                header('Location: config.php?config_id=' . $new_config_id . '&success=' . urlencode($success));
                exit;
            }
            
            $field_values = $form_values;
            
        } catch (Exception $e) {
            $error = '保存失败，请稍后重试';
            Logger::error("保存用户配置失败: " . $e->getMessage());
        }
    }
}

$success = $_GET['success'] ?? $success;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $config_id ? '编辑' : '创建' ?> <?= htmlspecialchars($coin['name']) ?> 配置 - 币种管理系统</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/user.css" rel="stylesheet">
    <style>
        .config-form {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .form-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
        }
        
        .form-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .coin-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--accent-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
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
        
        .required {
            color: var(--accent-red);
        }
        
        .form-control {
            width: 100%;
            padding: 0.875rem;
            background: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            color: var(--text-primary);
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.1);
        }
        
        .form-control[readonly] {
            background: var(--primary-bg);
            opacity: 0.7;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .preview-section {
            background: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .preview-content {
            background: var(--primary-bg);
            border-radius: 0.25rem;
            padding: 1rem;
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="nav-left">
            <div class="logo">
                <i class="fas fa-coins"></i>
                <span>币种管理</span>
            </div>
        </div>
        <div class="nav-right">
            <div class="user-menu">
                <span><?= htmlspecialchars($user['username']) ?></span>
                <a href="logout.php" class="btn btn-outline">
                    <i class="fas fa-sign-out-alt"></i>
                    登出
                </a>
            </div>
        </div>
    </div>

    <div class="config-form">
        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <div class="form-card">
            <div class="form-header">
                <div class="coin-icon">
                    <?php if ($coin['icon_url']): ?>
                        <img src="<?= htmlspecialchars($coin['icon_url']) ?>" alt="<?= htmlspecialchars($coin['name']) ?>" style="width: 100%; height: 100%; border-radius: 50%;">
                    <?php else: ?>
                        <i class="fas fa-coins"></i>
                    <?php endif; ?>
                </div>
                <div>
                    <h1><?= $config_id ? '编辑' : '创建' ?> <?= htmlspecialchars($coin['name']) ?> 配置</h1>
                    <p><?= htmlspecialchars($coin['symbol']) ?> - 配置您的个性化参数</p>
                </div>
            </div>

            <form method="POST">
                <?php foreach ($fields as $field): ?>
                <div class="form-group">
                    <label for="<?= htmlspecialchars($field['placeholder_key']) ?>">
                        <?= htmlspecialchars($field['title']) ?>
                        <?php if ($field['is_required']): ?>
                            <span class="required">*</span>
                        <?php endif; ?>
                    </label>
                    
                    <?php 
                    $value = $field_values[$field['placeholder_key']] ?? '';
                    switch ($field['field_type']):
                        case 'textarea': ?>
                            <textarea 
                                id="<?= htmlspecialchars($field['placeholder_key']) ?>"
                                name="<?= htmlspecialchars($field['placeholder_key']) ?>"
                                class="form-control"
                                placeholder="请输入<?= htmlspecialchars($field['title']) ?>"
                                <?= $field['is_required'] ? 'required' : '' ?>
                            ><?= htmlspecialchars($value) ?></textarea>
                        <?php break;
                        
                        case 'select':
                            $options = json_decode($field['options'], true) ?: [];
                        ?>
                            <select 
                                id="<?= htmlspecialchars($field['placeholder_key']) ?>"
                                name="<?= htmlspecialchars($field['placeholder_key']) ?>"
                                class="form-control"
                                <?= $field['is_required'] ? 'required' : '' ?>
                            >
                                <option value="">请选择<?= htmlspecialchars($field['title']) ?></option>
                                <?php foreach ($options as $option): ?>
                                    <option value="<?= htmlspecialchars($option) ?>" <?= $value === $option ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($option) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php break;
                        
                        default: ?>
                            <input 
                                type="<?= htmlspecialchars($field['field_type']) ?>"
                                id="<?= htmlspecialchars($field['placeholder_key']) ?>"
                                name="<?= htmlspecialchars($field['placeholder_key']) ?>"
                                class="form-control"
                                placeholder="请输入<?= htmlspecialchars($field['title']) ?>"
                                value="<?= htmlspecialchars($value) ?>"
                                <?= $field['is_required'] ? 'required' : '' ?>
                            >
                        <?php break;
                    endswitch; ?>
                </div>
                <?php endforeach; ?>

                <div class="button-group">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        返回
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <?= $config_id ? '更新配置' : '创建配置' ?>
                    </button>
                    <?php if ($config_id): ?>
                    <button type="button" class="btn btn-outline" onclick="showPreview()">
                        <i class="fas fa-eye"></i>
                        预览配置
                    </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <?php if ($config_id && !empty($config['generated_config'])): ?>
        <div class="form-card" id="previewSection" style="display: none;">
            <h3>配置预览</h3>
            <div class="preview-section">
                <div class="preview-content"><?= htmlspecialchars($config['generated_config']) ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function showPreview() {
            const section = document.getElementById('previewSection');
            if (section) {
                section.style.display = section.style.display === 'none' ? 'block' : 'none';
            }
        }
        
        // 自动保存草稿（可选功能）
        let saveTimer;
        const form = document.querySelector('form');
        const inputs = form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                clearTimeout(saveTimer);
                saveTimer = setTimeout(() => {
                    // 这里可以实现自动保存草稿功能
                    console.log('自动保存草稿...');
                }, 2000);
            });
        });
    </script>
</body>
</html>