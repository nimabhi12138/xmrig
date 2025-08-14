<?php
/**
 * 退出登录
 */
session_start();

// 清除所有会话变量
$_SESSION = array();

// 销毁会话cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 销毁会话
session_destroy();

// 跳转到登录页
header('Location: login.php');
exit;
?>