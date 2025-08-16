<?php
// 用户配置管理API

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$params = $GLOBALS['route_params'] ?? [];

switch ($method) {
    case 'GET':
        getUserConfigs();
        break;
    case 'POST':
        createUserConfig($input);
        break;
    case 'PUT':
        if (isset($params['id'])) {
            updateUserConfig($params['id'], $input);
        } else {
            Response::error('缺少配置ID', 400);
        }
        break;
    case 'DELETE':
        if (isset($params['id'])) {
            deleteUserConfig($params['id']);
        } else {
            Response::error('缺少配置ID', 400);
        }
        break;
    default:
        Response::error('不支持的请求方法', 405);
}

function getUserConfigs() {
    // 验证用户登录
    if (!Session::isLoggedIn('user')) {
        Response::error('需要用户登录', 401);
    }
    
    $user = Session::getCurrentUser('user');
    $db = Database::getInstance();
    
    // 获取用户的所有配置
    $configs = $db->fetchAll(
        "SELECT uc.*, c.name as coin_name, c.symbol as coin_symbol, c.icon_url
         FROM user_configs uc 
         JOIN coins c ON uc.coin_id = c.id 
         WHERE uc.user_id = ? AND c.status = 1
         ORDER BY c.sort_order ASC, c.name ASC",
        [$user['id']]
    );
    
    Response::success($configs);
}

function createUserConfig($input) {
    // 验证用户登录
    if (!Session::isLoggedIn('user')) {
        Response::error('需要用户登录', 401);
    }
    
    $user = Session::getCurrentUser('user');
    
    $validator = new Validator();
    $validator->required('coin_id', $input['coin_id'] ?? '')
             ->required('field_values', $input['field_values'] ?? '');
    
    if ($validator->hasErrors()) {
        Response::error('验证失败', 400, $validator->getErrors());
    }
    
    $db = Database::getInstance();
    
    // 检查币种是否存在
    $coin = $db->fetchOne("SELECT * FROM coins WHERE id = ? AND status = 1", [$input['coin_id']]);
    if (!$coin) {
        Response::error('币种不存在或已禁用', 404);
    }
    
    // 检查用户是否已有该币种的配置
    $existing = $db->fetchOne(
        "SELECT id FROM user_configs WHERE user_id = ? AND coin_id = ?",
        [$user['id'], $input['coin_id']]
    );
    
    if ($existing) {
        Response::error('该币种配置已存在', 400);
    }
    
    // 获取币种的自定义字段
    $fields = $db->fetchAll(
        "SELECT * FROM custom_fields WHERE coin_id = ? ORDER BY sort_order ASC",
        [$input['coin_id']]
    );
    
    // 验证必填字段
    $fieldValues = $input['field_values'];
    foreach ($fields as $field) {
        if ($field['is_required'] && empty($fieldValues[$field['placeholder_key']])) {
            Response::error("字段 {$field['title']} 是必填的", 400);
        }
    }
    
    try {
        // 生成配置
        $generatedConfig = '';
        if (!empty($coin['global_template'])) {
            $generatedConfig = TemplateEngine::replacePlaceholders(
                $coin['global_template'], 
                $fieldValues
            );
        }
        
        $configId = $db->insert('user_configs', [
            'user_id' => $user['id'],
            'coin_id' => $input['coin_id'],
            'field_values' => json_encode($fieldValues),
            'generated_config' => $generatedConfig
        ]);
        
        $config = $db->fetchOne(
            "SELECT uc.*, c.name as coin_name, c.symbol as coin_symbol 
             FROM user_configs uc 
             JOIN coins c ON uc.coin_id = c.id 
             WHERE uc.id = ?",
            [$configId]
        );
        
        Logger::info("用户创建配置: {$user['username']} - {$coin['name']}");
        
        Response::success($config, '配置创建成功');
        
    } catch (Exception $e) {
        Logger::error("创建用户配置失败: " . $e->getMessage());
        Response::error('创建失败', 500);
    }
}

function updateUserConfig($configId, $input) {
    // 验证用户登录
    if (!Session::isLoggedIn('user')) {
        Response::error('需要用户登录', 401);
    }
    
    $user = Session::getCurrentUser('user');
    
    if (!$configId || !is_numeric($configId)) {
        Response::error('无效的配置ID', 400);
    }
    
    $db = Database::getInstance();
    
    // 检查配置是否存在且属于当前用户
    $config = $db->fetchOne(
        "SELECT uc.*, c.global_template 
         FROM user_configs uc 
         JOIN coins c ON uc.coin_id = c.id 
         WHERE uc.id = ? AND uc.user_id = ?",
        [$configId, $user['id']]
    );
    
    if (!$config) {
        Response::error('配置不存在或无权限', 404);
    }
    
    $validator = new Validator();
    $validator->required('field_values', $input['field_values'] ?? '');
    
    if ($validator->hasErrors()) {
        Response::error('验证失败', 400, $validator->getErrors());
    }
    
    // 获取币种的自定义字段
    $fields = $db->fetchAll(
        "SELECT * FROM custom_fields WHERE coin_id = ? ORDER BY sort_order ASC",
        [$config['coin_id']]
    );
    
    // 验证必填字段
    $fieldValues = $input['field_values'];
    foreach ($fields as $field) {
        if ($field['is_required'] && empty($fieldValues[$field['placeholder_key']])) {
            Response::error("字段 {$field['title']} 是必填的", 400);
        }
    }
    
    try {
        // 重新生成配置
        $generatedConfig = '';
        if (!empty($config['global_template'])) {
            $generatedConfig = TemplateEngine::replacePlaceholders(
                $config['global_template'], 
                $fieldValues
            );
        }
        
        $db->update('user_configs', [
            'field_values' => json_encode($fieldValues),
            'generated_config' => $generatedConfig
        ], 'id = ?', [$configId]);
        
        $updatedConfig = $db->fetchOne(
            "SELECT uc.*, c.name as coin_name, c.symbol as coin_symbol 
             FROM user_configs uc 
             JOIN coins c ON uc.coin_id = c.id 
             WHERE uc.id = ?",
            [$configId]
        );
        
        Logger::info("用户更新配置: {$user['username']} - 配置ID {$configId}");
        
        Response::success($updatedConfig, '配置更新成功');
        
    } catch (Exception $e) {
        Logger::error("更新用户配置失败: " . $e->getMessage());
        Response::error('更新失败', 500);
    }
}

function deleteUserConfig($configId) {
    // 验证用户登录
    if (!Session::isLoggedIn('user')) {
        Response::error('需要用户登录', 401);
    }
    
    $user = Session::getCurrentUser('user');
    
    if (!$configId || !is_numeric($configId)) {
        Response::error('无效的配置ID', 400);
    }
    
    $db = Database::getInstance();
    
    // 检查配置是否存在且属于当前用户
    $config = $db->fetchOne(
        "SELECT uc.*, c.name as coin_name 
         FROM user_configs uc 
         JOIN coins c ON uc.coin_id = c.id 
         WHERE uc.id = ? AND uc.user_id = ?",
        [$configId, $user['id']]
    );
    
    if (!$config) {
        Response::error('配置不存在或无权限', 404);
    }
    
    try {
        $db->delete('user_configs', 'id = ?', [$configId]);
        
        Logger::info("用户删除配置: {$user['username']} - {$config['coin_name']}");
        
        Response::success(null, '配置删除成功');
        
    } catch (Exception $e) {
        Logger::error("删除用户配置失败: " . $e->getMessage());
        Response::error('删除失败', 500);
    }
}
?>