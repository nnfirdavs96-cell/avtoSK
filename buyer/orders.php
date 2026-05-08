<?php
require_once dirname(__DIR__) . '/config/config.php';
requireRole('buyer');
$user = getCurrentUser();
$db   = getDB();

$viewId = (int)($_GET['id'] ?? 0);
$orderDetail = null;
$orderItems  = [];

if ($viewId) {
    $s = $db->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
    $s->execute([$viewId, $user['id']]);
    $orderDetail = $s->fetch();
    if ($orderDetail) {
        $is = $db->prepare(
            "SELECT oi.*, p.name AS part_name, p.part_number, b.name AS brand_name
             FROM order_items oi JOIN parts p ON p.id=oi.part_id LEFT JOIN brands b ON b.id=p.brand_id
             WHERE oi.order_id=?"
        );
        $is->execute([$viewId]);
        $orderItems = $is->fetchAll();
    }
}

$ordersStmt = $db->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC");
$ordersStmt->execute([$user['id']]);
$orders = $ordersStmt->fetchAll();
$pageTitle = 'Мои заказы';

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
          <li class="active">Мои заказы</li>
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
          Мои заказы
        </div>

        <!-- Order detail view -->
        <?php if ($orderDetail): ?>
        <div class="account_card" style="margin-bottom:24px;">
          <div class="account_card_header">
            <div>
              <h3>Заказ #<?= $orderDetail['id'] ?></h3>
              <div style="font-size:12px;color:#888;font-weight:400;margin-top:4px;"><?= date('d.m.Y H:i', strtotime($orderDetail['created_at'])) ?></div>
            </div>
            <span class="badge_status badge_<?= $orderDetail['status'] ?>"><?= getOrderStatusLabel($orderDetail['status']) ?></span>
          </div>
          <div class="account_card_body">
            <div class="row" style="margin-bottom:16px;">
              <div class="col-md-6">
                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:#888;margin-bottom:6px;">Адрес доставки</div>
                <p style="font-size:13px;color:#555;line-height:1.6;"><?= nl2br(sanitize($orderDetail['shipping_address'])) ?></p>
              </div>
              <?php if ($orderDetail['notes']): ?>
              <div class="col-md-6">
                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:#888;margin-bottom:6px;">Примечания</div>
                <p style="font-size:13px;color:#555;line-height:1.6;"><?= nl2br(sanitize($orderDetail['notes'])) ?></p>
              </div>
              <?php endif; ?>
            </div>
            <div style="overflow-x:auto;">
              <table class="account_table">
                <thead>
                  <tr>
                    <th>Номер</th>
                    <th>Наименование</th>
                    <th>Бренд</th>
                    <th style="text-align:center;">Кол-во</th>
                    <th style="text-align:right;">Цена</th>
                    <th style="text-align:right;">Сумма</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($orderItems as $item): ?>
                  <tr>
                    <td style="font-family:monospace;color:#e74c3c;font-size:12px;"><?= sanitize($item['part_number']) ?></td>
                    <td><a href="<?= APP_URL ?>/catalog/part.php?id=<?= $item['part_id'] ?>" style="color:#333;text-decoration:none;font-size:13px;"><?= sanitize($item['part_name']) ?></a></td>
                    <td style="color:#888;font-size:12px;"><?= sanitize($item['brand_name']) ?></td>
                    <td style="text-align:center;font-family:monospace;"><?= $item['quantity'] ?></td>
                    <td style="text-align:right;font-family:monospace;font-size:13px;color:#888;"><?= formatPriceInCurrency($item['unit_price']) ?></td>
                    <td style="text-align:right;font-family:monospace;font-weight:700;color:#e74c3c;"><?= formatPriceInCurrency($item['unit_price']*$item['quantity']) ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div style="text-align:right;margin-top:16px;padding-top:14px;border-top:2px solid #e74c3c;">
              <span style="font-size:13px;color:#888;margin-right:12px;">ИТОГО:</span>
              <span style="font-size:1.5rem;font-weight:900;color:#e74c3c;"><?= formatPriceInCurrency($orderDetail['total_amount']) ?></span>
            </div>
          </div>
          <div style="padding:14px 20px;border-top:1px solid #f0f0f0;">
            <a href="<?= APP_URL ?>/buyer/orders.php" class="account_btn account_btn_sm">← Все заказы</a>
          </div>
        </div>
        <?php endif; ?>

        <!-- Orders list -->
        <?php if (empty($orders)): ?>
        <div class="account_no_data">
          <div class="account_no_data_icon">📦</div>
          <p>Заказов пока нет.</p>
          <a href="<?= APP_URL ?>/catalog/index.php" class="account_btn account_btn_primary" style="margin-top:14px;display:inline-block;padding:12px 30px;">В каталог</a>
        </div>
        <?php else: ?>
        <div class="account_card">
          <div class="account_card_header">
            <h3>История заказов</h3>
          </div>
          <div style="overflow-x:auto;">
            <table class="account_table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Дата</th>
                  <th>Сумма</th>
                  <th>Статус</th>
                  <th>Обновлён</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($orders as $o): ?>
                <tr>
                  <td style="font-family:monospace;color:#e74c3c;font-weight:700;">#<?= $o['id'] ?></td>
                  <td style="color:#888;font-size:12px;"><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></td>
                  <td style="font-family:monospace;font-weight:600;"><?= formatPriceInCurrency($o['total_amount']) ?></td>
                  <td><span class="badge_status badge_<?= $o['status'] ?>"><?= getOrderStatusLabel($o['status']) ?></span></td>
                  <td style="color:#888;font-size:12px;"><?= date('d.m.Y', strtotime($o['updated_at'])) ?></td>
                  <td><a href="?id=<?= $o['id'] ?>" class="account_btn account_btn_sm">Детали</a></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
