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

<!--breadcrumb area start-->
<div class="breadcrumb_area">
  <div class="container">
    <div class="row"><div class="col-12">
      <div class="breadcrumb_content">
        <ul>
          <li><a href="<?= APP_URL ?>/index.php">Главная</a></li>
          <li><a href="<?= APP_URL ?>/buyer/index.php">Кабинет</a></li>
          <li class="active">Корзина</li>
        </ul>
      </div>
    </div></div>
  </div>
</div>
<!--breadcrumb area end-->

<div class="account_area">
  <div class="container">
    <div class="row">

      <!-- Sidebar -->
      <div class="col-lg-3 col-md-4">
        <div class="account_sidebar">
          <div class="widget_list">
            <h3 class="widget_title">Мой аккаунт</h3>
            <?php renderNav(); ?>
          </div>
        </div>
      </div>

      <!-- Main content -->
      <div class="col-lg-9 col-md-8">

        <div style="font-size:18px;font-weight:700;color:#333;margin-bottom:24px;padding-bottom:12px;border-bottom:2px solid #e74c3c;">
          Корзина <?php if (!empty($cartItems)): ?><span style="font-size:14px;color:#888;font-weight:400;">(<?= count($cartItems) ?> позиций)</span><?php endif; ?>
        </div>

        <?php if (empty($cartItems)): ?>
        <div class="account_no_data">
          <div class="account_no_data_icon">🛒</div>
          <p>Ваша корзина пуста.</p>
          <a href="<?= APP_URL ?>/catalog/index.php" class="account_btn account_btn_primary" style="margin-top:16px;display:inline-block;padding:12px 30px;">Перейти в каталог</a>
        </div>

        <?php else: ?>
        <div class="account_cart_grid">

          <!-- Cart items -->
          <div>
            <div class="account_card">
              <div class="account_card_header">
                <h3>Товары</h3>
              </div>
              <div style="overflow-x:auto;">
                <table class="account_table">
                  <thead>
                    <tr>
                      <th>Товар</th>
                      <th style="text-align:center;">Кол-во</th>
                      <th style="text-align:right;">Цена</th>
                      <th style="text-align:right;">Сумма</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($cartItems as $item): ?>
                    <tr data-cart-row="<?= (int)$item['part_id'] ?>">
                      <td>
                        <div style="color:#e74c3c;font-family:monospace;font-size:12px;margin-bottom:2px;"><?= sanitize($item['part_number']) ?></div>
                        <a href="<?= APP_URL ?>/catalog/part.php?id=<?= $item['part_id'] ?>" style="color:#333;text-decoration:none;font-size:13px;font-weight:500;"><?= sanitize(truncate($item['name'],50)) ?></a>
                        <div style="font-size:11px;color:#aaa;"><?= sanitize($item['brand_name']) ?></div>
                      </td>
                      <td style="text-align:center;">
                        <div class="account_qty_control">
                          <button type="button" class="account_qty_btn" data-qty-minus>−</button>
                          <input type="number" class="account_qty_num" data-qty-input value="<?= (int)$item['quantity'] ?>" min="1" max="99" readonly>
                          <button type="button" class="account_qty_btn" data-qty-plus>+</button>
                        </div>
                      </td>
                      <td style="text-align:right;font-family:monospace;font-size:13px;color:#888;"><?= formatPriceInCurrency($item['price']) ?></td>
                      <td style="text-align:right;font-family:monospace;font-weight:700;color:#e74c3c;" data-row-subtotal><?= formatPriceInCurrency($item['price'] * $item['quantity']) ?></td>
                      <td>
                        <button type="button" class="account_btn account_btn_danger account_btn_sm" data-cart-remove="<?= (int)$item['part_id'] ?>" style="padding:4px 10px;">✕</button>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Order summary -->
          <div>
            <div class="account_order_summary">
              <div style="font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#333;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #f0f0f0;">Итого</div>
              <?php foreach ($cartItems as $item): ?>
              <div class="account_summary_row">
                <span style="font-size:12px;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= sanitize(truncate($item['name'],28)) ?></span>
                <span style="font-family:monospace;font-size:12px;"><?= $item['quantity'] ?> × <?= formatPriceInCurrency($item['price']) ?></span>
              </div>
              <?php endforeach; ?>
              <div class="account_summary_total">
                <span style="font-size:13px;color:#888;">Итого</span>
                <span id="cart-total" style="font-size:1.5rem;font-weight:900;color:#e74c3c;"><?= formatPriceInCurrency($cartTotal) ?></span>
              </div>

              <form method="post" action="" style="margin-top:20px;">
                <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">
                <input type="hidden" name="checkout" value="1">
                <div class="account_form_group">
                  <label class="account_form_label">Адрес доставки *</label>
                  <textarea name="address" class="account_form_textarea" rows="3" placeholder="г. Москва, ул. Пример, д. 1, кв. 5" required></textarea>
                </div>
                <div class="account_form_group">
                  <label class="account_form_label">Примечания</label>
                  <textarea name="notes" class="account_form_textarea" rows="2" placeholder="Удобное время доставки..."></textarea>
                </div>
                <button type="submit" class="button account_btn_block" style="width:100%;padding:14px;font-size:14px;">
                  Оформить заказ
                </button>
              </form>
            </div>
          </div>

        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<script>
(function(){
  var AZ_BASE = (function(){
    var s = document.querySelector('script[src*="main.js"]');
    return s ? s.src.replace(/\/assets\/js\/main\.js.*/, '') : '';
  }());

  document.querySelectorAll('[data-qty-minus],[data-qty-plus]').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var row = this.closest('tr[data-cart-row]');
      if (!row) return;
      var partId = row.dataset.cartRow;
      var inp = row.querySelector('[data-qty-input]');
      var cur = parseInt(inp.value) || 1;
      var next = this.hasAttribute('data-qty-minus') ? Math.max(1, cur-1) : Math.min(99, cur+1);
      if (next === cur) return;
      inp.value = next;
      fetch(AZ_BASE + '/api/cart.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({action:'update', part_id: parseInt(partId), quantity: next})
      }).then(function(r){ return r.json(); }).then(function(data){
        if (data.success) {
          var sub = row.querySelector('[data-row-subtotal]');
          if (sub && data.row_subtotal_fmt) sub.textContent = data.row_subtotal_fmt;
          var tot = document.getElementById('cart-total');
          if (tot && data.total_fmt) tot.textContent = data.total_fmt;
        }
      });
    });
  });
}());
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
