<?php
// 外部API配置获取端点
// 路径: /api/config/{user_id}?token=xxx

$params = $GLOBALS['route_params'] ?? [];
$userId = $params['user_id'] ?? null;
$token = $_GET['token'] ?? '';

if (!$userId || !is_numeric($userId)) {
    Response::error('无效的用户ID', 400);
}

if (empty($token)) {
    Response::error('缺少访问令牌', 401);
}

// 验证令牌
$user = Security::validateApiToken($token);
if (!$user) {
    Response::error('无效的访问令牌', 401);
}

// 验证用户ID匹配
if ($user['id'] != $userId) {
    Response::error('用户ID与令牌不匹配', 403);
}

$db = Database::getInstance();

// 获取用户的所有配置
$configs = $db->fetchAll(
    "SELECT uc.*, c.name as coin_name, c.symbol as coin_symbol 
     FROM user_configs uc 
     JOIN coins c ON uc.coin_id = c.id 
     WHERE uc.user_id = ? AND c.status = 1",
    [$userId]
);

if (empty($configs)) {
    Response::error('未找到用户配置', 404);
}

// 构建返回数据
$result = [];

foreach ($configs as $config) {
    // 如果没有生成的配置，则现场生成
    if (empty($config['generated_config'])) {
        // 获取币种信息
        $coin = $db->fetchOne("SELECT * FROM coins WHERE id = ?", [$config['coin_id']]);
        if (!$coin || empty($coin['global_template'])) {
            continue;
        }
        
        // 解析字段值
        $fieldValues = json_decode($config['field_values'], true);
        if (!$fieldValues) {
            continue;
        }
        
        try {
            // 生成配置
            $generatedConfig = TemplateEngine::replacePlaceholders(
                $coin['global_template'], 
                $fieldValues
            );
            
            // 保存生成的配置
            $db->update('user_configs', [
                'generated_config' => $generatedConfig
            ], 'id = ?', [$config['id']]);
            
            $config['generated_config'] = $generatedConfig;
            
        } catch (Exception $e) {
            Logger::error("生成配置失败: " . $e->getMessage());
            continue;
        }
    }
    
    // 解析生成的配置
    $generatedData = json_decode($config['generated_config'], true);
    
    $result[] = [
        'coin' => [
            'id' => $config['coin_id'],
            'name' => $config['coin_name'],
            'symbol' => $config['coin_symbol']
        ],
        'config' => $generatedData,
        'updated_at' => $config['updated_at']
    ];
}

// 记录API访问日志
Logger::info("API访问: 用户ID {$userId}, 配置数量 " . count($result));

// 设置缓存头（可根据需要调整）
header('Cache-Control: private, max-age=300'); // 5分钟缓存
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 300) . ' GMT');

Response::success([
    'user_id' => $userId,
    'configs' => $result,
    'total' => count($result),
    'generated_at' => date('Y-m-d H:i:s')
], '配置获取成功');
?>