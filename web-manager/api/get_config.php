<?php
/**
 * XMRig配置获取API
 * 用于矿工程序获取配置
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once '../config.php';

// 获取参数
$username = $_GET['user'] ?? '';
$workerName = $_GET['worker'] ?? '';
$token = $_GET['token'] ?? '';

// 验证参数
if (empty($username) || empty($workerName)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Missing parameters',
        'message' => 'user and worker parameters are required'
    ]);
    exit;
}

try {
    $db = getDB();
    
    // 查询用户
    $stmt = $db->prepare("SELECT id, status FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'error' => 'User not found',
            'message' => 'Invalid username'
        ]);
        exit;
    }
    
    if ($user['status'] != 1) {
        http_response_code(403);
        echo json_encode([
            'error' => 'User disabled',
            'message' => 'User account is disabled'
        ]);
        exit;
    }
    
    // 查询用户配置
    $stmt = $db->prepare("
        SELECT uc.*, c.name as coin_name, c.algorithm, c.public_config
        FROM user_configs uc
        JOIN coins c ON uc.coin_id = c.id
        WHERE uc.user_id = ? AND uc.worker_name = ? AND uc.status = 1
    ");
    $stmt->execute([$user['id'], $workerName]);
    $config = $stmt->fetch();
    
    if (!$config) {
        http_response_code(404);
        echo json_encode([
            'error' => 'Config not found',
            'message' => 'No configuration found for this worker'
        ]);
        exit;
    }
    
    // 解析配置
    $publicConfig = json_decode($config['public_config'], true) ?: [];
    $userConfig = json_decode($config['config_data'], true) ?: [];
    
    // 合并配置
    $finalConfig = array_merge($publicConfig, [
        'worker-id' => $workerName,
        'log-file' => $workerName . '.log'
    ]);
    
    // 处理矿池配置
    if (isset($finalConfig['pools']) && is_array($finalConfig['pools'])) {
        foreach ($finalConfig['pools'] as &$pool) {
            // 设置用户钱包
            if (isset($userConfig['wallet'])) {
                $pool['user'] = $userConfig['wallet'] . '.' . $workerName;
            }
            
            // 设置算法
            if (!empty($config['algorithm'])) {
                $pool['algo'] = $config['algorithm'];
            }
        }
    }
    
    // 添加用户自定义的暂停配置
    if (isset($userConfig['process_pause_names'])) {
        $finalConfig['process-pause-names'] = $userConfig['process_pause_names'];
    }
    
    if (isset($userConfig['window_pause_names'])) {
        $finalConfig['window-pause-names'] = $userConfig['window_pause_names'];
    }
    
    // 添加其他用户配置
    foreach ($userConfig as $key => $value) {
        if (!in_array($key, ['wallet', 'worker_name', 'process_pause_names', 'window_pause_names'])) {
            $finalConfig[$key] = $value;
        }
    }
    
    // 输出配置
    echo json_encode($finalConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => 'Internal server error occurred'
    ]);
}
?>