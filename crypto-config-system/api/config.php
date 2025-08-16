<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

require_once '../config/config.php';
require_once '../includes/Database.php';

// 获取用户ID和token
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$token = isset($_GET['token']) ? $_GET['token'] : '';

// 也支持通过Authorization header传递token
$headers = getallheaders();
if (isset($headers['Authorization'])) {
    $auth = $headers['Authorization'];
    if (strpos($auth, 'Bearer ') === 0) {
        $token = substr($auth, 7);
    }
}

// 验证参数
if (!$user_id || !$token) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Missing required parameters',
        'message' => 'user_id and token are required'
    ]);
    exit;
}

$db = Database::getInstance();

// 验证token
$stmt = $db->query("SELECT id FROM users WHERE id = :id AND api_token = :token", [
    'id' => $user_id,
    'token' => $token
]);

if (!$stmt->fetch()) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Unauthorized',
        'message' => 'Invalid user_id or token'
    ]);
    exit;
}

// 获取用户配置
$stmt = $db->query("
    SELECT uc.*, c.name as currency_name, c.symbol as currency_symbol
    FROM user_configs uc
    JOIN currencies c ON uc.currency_id = c.id
    WHERE uc.user_id = :user_id
", ['user_id' => $user_id]);

$configs = [];
while ($row = $stmt->fetch()) {
    $config = [
        'currency' => [
            'name' => $row['currency_name'],
            'symbol' => $row['currency_symbol']
        ],
        'config' => json_decode($row['processed_config'], true),
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at']
    ];
    $configs[] = $config;
}

if (empty($configs)) {
    http_response_code(404);
    echo json_encode([
        'error' => 'No configuration found',
        'message' => 'User has not configured any currency yet'
    ]);
    exit;
}

// 返回配置
echo json_encode([
    'success' => true,
    'user_id' => $user_id,
    'configurations' => $configs,
    'timestamp' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);