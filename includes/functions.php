<?php
/**
 * АвтоЗапчасть — Helper Functions
 */

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    if (isset($_SESSION['user_data'])) return $_SESSION['user_data'];
    $db   = getDB();
    $stmt = $db->prepare("SELECT id, username, email, role, phone, is_active FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['user_data'] = $user;
        return $user;
    }
    session_destroy();
    return null;
}

function hasRole($role): bool {
    if (!isLoggedIn()) return false;
    $roles = is_array($role) ? $role : [$role];
    return in_array($_SESSION['role'] ?? '', $roles, true);
}

function requireRole($role): void {
    if (!isLoggedIn()) {
        redirect(APP_URL . '/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
    $roles = is_array($role) ? $role : [$role];
    if ($_SESSION['role'] === 'superadmin') return;
    if (in_array('superadmin', $roles, true)) {
        flashMessage('danger', 'Доступ запрещён.');
        redirect(APP_URL . '/index.php');
    }
    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        flashMessage('danger', 'Доступ запрещён. Недостаточно прав.');
        redirect(APP_URL . '/index.php');
    }
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function sanitize($input): string {
    return htmlspecialchars((string)$input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function formatPrice($price): string {
    return number_format((float)$price, 0, ',', ' ') . ' ₽';
}

function formatPriceInCurrency(float $priceRub, ?string $currency = null): string {
    if (!$currency) $currency = getCurrentCurrency();
    $rates = getExchangeRates();
    if ($currency === 'RUB' || !isset($rates[$currency])) {
        return formatPrice($priceRub);
    }
    $rate      = (float)$rates[$currency]['rate'];
    $converted = $priceRub * $rate;
    $symbol    = $rates[$currency]['symbol'];
    if (in_array($currency, ['USD','EUR'])) {
        return $symbol . number_format($converted, 2, '.', ' ');
    }
    return number_format($converted, 0, ',', ' ') . ' ' . $symbol;
}

function getCurrentCurrency(): string {
    if (isset($_SESSION['currency'])) return $_SESSION['currency'];
    if (isset($_COOKIE['currency'])) {
        $_SESSION['currency'] = $_COOKIE['currency'];
        return $_COOKIE['currency'];
    }
    return getSetting('site_currency', 'RUB');
}

function getExchangeRates(): array {
    static $rates = null;
    if ($rates !== null) return $rates;
    try {
        $db   = getDB();
        $stmt = $db->query("SELECT currency, name, symbol, rate FROM exchange_rates WHERE is_active = 1 ORDER BY currency");
        $rows = $stmt->fetchAll();
        $rates = [];
        foreach ($rows as $row) {
            $rates[$row['currency']] = $row;
        }
    } catch (Exception $e) {
        $rates = ['RUB' => ['currency'=>'RUB','name'=>'Российский рубль','symbol'=>'₽','rate'=>1]];
    }
    return $rates;
}

function getSetting(string $key, string $default = ''): string {
    static $settings = null;
    if ($settings === null) {
        try {
            $db   = getDB();
            $stmt = $db->query("SELECT `key`, `value` FROM site_settings");
            $settings = [];
            foreach ($stmt->fetchAll() as $row) {
                $settings[$row['key']] = $row['value'];
            }
        } catch (Exception $e) {
            $settings = [];
        }
    }
    return $settings[$key] ?? $default;
}

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function flashMessage(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function getCartCount(): int {
    if (!isLoggedIn()) return 0;
    try {
        $db   = getDB();
        $stmt = $db->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

function getCategories(): array {
    try {
        $db   = getDB();
        $stmt = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order, name");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function getCategoryTree(array $categories, ?int $parentId = null): array {
    $tree = [];
    foreach ($categories as $cat) {
        $catParent = $cat['parent_id'] === null ? null : (int)$cat['parent_id'];
        if ($catParent === $parentId) {
            $cat['children'] = getCategoryTree($categories, (int)$cat['id']);
            $tree[] = $cat;
        }
    }
    return $tree;
}

function getBrands(): array {
    try {
        $db   = getDB();
        $stmt = $db->query("SELECT * FROM brands WHERE is_active = 1 ORDER BY name");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function getOrderStatusLabel(string $status): string {
    return [
        'pending'    => 'Новый',
        'processing' => 'В обработке',
        'shipped'    => 'Отправлен',
        'delivered'  => 'Доставлен',
        'cancelled'  => 'Отменён',
    ][$status] ?? $status;
}

function getOrderStatusClass(string $status): string {
    return [
        'pending'    => 'warning',
        'processing' => 'info',
        'shipped'    => 'primary',
        'delivered'  => 'success',
        'cancelled'  => 'danger',
    ][$status] ?? 'secondary';
}

function truncate(string $str, int $len = 100, string $suffix = '...'): string {
    if (mb_strlen($str) <= $len) return $str;
    return mb_substr($str, 0, $len) . $suffix;
}

function getStockStatus(int $stock): array {
    if ($stock <= 0) return ['label' => 'Нет в наличии', 'class' => 'out-of-stock'];
    if ($stock <= 5) return ['label' => 'Заканчивается',  'class' => 'low-stock'];
    return ['label' => 'В наличии', 'class' => 'in-stock'];
}

/**
 * Fetch exchange rates from Russian Central Bank (CBR)
 * Returns true on success, false on failure
 */
function updateExchangeRatesFromCBR(): bool {
    try {
        $xml = @file_get_contents('https://www.cbr.ru/scripts/XML_daily.asp');
        if (!$xml) return false;
        $doc = new SimpleXMLElement($xml);
        $db  = getDB();
        foreach ($doc->Valute as $v) {
            $code    = (string)$v->CharCode;
            $nominal = (int)$v->Nominal;
            $value   = (float)str_replace(',', '.', (string)$v->Value);
            $rateToRub = $value / $nominal;
            $rateInverted = 1 / $rateToRub;
            $db->prepare(
                "UPDATE exchange_rates SET rate = ?, updated_at = NOW() WHERE currency = ?"
            )->execute([$rateInverted, $code]);
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Check Moscow warehouse stock for a part number
 */
function getWarehouseStock(string $partNumber): ?array {
    if (!getSetting('warehouse_enabled', '0')) return null;
    $apiUrl = getSetting('warehouse_api_url', '');
    $apiKey = getSetting('warehouse_api_key', '');
    if (!$apiUrl) return null;
    try {
        $db   = getDB();
        $stmt = $db->prepare(
            "SELECT * FROM warehouse_cache WHERE part_number = ? AND last_checked > DATE_SUB(NOW(), INTERVAL 6 HOUR)"
        );
        $stmt->execute([$partNumber]);
        $cached = $stmt->fetch();
        if ($cached) return $cached;
        // Fetch from API
        $url  = rtrim($apiUrl, '/') . '/stock/' . urlencode($partNumber);
        $opts = ['http' => [
            'timeout' => 5,
            'header'  => "Authorization: Bearer $apiKey\r\nContent-Type: application/json\r\n",
        ]];
        $ctx  = stream_context_create($opts);
        $resp = @file_get_contents($url, false, $ctx);
        if (!$resp) return null;
        $data = json_decode($resp, true);
        if (!$data) return null;
        $warehouseStock = (int)($data['stock'] ?? $data['quantity'] ?? 0);
        $warehousePrice = isset($data['price']) ? (float)$data['price'] : null;
        $warehouseEta   = $data['eta'] ?? $data['delivery_time'] ?? null;
        $db->prepare(
            "INSERT INTO warehouse_cache (part_number, warehouse_stock, warehouse_price, warehouse_eta, raw_response)
             VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE
             warehouse_stock=VALUES(warehouse_stock), warehouse_price=VALUES(warehouse_price),
             warehouse_eta=VALUES(warehouse_eta), raw_response=VALUES(raw_response),
             last_checked=NOW()"
        )->execute([$partNumber, $warehouseStock, $warehousePrice, $warehouseEta, $resp]);
        return [
            'part_number'      => $partNumber,
            'warehouse_stock'  => $warehouseStock,
            'warehouse_price'  => $warehousePrice,
            'warehouse_eta'    => $warehouseEta,
        ];
    } catch (Exception $e) {
        return null;
    }
}
