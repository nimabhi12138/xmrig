<?php
// 管理员认证API

$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('只支持POST请求', 405);
}

$validator = new Validator();
$validator->required('username', $input['username'] ?? '')
         ->required('password', $input['password'] ?? '');

if ($validator->hasErrors()) {
    Response::error('验证失败', 400, $validator->getErrors());
}

$username = Security::sanitizeInput($input['username']);
$password = $input['password'];

$db = Database::getInstance();
$admin = $db->fetchOne(
    "SELECT id, username, email, password FROM admins WHERE username = ?",
    [$username]
);

if (!$admin || !Security::verifyPassword($password, $admin['password'])) {
    Response::error('用户名或密码错误', 401);
}

// 设置管理员会话
Session::set('admin_id', $admin['id']);
Session::set('admin_username', $admin['username']);

// 更新最后登录时间
$db->update('admins', ['updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$admin['id']]);

Logger::info("管理员登录: {$admin['username']}");

Response::success([
    'admin' => [
        'id' => $admin['id'],
        'username' => $admin['username'],
        'email' => $admin['email']
    ]
], '登录成功');
?>