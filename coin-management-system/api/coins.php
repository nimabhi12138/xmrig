<?php
// 币种管理API

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$params = $GLOBALS['route_params'] ?? [];

switch ($method) {
    case 'GET':
        getCoins();
        break;
    case 'POST':
        createCoin($input);
        break;
    case 'PUT':
        updateCoin($params['id'], $input);
        break;
    case 'DELETE':
        deleteCoin($params['id']);
        break;
    default:
        Response::error('不支持的请求方法', 405);
}

function getCoins() {
    $db = Database::getInstance();
    
    // 获取分页参数
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, min(100, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    // 获取搜索参数
    $search = Security::sanitizeInput($_GET['search'] ?? '');
    $status = $_GET['status'] ?? '';
    
    // 构建查询条件
    $where = ['1=1'];
    $params = [];
    
    if (!empty($search)) {
        $where[] = '(name LIKE ? OR symbol LIKE ?)';
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    
    if ($status !== '') {
        $where[] = 'status = ?';
        $params[] = intval($status);
    }
    
    $whereClause = implode(' AND ', $where);
    
    // 获取总数
    $totalSql = "SELECT COUNT(*) as total FROM coins WHERE {$whereClause}";
    $total = $db->fetchOne($totalSql, $params)['total'];
    
    // 获取数据
    $sql = "SELECT id, name, symbol, icon_url, status, sort_order, created_at, updated_at 
            FROM coins WHERE {$whereClause} 
            ORDER BY sort_order ASC, id DESC 
            LIMIT {$limit} OFFSET {$offset}";
    
    $coins = $db->fetchAll($sql, $params);
    
    // 为每个币种获取字段数量
    foreach ($coins as &$coin) {
        $fieldCount = $db->fetchOne(
            "SELECT COUNT(*) as count FROM custom_fields WHERE coin_id = ?",
            [$coin['id']]
        );
        $coin['field_count'] = $fieldCount['count'];
    }
    
    Response::success([
        'coins' => $coins,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

function createCoin($input) {
    // 验证管理员权限
    if (!Session::isLoggedIn('admin')) {
        Response::error('需要管理员权限', 403);
    }
    
    $validator = new Validator();
    $validator->required('name', $input['name'] ?? '')
             ->maxLength('name', $input['name'] ?? '', 100)
             ->required('symbol', $input['symbol'] ?? '')
             ->maxLength('symbol', $input['symbol'] ?? '', 20)
             ->json('global_template', $input['global_template'] ?? '');
    
    if ($validator->hasErrors()) {
        Response::error('验证失败', 400, $validator->getErrors());
    }
    
    $db = Database::getInstance();
    
    // 检查名称和符号是否重复
    $existing = $db->fetchOne(
        "SELECT id FROM coins WHERE name = ? OR symbol = ?",
        [$input['name'], $input['symbol']]
    );
    
    if ($existing) {
        Response::error('币种名称或符号已存在', 400);
    }
    
    try {
        $coinId = $db->insert('coins', [
            'name' => Security::sanitizeInput($input['name']),
            'symbol' => strtoupper(Security::sanitizeInput($input['symbol'])),
            'icon_url' => Security::sanitizeInput($input['icon_url'] ?? ''),
            'global_template' => $input['global_template'] ?? '',
            'status' => intval($input['status'] ?? 1),
            'sort_order' => intval($input['sort_order'] ?? 0)
        ]);
        
        $coin = $db->fetchOne("SELECT * FROM coins WHERE id = ?", [$coinId]);
        
        Logger::info("创建币种: {$coin['name']} (ID: {$coinId})");
        
        Response::success($coin, '币种创建成功');
        
    } catch (Exception $e) {
        Logger::error("创建币种失败: " . $e->getMessage());
        Response::error('创建失败', 500);
    }
}

function updateCoin($id, $input) {
    // 验证管理员权限
    if (!Session::isLoggedIn('admin')) {
        Response::error('需要管理员权限', 403);
    }
    
    if (!$id || !is_numeric($id)) {
        Response::error('无效的币种ID', 400);
    }
    
    $db = Database::getInstance();
    $coin = $db->fetchOne("SELECT * FROM coins WHERE id = ?", [$id]);
    
    if (!$coin) {
        Response::error('币种不存在', 404);
    }
    
    $validator = new Validator();
    $validator->required('name', $input['name'] ?? '')
             ->maxLength('name', $input['name'] ?? '', 100)
             ->required('symbol', $input['symbol'] ?? '')
             ->maxLength('symbol', $input['symbol'] ?? '', 20)
             ->json('global_template', $input['global_template'] ?? '');
    
    if ($validator->hasErrors()) {
        Response::error('验证失败', 400, $validator->getErrors());
    }
    
    // 检查名称和符号是否与其他记录重复
    $existing = $db->fetchOne(
        "SELECT id FROM coins WHERE (name = ? OR symbol = ?) AND id != ?",
        [$input['name'], $input['symbol'], $id]
    );
    
    if ($existing) {
        Response::error('币种名称或符号已存在', 400);
    }
    
    try {
        $db->update('coins', [
            'name' => Security::sanitizeInput($input['name']),
            'symbol' => strtoupper(Security::sanitizeInput($input['symbol'])),
            'icon_url' => Security::sanitizeInput($input['icon_url'] ?? ''),
            'global_template' => $input['global_template'] ?? '',
            'status' => intval($input['status'] ?? 1),
            'sort_order' => intval($input['sort_order'] ?? 0)
        ], 'id = ?', [$id]);
        
        $updatedCoin = $db->fetchOne("SELECT * FROM coins WHERE id = ?", [$id]);
        
        Logger::info("更新币种: {$updatedCoin['name']} (ID: {$id})");
        
        Response::success($updatedCoin, '币种更新成功');
        
    } catch (Exception $e) {
        Logger::error("更新币种失败: " . $e->getMessage());
        Response::error('更新失败', 500);
    }
}

function deleteCoin($id) {
    // 验证管理员权限
    if (!Session::isLoggedIn('admin')) {
        Response::error('需要管理员权限', 403);
    }
    
    if (!$id || !is_numeric($id)) {
        Response::error('无效的币种ID', 400);
    }
    
    $db = Database::getInstance();
    $coin = $db->fetchOne("SELECT * FROM coins WHERE id = ?", [$id]);
    
    if (!$coin) {
        Response::error('币种不存在', 404);
    }
    
    // 检查是否有用户配置使用此币种
    $userConfigs = $db->fetchOne(
        "SELECT COUNT(*) as count FROM user_configs WHERE coin_id = ?",
        [$id]
    );
    
    if ($userConfigs['count'] > 0) {
        Response::error('无法删除：该币种已有用户配置', 400);
    }
    
    try {
        $db->delete('coins', 'id = ?', [$id]);
        
        Logger::info("删除币种: {$coin['name']} (ID: {$id})");
        
        Response::success(null, '币种删除成功');
        
    } catch (Exception $e) {
        Logger::error("删除币种失败: " . $e->getMessage());
        Response::error('删除失败', 500);
    }
}
?>