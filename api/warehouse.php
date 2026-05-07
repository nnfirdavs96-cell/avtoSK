<?php
require_once dirname(__DIR__) . '/config/config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) { http_response_code(401); echo json_encode(['error'=>'Auth required']); exit; }

$partNumber = trim($_GET['part'] ?? '');
if (!$partNumber) { echo json_encode(['error'=>'part required']); exit; }

if (!getSetting('warehouse_enabled','0')) {
    echo json_encode(['enabled'=>false]);
    exit;
}

$data = getWarehouseStock($partNumber);
if (!$data) {
    echo json_encode(['stock'=>0,'price'=>null,'eta'=>null,'source'=>'not_available']);
    exit;
}

echo json_encode([
    'stock'   => $data['warehouse_stock'],
    'price'   => $data['warehouse_price'],
    'eta'     => $data['warehouse_eta'],
    'source'  => 'warehouse_moscow',
]);
