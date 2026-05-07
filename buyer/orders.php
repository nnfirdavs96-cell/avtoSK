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
    <div class="dash-heading">МОИ ЗАКАЗЫ</div>

    <?php if ($orderDetail): ?>
    <div class="az-card mb-24">
      <div class="az-card-header">
        <div>
          <h3>ЗАКАЗ #<?= $orderDetail['id'] ?></h3>
          <div style="font-size:11px;color:var(--text-muted);"><?= date('d.m.Y H:i', strtotime($orderDetail['created_at'])) ?></div>
        </div>
        <span class="az-status az-status-<?= $orderDetail['status'] ?>"><?= getOrderStatusLabel($orderDetail['status']) ?></span>
      </div>
      <div class="az-card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
          <div>
            <div style="font-size:11px;text-transform:uppercase;color:var(--text-muted);margin-bottom:4px;">Адрес доставки</div>
            <p style="font-size:13px;color:var(--text-secondary);"><?= nl2br(sanitize($orderDetail['shipping_address'])) ?></p>
          </div>
          <?php if ($orderDetail['notes']): ?>
          <div>
            <div style="font-size:11px;text-transform:uppercase;color:var(--text-muted);margin-bottom:4px;">Примечания</div>
            <p style="font-size:13px;color:var(--text-secondary);"><?= nl2br(sanitize($orderDetail['notes'])) ?></p>
          </div>
          <?php endif; ?>
        </div>
        <div class="az-table-wrap">
          <table class="az-table">
            <thead><tr><th>Номер</th><th>Наименование</th><th>Бренд</th><th style="text-align:center;">Кол-во</th><th style="text-align:right;">Цена</th><th style="text-align:right;">Сумма</th></tr></thead>
            <tbody>
              <?php foreach ($orderItems as $item): ?>
              <tr>
                <td><span class="mono" style="color:var(--accent);"><?= sanitize($item['part_number']) ?></span></td>
                <td><a href="<?= APP_URL ?>/catalog/part.php?id=<?= $item['part_id'] ?>" style="color:var(--text-primary);text-decoration:none;font-size:13px;"><?= sanitize($item['part_name']) ?></a></td>
                <td style="color:var(--text-muted);font-size:12px;"><?= sanitize($item['brand_name']) ?></td>
                <td style="text-align:center;font-family:monospace;"><?= $item['quantity'] ?></td>
                <td style="text-align:right;font-family:monospace;font-size:13px;color:var(--text-secondary);"><?= formatPrice($item['unit_price']) ?></td>
                <td style="text-align:right;font-family:monospace;color:var(--accent);"><?= formatPrice($item['unit_price']*$item['quantity']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div style="text-align:right;margin-top:12px;padding-top:12px;border-top:1px solid var(--border);">
          <span style="font-size:11px;color:var(--text-muted);">ИТОГО: </span>
          <span style="font-size:1.5rem;font-weight:900;color:var(--accent);"><?= formatPrice($orderDetail['total_amount']) ?></span>
        </div>
      </div>
      <div class="az-card-footer">
        <a href="<?= APP_URL ?>/buyer/orders.php" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">← Все заказы</a>
      </div>
    </div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
    <div class="az-no-data">
      <div class="az-no-data-icon">📦</div>
      <p>Заказов пока нет.</p>
      <a href="<?= APP_URL ?>/catalog/index.php" class="az-admin-btn az-admin-btn-primary" style="margin-top:14px;">В каталог</a>
    </div>
    <?php else: ?>
    <div class="az-table-wrap">
      <table class="az-table">
        <thead><tr><th>#</th><th>Дата</th><th>Сумма</th><th>Статус</th><th>Обновлён</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
          <tr>
            <td><span class="mono" style="color:var(--accent);">#<?= $o['id'] ?></span></td>
            <td style="color:var(--text-muted);font-size:12px;"><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></td>
            <td style="font-family:monospace;color:var(--accent);"><?= formatPrice($o['total_amount']) ?></td>
            <td><span class="az-status az-status-<?= $o['status'] ?>"><?= getOrderStatusLabel($o['status']) ?></span></td>
            <td style="color:var(--text-muted);font-size:12px;"><?= date('d.m.Y H:i', strtotime($o['updated_at'])) ?></td>
            <td><a href="?id=<?= $o['id'] ?>" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">Детали</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
