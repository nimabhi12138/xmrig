<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

header('Content-Type: application/json');

// 检查用户登录
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$currency_id = isset($_GET['currency_id']) ? intval($_GET['currency_id']) : 0;

if (!$currency_id) {
    echo json_encode([]);
    exit;
}

$db = Database::getInstance();

// 获取该币种的所有字段
$stmt = $db->query("SELECT * FROM custom_fields WHERE currency_id = :currency_id ORDER BY sort_order", 
                  ['currency_id' => $currency_id]);
$fields = $stmt->fetchAll();

echo json_encode($fields);