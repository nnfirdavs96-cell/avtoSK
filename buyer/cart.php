<?php
require_once dirname(__DIR__) . '/config/config.php';
requireRole('buyer');

$user = getCurrentUser();
$db   = getDB();
$csrf = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        flashMessage('danger','Ошибка безопасности.');
        redirect(APP_URL . '/buyer/cart.php');
    }
    $address = trim($_POST['address'] ?? '');
    $notes   = trim($_POST['notes'] ?? '');
    if (empty($address)) { flashMessage('danger','Укажите адрес доставки.'); redirect(APP_URL.'/buyer/cart.php'); }
    $cartStmt = $db->prepare(
        "SELECT c.*, p.price, p.stock, p.name AS part_name
         FROM cart c JOIN parts p ON p.id=c.part_id
         WHERE c.user_id=? AND p.is_active=1"
    );
    $cartStmt->execute([$user['id']]);
    $cartItems = $cartStmt->fetchAll();
    if (empty($cartItems)) { flashMessage('warning','Корзина пуста.'); redirect(APP_URL.'/buyer/cart.php'); }
    $total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cartItems));
    $db->beginTransaction();
    try {
        $ordStmt = $db->prepare("INSERT INTO orders (user_id,total_amount,shipping_address,notes) VALUES (?,?,?,?)");
        $ordStmt->execute([$user['id'], $total, $address, $notes ?: null]);
        $orderId = (int)$db->lastInsertId();
        $itmStmt = $db->prepare("INSERT INTO order_items (order_id,part_id,quantity,unit_price) VALUES (?,?,?,?)");
        foreach ($cartItems as $item) $itmStmt->execute([$orderId, $item['part_id'], $item['quantity'], $item['price']]);
        $db->prepare("DELETE FROM cart WHERE user_id=?")->execute([$user['id']]);
        $db->commit();
        flashMessage('success', "Заказ #$orderId оформлен! Мы свяжемся с вами.");
        redirect(APP_URL . '/buyer/orders.php?id=' . $orderId);
    } catch (Exception $e) {
        $db->rollBack();
        flashMessage('danger','Ошибка оформления. Попробуйте снова.');
        redirect(APP_URL.'/buyer/cart.php');
    }
}

$cartStmt = $db->prepare(
    "SELECT c.id AS cart_id, c.part_id, c.quantity, p.name, p.part_number, p.price, p.stock, b.name AS brand_name
     FROM cart c JOIN parts p ON p.id=c.part_id LEFT JOIN brands b ON b.id=p.brand_id
     WHERE c.user_id=? AND p.is_active=1 ORDER BY c.added_at DESC"
);
$cartStmt->execute([$user['id']]);
$cartItems = $cartStmt->fetchAll();
$cartTotal = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cartItems));
$pageTitle = 'Корзина';

require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/nav.php';
?>

<div class="az-admin-page">
<div class="az-admin-navbar">
  <a href="<?= APP_URL ?>/index.php" class="az-admin-logo">АВТО<span>ЗАПЧАСТЬ</span></a>
  <ul class="az-admin-nav-links">
    <li><a href="<?= APP_URL ?>/catalog/index.php">Каталог</a></li>
    <li><a href="<?= APP_URL ?>/auth/logout.php">Выйти</a></li>
  </ul>
</div>
<div class="dash-layout">
  <div class="dash-sidebar"><?php renderNav(); ?></div>
  <div class="dash-main">
    <div class="dash-heading">КОРЗИНА</div>

    <?php if (empty($cartItems)): ?>
    <div class="az-no-data">
      <div class="az-no-data-icon">🛒</div>
      <p>Ваша корзина пуста.</p>
      <a href="<?= APP_URL ?>/catalog/index.php" class="az-admin-btn az-admin-btn-primary" style="margin-top:16px;">Перейти в каталог</a>
    </div>
    <?php else: ?>
    <div class="az-cart-grid">
      <div>
        <div class="az-card">
          <div class="az-card-header"><h3>ТОВАРЫ (<?= count($cartItems) ?>)</h3></div>
          <div class="az-table-wrap">
            <table class="az-table">
              <thead>
                <tr><th>Товар</th><th style="text-align:center;">Кол-во</th><th style="text-align:right;">Цена</th><th style="text-align:right;">Сумма</th><th></th></tr>
              </thead>
              <tbody>
                <?php foreach ($cartItems as $item): ?>
                <tr data-cart-row="<?= (int)$item['part_id'] ?>">
                  <td>
                    <div class="mono" style="color:var(--accent);margin-bottom:2px;"><?= sanitize($item['part_number']) ?></div>
                    <a href="<?= APP_URL ?>/catalog/part.php?id=<?= $item['part_id'] ?>" style="color:var(--text-primary);text-decoration:none;font-size:13px;"><?= sanitize(truncate($item['name'],50)) ?></a>
                    <div style="font-size:11px;color:var(--text-muted);"><?= sanitize($item['brand_name']) ?></div>
                  </td>
                  <td style="text-align:center;">
                    <div class="az-qty-control" style="justify-content:center;">
                      <button class="az-qty-btn" data-qty-minus>−</button>
                      <input type="number" class="az-qty-num" data-qty-input value="<?= (int)$item['quantity'] ?>" min="1" max="99" readonly>
                      <button class="az-qty-btn" data-qty-plus>+</button>
                    </div>
                  </td>
                  <td style="text-align:right;font-family:monospace;font-size:13px;color:var(--text-secondary);"><?= formatPrice($item['price']) ?></td>
                  <td style="text-align:right;font-family:monospace;color:var(--accent);" data-row-subtotal><?= formatPrice($item['price'] * $item['quantity']) ?></td>
                  <td><button class="az-admin-btn az-admin-btn-danger az-admin-btn-sm" data-cart-remove="<?= (int)$item['part_id'] ?>">✕</button></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div>
        <div class="az-order-summary" style="background:var(--bg-card);border:1px solid var(--border);">
          <div style="font-size:11px;text-transform:uppercase;letter-spacing:.1em;color:var(--text-muted);margin-bottom:12px;">// Итого</div>
          <?php foreach ($cartItems as $item): ?>
          <div class="az-summary-row" style="border-color:var(--border);color:var(--text-secondary);">
            <span style="font-size:12px;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= sanitize(truncate($item['name'],28)) ?></span>
            <span style="font-family:monospace;font-size:12px;"><?= $item['quantity'] ?> × <?= formatPrice($item['price']) ?></span>
          </div>
          <?php endforeach; ?>
          <div class="az-summary-total" style="border-color:var(--accent);">
            <span style="font-size:11px;text-transform:uppercase;color:var(--text-muted);">Итого</span>
            <span id="cart-total" style="font-size:1.6rem;font-weight:900;color:var(--accent);"><?= formatPrice($cartTotal) ?></span>
          </div>
          <form method="post" action="" style="margin-top:16px;">
            <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">
            <input type="hidden" name="checkout" value="1">
            <div class="az-form-group">
              <label class="az-form-label">Адрес доставки *</label>
              <textarea name="address" class="az-form-textarea" rows="3" placeholder="г. Москва, ул. Пример, д. 1" required></textarea>
            </div>
            <div class="az-form-group">
              <label class="az-form-label">Примечания</label>
              <textarea name="notes" class="az-form-textarea" rows="2" placeholder="Удобное время..."></textarea>
            </div>
            <button type="submit" class="az-admin-btn az-admin-btn-primary az-admin-btn-block az-admin-btn-lg">ОФОРМИТЬ ЗАКАЗ</button>
          </form>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
