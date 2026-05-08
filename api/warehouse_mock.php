<?php
/**
 * Mock warehouse API for testing.
 * Responds to: GET /api/warehouse_mock.php/stock/{part_number}
 * Returns random stock data keyed by part number.
 */
header('Content-Type: application/json; charset=utf-8');

// Part number comes from PATH_INFO: /stock/PART-123
$path = $_SERVER['PATH_INFO'] ?? '';
$partNumber = trim(str_replace('/stock/', '', $path), '/');

if (!$partNumber) {
    echo json_encode(['error' => 'part number required']);
    exit;
}

// Deterministic but varied data based on part number
$seed = crc32($partNumber);
mt_srand($seed);
$stock = mt_rand(0, 30);
$price = $stock > 0 ? mt_rand(800, 15000) : null;
$eta   = $stock > 0 ? null : (mt_rand(0, 1) ? '3-5 дней' : '7-10 дней');

echo json_encode([
    'stock'         => $stock,
    'price'         => $price,
    'eta'           => $eta,
    'delivery_time' => $eta,
    'part_number'   => $partNumber,
    'source'        => 'mock',
]);
