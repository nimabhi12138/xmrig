<?php
require_once '../includes/functions.php';

// 记录登出日志
$admin = Session::getCurrentUser('admin');
if ($admin) {
    Logger::info("管理员登出: {$admin['username']}");
}

// 销毁会话
Session::destroy();

// 重定向到登录页面
header('Location: login.php');
exit;
?>