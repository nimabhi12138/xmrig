<?php
// 用户认证API

$input = json_decode(file_get_contents('php://input'), true);
$action = basename($_SERVER['REQUEST_URI']);

switch ($action) {
    case 'login':
        handleLogin($input);
        break;
    case 'register':
        handleRegister($input);
        break;
    case 'logout':
        handleLogout();
        break;
    default:
        Response::error('无效的操作', 400);
}

function handleLogin($input) {
    $validator = new Validator();
    $validator->required('username', $input['username'] ?? '')
             ->required('password', $input['password'] ?? '');
    
    if ($validator->hasErrors()) {
        Response::error('验证失败', 400, $validator->getErrors());
    }
    
    $username = Security::sanitizeInput($input['username']);
    $password = $input['password'];
    
    $db = Database::getInstance();
    $user = $db->fetchOne(
        "SELECT id, username, email, password, status FROM users WHERE username = ? OR email = ?",
        [$username, $username]
    );
    
    if (!$user || !Security::verifyPassword($password, $user['password'])) {
        Response::error('用户名或密码错误', 401);
    }
    
    if ($user['status'] != 1) {
        Response::error('账户已被禁用', 403);
    }
    
    // 设置会话
    Session::set('user_id', $user['id']);
    Session::set('username', $user['username']);
    
    // 更新最后登录时间
    $db->update('users', ['updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
    
    Logger::info("用户登录: {$user['username']}");
    
    Response::success([
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email']
        ]
    ], '登录成功');
}

function handleRegister($input) {
    // 检查是否允许注册
    $db = Database::getInstance();
    $config = $db->fetchOne("SELECT config_value FROM system_config WHERE config_key = 'registration_enabled'");
    if (!$config || $config['config_value'] != '1') {
        Response::error('当前不允许用户注册', 403);
    }
    
    $validator = new Validator();
    $validator->required('username', $input['username'] ?? '')
             ->minLength('username', $input['username'] ?? '', 3)
             ->maxLength('username', $input['username'] ?? '', 50)
             ->required('email', $input['email'] ?? '')
             ->email('email', $input['email'] ?? '')
             ->required('password', $input['password'] ?? '')
             ->minLength('password', $input['password'] ?? '', 6);
    
    if (!$validator->hasErrors()) {
        $validator->unique('username', $input['username'], 'users')
                 ->unique('email', $input['email'], 'users');
    }
    
    if ($validator->hasErrors()) {
        Response::error('验证失败', 400, $validator->getErrors());
    }
    
    $username = Security::sanitizeInput($input['username']);
    $email = Security::sanitizeInput($input['email']);
    $password = Security::hashPassword($input['password']);
    $apiToken = Security::generateToken();
    
    try {
        $userId = $db->insert('users', [
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'api_token' => $apiToken
        ]);
        
        Logger::info("新用户注册: {$username}");
        
        Response::success([
            'user' => [
                'id' => $userId,
                'username' => $username,
                'email' => $email,
                'api_token' => $apiToken
            ]
        ], '注册成功');
        
    } catch (Exception $e) {
        Logger::error("用户注册失败: " . $e->getMessage());
        Response::error('注册失败', 500);
    }
}

function handleLogout() {
    $user = Session::getCurrentUser();
    if ($user) {
        Logger::info("用户登出: {$user['username']}");
    }
    
    Session::destroy();
    Response::success(null, '登出成功');
}
?>