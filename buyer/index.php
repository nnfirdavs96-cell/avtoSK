<?php
require_once dirname(__DIR__) . '/config/config.php';
requireRole('buyer');
$user = getCurrentUser();
$db   = getDB();

$oc = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?"); $oc->execute([$user['id']]); $orderCount = (int)$oc->fetchColumn();
$cc = $db->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id=?"); $cc->execute([$user['id']]); $cartCount2 = (int)$cc->fetchColumn();
$ts = $db->prepare("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE user_id=? AND status='delivered'"); $ts->execute([$user['id']]); $totalSpend = (float)$ts->fetchColumn();

$recentOrders = $db->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC LIMIT 5");
$recentOrders->execute([$user['id']]);
$orders = $recentOrders->fetchAll();

$pageTitle = 'Личный кабинет';
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
          <li class="active">Личный кабинет</li>
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

        <div class="dash-heading" style="font-size:18px;font-weight:700;color:#333;margin-bottom:24px;padding-bottom:12px;border-bottom:2px solid #e74c3c;">
          Добро пожаловать, <?= sanitize($user['username']) ?>
        </div>

        <!-- Stats -->
        <div class="account_stat_grid">
          <div class="account_stat_card">
            <div class="account_stat_label">Заказов</div>
            <div class="account_stat_value"><?= $orderCount ?></div>
          </div>
          <div class="account_stat_card">
            <div class="account_stat_label">В корзине</div>
            <div class="account_stat_value"><?= $cartCount2 ?></div>
          </div>
          <div class="account_stat_card">
            <div class="account_stat_label">Потрачено</div>
            <div class="account_stat_value" style="font-size:1.3rem;"><?= formatPrice($totalSpend) ?></div>
          </div>
        </div>

        <!-- Recent orders -->
        <div class="account_card">
          <div class="account_card_header">
            <h3>Последние заказы</h3>
            <a href="<?= APP_URL ?>/buyer/orders.php" class="account_btn account_btn_sm">Все заказы →</a>
          </div>
          <?php if (empty($orders)): ?>
          <div class="account_no_data">
            <div class="account_no_data_icon">📦</div>
            <p>Заказов пока нет.</p>
            <a href="<?= APP_URL ?>/catalog/index.php" class="account_btn account_btn_primary" style="margin-top:14px;display:inline-block;">В каталог</a>
          </div>
          <?php else: ?>
          <div style="overflow-x:auto;">
            <table class="account_table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Дата</th>
                  <th>Сумма</th>
                  <th>Статус</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($orders as $o): ?>
                <tr>
                  <td style="font-family:monospace;color:#e74c3c;font-weight:700;">#<?= $o['id'] ?></td>
                  <td style="color:#888;font-size:12px;"><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></td>
                  <td style="font-family:monospace;font-weight:600;"><?= formatPrice($o['total_amount']) ?></td>
                  <td><span class="badge_status badge_<?= $o['status'] ?>"><?= getOrderStatusLabel($o['status']) ?></span></td>
                  <td><a href="<?= APP_URL ?>/buyer/orders.php?id=<?= $o['id'] ?>" class="account_btn account_btn_sm">Детали</a></td>
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
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
