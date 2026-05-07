<?php
require_once dirname(__DIR__) . '/config/config.php';
requireRole('buyer');
$user = getCurrentUser();
$db   = getDB();

$orderCount = (int)$db->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?")->execute([$user['id']]) ? 0 : 0;
$oc = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?"); $oc->execute([$user['id']]); $orderCount = (int)$oc->fetchColumn();
$cartCount2 = getCartCount();
$totalSpend = (float)$db->prepare("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE user_id=? AND status='delivered'")->execute([$user['id']]) ? 0 : 0;
$ts = $db->prepare("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE user_id=? AND status='delivered'"); $ts->execute([$user['id']]); $totalSpend = (float)$ts->fetchColumn();

$recentOrders = $db->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC LIMIT 5");
$recentOrders->execute([$user['id']]);
$orders = $recentOrders->fetchAll();

$pageTitle = 'Личный кабинет';
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
    <div class="dash-heading">ДОБРО ПОЖАЛОВАТЬ, <?= mb_strtoupper(sanitize($user['username'])) ?></div>

    <div class="az-stat-grid">
      <div class="az-stat-card">
        <div class="az-stat-label">Заказов</div>
        <div class="az-stat-value az-stat-accent"><?= $orderCount ?></div>
      </div>
      <div class="az-stat-card">
        <div class="az-stat-label">В корзине</div>
        <div class="az-stat-value az-stat-accent"><?= $cartCount2 ?></div>
      </div>
      <div class="az-stat-card">
        <div class="az-stat-label">Потрачено</div>
        <div class="az-stat-value" style="font-size:1.2rem;"><?= formatPrice($totalSpend) ?></div>
      </div>
    </div>

    <div class="az-card">
      <div class="az-card-header"><h3>ПОСЛЕДНИЕ ЗАКАЗЫ</h3><a href="<?= APP_URL ?>/buyer/orders.php" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">Все заказы</a></div>
      <?php if (empty($orders)): ?>
      <div class="az-card-body"><p style="color:var(--text-muted);text-align:center;">Заказов пока нет. <a href="<?= APP_URL ?>/catalog/index.php">Перейти в каталог →</a></p></div>
      <?php else: ?>
      <div class="az-table-wrap">
        <table class="az-table">
          <thead><tr><th>#</th><th>Дата</th><th>Сумма</th><th>Статус</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
              <td><span class="mono" style="color:var(--accent);">#<?= $o['id'] ?></span></td>
              <td style="font-size:12px;color:var(--text-muted);"><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></td>
              <td style="font-family:monospace;color:var(--accent);"><?= formatPrice($o['total_amount']) ?></td>
              <td><span class="az-status az-status-<?= $o['status'] ?>"><?= getOrderStatusLabel($o['status']) ?></span></td>
              <td><a href="<?= APP_URL ?>/buyer/orders.php?id=<?= $o['id'] ?>" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">Детали</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
