<?php
require_once dirname(__DIR__) . '/config/config.php';
requireRole(['superadmin']);

$db = getDB();

$totalUsers   = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$activeUsers  = (int)$db->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn();
$totalParts   = (int)$db->query("SELECT COUNT(*) FROM parts WHERE is_active=1")->fetchColumn();
$totalOrders  = (int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pendingOrds  = (int)$db->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
$revenue      = (float)$db->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status NOT IN ('cancelled')")->fetchColumn();
$totalBrands  = (int)$db->query("SELECT COUNT(*) FROM brands WHERE is_active=1")->fetchColumn();
$totalCats    = (int)$db->query("SELECT COUNT(*) FROM categories WHERE is_active=1")->fetchColumn();
$lowStock     = (int)$db->query("SELECT COUNT(*) FROM parts WHERE is_active=1 AND stock<=5 AND stock>0")->fetchColumn();
$outOfStock   = (int)$db->query("SELECT COUNT(*) FROM parts WHERE is_active=1 AND stock=0")->fetchColumn();

$roleStats = $db->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role")->fetchAll();
$roleCounts = array_column($roleStats, 'cnt', 'role');

$recentActivity = $db->query(
    "SELECT o.id, o.total_amount, o.status, o.created_at, u.username, u.email
     FROM orders o JOIN users u ON u.id=o.user_id
     ORDER BY o.created_at DESC LIMIT 8"
)->fetchAll();

$pageTitle = 'Суперадмин';
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
    <div class="dash-heading">СУПЕРАДМИН ПАНЕЛЬ</div>

    <!-- Основные показатели -->
    <div class="az-stat-grid">
      <div class="az-stat-card">
        <div class="az-stat-label">Всего пользователей</div>
        <div class="az-stat-value az-stat-accent"><?= $totalUsers ?></div>
        <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">Активных: <?= $activeUsers ?></div>
      </div>
      <div class="az-stat-card">
        <div class="az-stat-label">Всего заказов</div>
        <div class="az-stat-value az-stat-accent"><?= $totalOrders ?></div>
        <div style="font-size:11px;color:var(--warning);margin-top:4px;">Новых: <?= $pendingOrds ?></div>
      </div>
      <div class="az-stat-card">
        <div class="az-stat-label">Выручка</div>
        <div class="az-stat-value az-stat-accent" style="font-size:1.2rem;"><?= formatPrice($revenue) ?></div>
        <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">Без отменённых</div>
      </div>
      <div class="az-stat-card">
        <div class="az-stat-label">Товаров</div>
        <div class="az-stat-value az-stat-accent"><?= $totalParts ?></div>
        <div style="font-size:11px;color:var(--danger);margin-top:4px;">Нет в наличии: <?= $outOfStock ?></div>
      </div>
      <div class="az-stat-card">
        <div class="az-stat-label">Заканчиваются</div>
        <div class="az-stat-value" style="color:var(--warning);"><?= $lowStock ?></div>
        <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">≤5 штук</div>
      </div>
      <div class="az-stat-card">
        <div class="az-stat-label">Бренды / Категории</div>
        <div class="az-stat-value az-stat-accent"><?= $totalBrands ?> / <?= $totalCats ?></div>
      </div>
    </div>

    <!-- Пользователи по ролям -->
    <div class="az-card" style="margin-bottom:20px;">
      <div class="az-card-header">
        <h3>ПОЛЬЗОВАТЕЛИ ПО РОЛЯМ</h3>
        <a href="<?= APP_URL ?>/superadmin/users.php" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">Управление →</a>
      </div>
      <div class="az-card-body" style="display:flex;gap:16px;flex-wrap:wrap;">
        <?php foreach (['buyer','manager','admin','superadmin'] as $r): ?>
        <div style="background:var(--card-hover);border-radius:8px;padding:12px 20px;min-width:120px;text-align:center;">
          <div style="font-size:11px;text-transform:uppercase;color:var(--text-muted);margin-bottom:6px;"><?= $r ?></div>
          <div style="font-size:1.8rem;font-weight:900;color:var(--accent);"><?= $roleCounts[$r] ?? 0 ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Быстрые ссылки -->
    <div class="az-card" style="margin-bottom:20px;">
      <div class="az-card-header"><h3>БЫСТРЫЕ ДЕЙСТВИЯ</h3></div>
      <div class="az-card-body" style="display:flex;gap:10px;flex-wrap:wrap;">
        <a href="<?= APP_URL ?>/superadmin/settings.php" class="az-admin-btn az-admin-btn-primary az-admin-btn-sm">Настройки сайта</a>
        <a href="<?= APP_URL ?>/superadmin/currencies.php" class="az-admin-btn az-admin-btn-primary az-admin-btn-sm">Курсы валют</a>
        <a href="<?= APP_URL ?>/superadmin/warehouse.php" class="az-admin-btn az-admin-btn-primary az-admin-btn-sm">Склад Москвы</a>
        <a href="<?= APP_URL ?>/superadmin/users.php" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">Пользователи</a>
        <a href="<?= APP_URL ?>/admin/orders.php" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">Заказы</a>
        <a href="<?= APP_URL ?>/manager/parts.php" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">Товары</a>
      </div>
    </div>

    <!-- Последние заказы -->
    <div class="az-card">
      <div class="az-card-header">
        <h3>ПОСЛЕДНИЕ ЗАКАЗЫ</h3>
        <a href="<?= APP_URL ?>/admin/orders.php" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">Все заказы</a>
      </div>
      <?php if (empty($recentActivity)): ?>
      <div class="az-card-body"><div class="az-no-data"><p>Заказов нет.</p></div></div>
      <?php else: ?>
      <div class="az-table-wrap">
        <table class="az-table">
          <thead><tr><th>#</th><th>Покупатель</th><th>Дата</th><th>Сумма</th><th>Статус</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($recentActivity as $o): ?>
            <tr>
              <td><span style="font-family:monospace;color:var(--accent);">#<?= $o['id'] ?></span></td>
              <td>
                <div style="font-size:13px;font-weight:500;"><?= sanitize($o['username']) ?></div>
                <div style="font-size:11px;color:var(--text-muted);"><?= sanitize($o['email']) ?></div>
              </td>
              <td style="color:var(--text-muted);font-size:12px;"><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></td>
              <td style="font-family:monospace;color:var(--accent);"><?= formatPrice($o['total_amount']) ?></td>
              <td><span class="az-status az-status-<?= $o['status'] ?>"><?= getOrderStatusLabel($o['status']) ?></span></td>
              <td><a href="<?= APP_URL ?>/admin/orders.php?id=<?= $o['id'] ?>" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">Просмотр</a></td>
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
