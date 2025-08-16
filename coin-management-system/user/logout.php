<?php
require_once '../includes/functions.php';

// 记录登出日志
$user = Session::getCurrentUser('user');
if ($user) {
    Logger::info("用户登出: {$user['username']}");
}

// 销毁会话
Session::destroy();

// 重定向到登录页面
header('Location: login.php');
exit;
?>