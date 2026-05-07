<?php
require_once dirname(__DIR__) . '/config/config.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

// Set currency (switch via GET ?set=USD&back=/page)
if (isset($_GET['set'])) {
    $set   = strtoupper(preg_replace('/[^A-Z]/', '', $_GET['set']));
    $rates = getExchangeRates();
    if (isset($rates[$set])) {
        $_SESSION['currency'] = $set;
        setcookie('currency', $set, time() + 86400 * 30, '/');
    }
    $back = $_GET['back'] ?? '/';
    header('Location: ' . APP_URL . $back);
    exit;
}

// Update rates from CBR
if ($action === 'update_cbr' && isLoggedIn() && hasRole('superadmin')) {
    $ok = updateExchangeRatesFromCBR();
    echo json_encode(['success' => $ok]);
    exit;
}

// Return current rates as JSON
$rates = getExchangeRates();
$activeCur = getCurrentCurrency();
echo json_encode([
    'active'   => $activeCur,
    'rates'    => $rates,
    'updated'  => date('c'),
]);
