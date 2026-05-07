<?php
require_once dirname(__DIR__) . '/config/config.php';
requireRole(['admin', 'superadmin']);

$db = getDB();

$totalUsers  = (int)$db->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
$totalOrders = (int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pendingOrds = (int)$db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$revenue     = (float)$db->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status NOT IN ('cancelled')")->fetchColumn();

$recentOrders = $db->query(
    "SELECT o.*, u.username, u.email FROM orders o
     JOIN users u ON u.id = o.user_id
     ORDER BY o.created_at DESC LIMIT 10"
)->fetchAll();

$pageTitle = 'Панель администратора';
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
    <div class="dash-heading">ПАНЕЛЬ АДМИНИСТРАТОРА</div>

    <div class="az-stat-grid">
      <div class="az-stat-card">
        <div class="az-stat-label">Пользователей</div>
        <div class="az-stat-value az-stat-accent"><?= $totalUsers ?></div>
        <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px;">Активных аккаунтов</div>
      </div>
      <div class="az-stat-card">
        <div class="az-stat-label">Всего заказов</div>
        <div class="az-stat-value az-stat-accent"><?= $totalOrders ?></div>
        <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px;">За всё время</div>
      </div>
      <div class="az-stat-card">
        <div class="az-stat-label">Новых заказов</div>
        <div class="az-stat-value" style="color:var(--warning);"><?= $pendingOrds ?></div>
        <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px;">Требуют обработки</div>
      </div>
      <div class="az-stat-card">
        <div class="az-stat-label">Выручка</div>
        <div class="az-stat-value az-stat-accent" style="font-size:1.3rem;"><?= formatPrice($revenue) ?></div>
        <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px;">Без отменённых</div>
      </div>
    </div>

    <div class="az-card">
      <div class="az-card-header">
        <h3>ПОСЛЕДНИЕ ЗАКАЗЫ</h3>
        <a href="<?= APP_URL ?>/admin/orders.php" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">Все заказы</a>
      </div>
      <?php if (empty($recentOrders)): ?>
      <div class="az-card-body">
        <div class="az-no-data">
          <div class="az-no-data-icon">📦</div>
          <p>Заказов пока нет.</p>
        </div>
      </div>
      <?php else: ?>
      <div class="az-table-wrap">
        <table class="az-table">
          <thead>
            <tr><th>#</th><th>Покупатель</th><th>Дата</th><th>Сумма</th><th>Статус</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($recentOrders as $order): ?>
            <tr>
              <td><span style="font-family:monospace;color:var(--accent);">#<?= $order['id'] ?></span></td>
              <td>
                <div style="font-size:0.875rem;font-weight:500;"><?= sanitize($order['username']) ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted);"><?= sanitize($order['email']) ?></div>
              </td>
              <td style="color:var(--text-muted);font-size:0.8rem;"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
              <td style="font-family:monospace;color:var(--accent);"><?= formatPrice($order['total_amount']) ?></td>
              <td><span class="az-status az-status-<?= sanitize($order['status']) ?>"><?= getOrderStatusLabel($order['status']) ?></span></td>
              <td><a href="<?= APP_URL ?>/admin/orders.php?id=<?= $order['id'] ?>" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">Просмотр</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
      <div class="az-card-footer">
        <a href="<?= APP_URL ?>/admin/orders.php" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">Все заказы →</a>
        <a href="<?= APP_URL ?>/admin/users.php" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">Пользователи →</a>
      </div>
    </div>

  </div>
</div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
