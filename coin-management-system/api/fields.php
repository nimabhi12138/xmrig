<?php
// 自定义字段管理API

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$params = $GLOBALS['route_params'] ?? [];

switch ($method) {
    case 'GET':
        if (isset($params['id'])) {
            // 获取特定币种的字段
            getCoinFields($params['id']);
        } else {
            Response::error('缺少币种ID', 400);
        }
        break;
    case 'POST':
        if (isset($params['id'])) {
            // 为特定币种创建字段
            createField($params['id'], $input);
        } else {
            Response::error('缺少币种ID', 400);
        }
        break;
    case 'PUT':
        if (isset($params['id'])) {
            // 更新字段
            updateField($params['id'], $input);
        } else {
            Response::error('缺少字段ID', 400);
        }
        break;
    case 'DELETE':
        if (isset($params['id'])) {
            // 删除字段
            deleteField($params['id']);
        } else {
            Response::error('缺少字段ID', 400);
        }
        break;
    default:
        Response::error('不支持的请求方法', 405);
}

function getCoinFields($coinId) {
    // 验证管理员权限
    if (!Session::isLoggedIn('admin')) {
        Response::error('需要管理员权限', 403);
    }
    
    $db = Database::getInstance();
    
    // 检查币种是否存在
    $coin = $db->fetchOne("SELECT * FROM coins WHERE id = ?", [$coinId]);
    if (!$coin) {
        Response::error('币种不存在', 404);
    }
    
    // 获取字段列表
    $fields = $db->fetchAll(
        "SELECT * FROM custom_fields WHERE coin_id = ? ORDER BY sort_order ASC, id ASC",
        [$coinId]
    );
    
    Response::success([
        'coin' => $coin,
        'fields' => $fields
    ]);
}

function createField($coinId, $input) {
    // 验证管理员权限
    if (!Session::isLoggedIn('admin')) {
        Response::error('需要管理员权限', 403);
    }
    
    $validator = new Validator();
    $validator->required('title', $input['title'] ?? '')
             ->maxLength('title', $input['title'] ?? '', 100)
             ->required('field_type', $input['field_type'] ?? '')
             ->required('placeholder_key', $input['placeholder_key'] ?? '')
             ->maxLength('placeholder_key', $input['placeholder_key'] ?? '', 50);
    
    if ($validator->hasErrors()) {
        Response::error('验证失败', 400, $validator->getErrors());
    }
    
    $db = Database::getInstance();
    
    // 检查币种是否存在
    $coin = $db->fetchOne("SELECT * FROM coins WHERE id = ?", [$coinId]);
    if (!$coin) {
        Response::error('币种不存在', 404);
    }
    
    // 检查占位符是否重复
    $existing = $db->fetchOne(
        "SELECT id FROM custom_fields WHERE coin_id = ? AND placeholder_key = ?",
        [$coinId, $input['placeholder_key']]
    );
    
    if ($existing) {
        Response::error('占位符已存在', 400);
    }
    
    try {
        $fieldId = $db->insert('custom_fields', [
            'coin_id' => $coinId,
            'title' => Security::sanitizeInput($input['title']),
            'field_type' => $input['field_type'],
            'placeholder_key' => strtoupper(Security::sanitizeInput($input['placeholder_key'])),
            'is_required' => intval($input['is_required'] ?? 0),
            'options' => !empty($input['options']) ? json_encode($input['options']) : null,
            'sort_order' => intval($input['sort_order'] ?? 0)
        ]);
        
        $field = $db->fetchOne("SELECT * FROM custom_fields WHERE id = ?", [$fieldId]);
        
        Logger::info("创建自定义字段: {$field['title']} (币种ID: {$coinId})");
        
        Response::success($field, '字段创建成功');
        
    } catch (Exception $e) {
        Logger::error("创建字段失败: " . $e->getMessage());
        Response::error('创建失败', 500);
    }
}

function updateField($fieldId, $input) {
    // 验证管理员权限
    if (!Session::isLoggedIn('admin')) {
        Response::error('需要管理员权限', 403);
    }
    
    if (!$fieldId || !is_numeric($fieldId)) {
        Response::error('无效的字段ID', 400);
    }
    
    $db = Database::getInstance();
    $field = $db->fetchOne("SELECT * FROM custom_fields WHERE id = ?", [$fieldId]);
    
    if (!$field) {
        Response::error('字段不存在', 404);
    }
    
    $validator = new Validator();
    $validator->required('title', $input['title'] ?? '')
             ->maxLength('title', $input['title'] ?? '', 100)
             ->required('field_type', $input['field_type'] ?? '')
             ->required('placeholder_key', $input['placeholder_key'] ?? '')
             ->maxLength('placeholder_key', $input['placeholder_key'] ?? '', 50);
    
    if ($validator->hasErrors()) {
        Response::error('验证失败', 400, $validator->getErrors());
    }
    
    // 检查占位符是否与其他字段重复
    $existing = $db->fetchOne(
        "SELECT id FROM custom_fields WHERE coin_id = ? AND placeholder_key = ? AND id != ?",
        [$field['coin_id'], $input['placeholder_key'], $fieldId]
    );
    
    if ($existing) {
        Response::error('占位符已存在', 400);
    }
    
    try {
        $db->update('custom_fields', [
            'title' => Security::sanitizeInput($input['title']),
            'field_type' => $input['field_type'],
            'placeholder_key' => strtoupper(Security::sanitizeInput($input['placeholder_key'])),
            'is_required' => intval($input['is_required'] ?? 0),
            'options' => !empty($input['options']) ? json_encode($input['options']) : null,
            'sort_order' => intval($input['sort_order'] ?? 0)
        ], 'id = ?', [$fieldId]);
        
        $updatedField = $db->fetchOne("SELECT * FROM custom_fields WHERE id = ?", [$fieldId]);
        
        Logger::info("更新自定义字段: {$updatedField['title']} (ID: {$fieldId})");
        
        Response::success($updatedField, '字段更新成功');
        
    } catch (Exception $e) {
        Logger::error("更新字段失败: " . $e->getMessage());
        Response::error('更新失败', 500);
    }
}

function deleteField($fieldId) {
    // 验证管理员权限
    if (!Session::isLoggedIn('admin')) {
        Response::error('需要管理员权限', 403);
    }
    
    if (!$fieldId || !is_numeric($fieldId)) {
        Response::error('无效的字段ID', 400);
    }
    
    $db = Database::getInstance();
    $field = $db->fetchOne("SELECT * FROM custom_fields WHERE id = ?", [$fieldId]);
    
    if (!$field) {
        Response::error('字段不存在', 404);
    }
    
    try {
        $db->delete('custom_fields', 'id = ?', [$fieldId]);
        
        Logger::info("删除自定义字段: {$field['title']} (ID: {$fieldId})");
        
        Response::success(null, '字段删除成功');
        
    } catch (Exception $e) {
        Logger::error("删除字段失败: " . $e->getMessage());
        Response::error('删除失败', 500);
    }
}
?>