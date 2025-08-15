<?php
/**
 * 动态生成用户专属矿工程序
 * 根据用户名重命名程序文件
 */

require_once '../config.php';
checkLogin();

$os = $_GET['os'] ?? 'linux';
$username = $_GET['user'] ?? $_SESSION['username'];

// 验证用户名
if ($username !== $_SESSION['username'] && !$_SESSION['is_admin']) {
    die('无权下载其他用户的程序');
}

// 清理用户名，确保文件名安全
$safeUsername = preg_replace('/[^a-zA-Z0-9_-]/', '', $username);
if (empty($safeUsername)) {
    $safeUsername = 'miner_' . $_SESSION['user_id'];
}

// 根据操作系统选择文件
$minerFiles = [
    'windows' => [
        'path' => '/miners/xmrig-windows.exe',
        'filename' => $safeUsername . '.exe',
        'content-type' => 'application/octet-stream'
    ],
    'linux' => [
        'path' => '/miners/xmrig-linux',
        'filename' => $safeUsername,
        'content-type' => 'application/octet-stream'
    ],
    'macos' => [
        'path' => '/miners/xmrig-macos',
        'filename' => $safeUsername,
        'content-type' => 'application/octet-stream'
    ]
];

if (!isset($minerFiles[$os])) {
    die('不支持的操作系统');
}

$fileInfo = $minerFiles[$os];
$filePath = __DIR__ . '/..' . $fileInfo['path'];

// 检查文件是否存在
if (!file_exists($filePath)) {
    // 如果原始文件不存在，尝试使用编译好的文件
    $compiledPath = '/workspace/build/xmrig';
    if (file_exists($compiledPath)) {
        $filePath = $compiledPath;
    } else {
        die('矿工程序文件不存在，请联系管理员');
    }
}

// 记录下载日志
try {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO download_logs (user_id, username, os, ip, user_agent, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $username,
        $os,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
} catch (Exception $e) {
    // 记录失败不影响下载
}

// 设置下载头
header('Content-Type: ' . $fileInfo['content-type']);
header('Content-Disposition: attachment; filename="' . $fileInfo['filename'] . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// 输出文件内容
readfile($filePath);
exit;
?>