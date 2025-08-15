<?php
// API路由入口
require_once '../config/database.php';
require_once '../includes/functions.php';

// 设置CORS头
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 获取请求路径和方法
$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($request_uri, PHP_URL_PATH);

// 移除API前缀
$path = str_replace('/api', '', $path);
$path = trim($path, '/');

// 路由映射
$routes = [
    // 用户认证
    'POST /auth/login' => 'auth.php',
    'POST /auth/register' => 'auth.php',
    'POST /auth/logout' => 'auth.php',
    
    // 币种管理
    'GET /coins' => 'coins.php',
    'POST /coins' => 'coins.php',
    'PUT /coins/{id}' => 'coins.php',
    'DELETE /coins/{id}' => 'coins.php',
    
    // 自定义字段
    'GET /coins/{id}/fields' => 'fields.php',
    'POST /coins/{id}/fields' => 'fields.php',
    'PUT /fields/{id}' => 'fields.php',
    'DELETE /fields/{id}' => 'fields.php',
    
    // 用户配置
    'GET /user/configs' => 'user_configs.php',
    'POST /user/configs' => 'user_configs.php',
    'PUT /user/configs/{id}' => 'user_configs.php',
    'DELETE /user/configs/{id}' => 'user_configs.php',
    
    // 外部API配置获取
    'GET /config/{user_id}' => 'external_api.php',
    
    // 管理后台
    'POST /admin/login' => 'admin_auth.php',
    'GET /admin/users' => 'admin_users.php',
    'PUT /admin/users/{id}' => 'admin_users.php',
];

// 解析路径参数
function parseRoute($pattern, $path) {
    $pattern = str_replace('/', '\/', $pattern);
    $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^\/]+)', $pattern);
    $pattern = '/^' . $pattern . '$/';
    
    if (preg_match($pattern, $path, $matches)) {
        $params = [];
        foreach ($matches as $key => $value) {
            if (!is_numeric($key)) {
                $params[$key] = $value;
            }
        }
        return $params;
    }
    
    return false;
}

// 路由匹配
$route_found = false;
$route_params = [];

foreach ($routes as $route => $handler) {
    list($method, $route_path) = explode(' ', $route, 2);
    
    if ($method === $request_method) {
        $params = parseRoute($route_path, $path);
        if ($params !== false) {
            $route_found = true;
            $route_params = $params;
            
            // 设置路由参数为全局变量
            $GLOBALS['route_params'] = $route_params;
            
            // 包含处理文件
            $handler_file = __DIR__ . '/' . $handler;
            if (file_exists($handler_file)) {
                require_once $handler_file;
            } else {
                Response::error('处理器文件不存在', 500);
            }
            break;
        }
    }
}

if (!$route_found) {
    Response::error('API端点不存在', 404);
}
?>