<?php
require_once dirname(__DIR__) . '/config/config.php';
requireRole(['admin', 'superadmin']);

$db   = getDB();
$csrf = generateCsrfToken();

// AJAX статус-апдейт
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action  = $_POST['action'] ?? '';
    $orderId = (int)($_POST['order_id'] ?? 0);
    $status  = $_POST['status'] ?? '';
    $allowed = ['pending','processing','shipped','delivered','cancelled'];
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success'=>false,'error'=>'CSRF']); exit;
    }
    if ($action === 'update_status' && in_array($status, $allowed, true)) {
        $db->prepare("UPDATE orders SET status=?, updated_at=NOW() WHERE id=?")->execute([$status, $orderId]);
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false,'error'=>'Bad request']);
    }
    exit;
}

// Просмотр конкретного заказа
$viewId = (int)($_GET['id'] ?? 0);
$orderDetail = null;
$orderItems  = [];
if ($viewId) {
    $s = $db->prepare("SELECT o.*, u.username, u.email, u.phone FROM orders o JOIN users u ON u.id=o.user_id WHERE o.id=?");
    $s->execute([$viewId]);
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

// Фильтры списка
$filterStatus = $_GET['status'] ?? '';
$filterUser   = trim($_GET['user'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$where   = [];
$params  = [];
if ($filterStatus) { $where[] = 'o.status=?';                      $params[] = $filterStatus; }
if ($filterUser)   { $where[] = '(u.username LIKE ? OR u.email LIKE ?)'; $params[] = "%$filterUser%"; $params[] = "%$filterUser%"; }
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$cntStmt = $db->prepare("SELECT COUNT(*) FROM orders o JOIN users u ON u.id=o.user_id $whereSQL");
$cntStmt->execute($params);
$total  = (int)$cntStmt->fetchColumn();
$pages  = max(1, (int)ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$ordStmt = $db->prepare(
    "SELECT o.*, u.username, u.email FROM orders o JOIN users u ON u.id=o.user_id
     $whereSQL ORDER BY o.created_at DESC LIMIT $perPage OFFSET $offset"
);
$ordStmt->execute($params);
$orders = $ordStmt->fetchAll();

$statuses  = ['pending','processing','shipped','delivered','cancelled'];
$pageTitle = 'Управление заказами';
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
    <div class="dash-heading">ЗАКАЗЫ</div>

    <?php if ($orderDetail): ?>
    <!-- Детали заказа -->
    <div class="az-card mb-24">
      <div class="az-card-header">
        <div>
          <h3>ЗАКАЗ #<?= $orderDetail['id'] ?></h3>
          <div style="font-size:11px;color:var(--text-muted);">
            <?= sanitize($orderDetail['username']) ?> · <?= sanitize($orderDetail['email']) ?>
            <?php if ($orderDetail['phone']): ?> · <?= sanitize($orderDetail['phone']) ?><?php endif; ?>
          </div>
        </div>
        <select class="az-form-select" style="width:auto;font-size:12px;padding:6px 10px;"
                data-status-update="<?= $orderDetail['id'] ?>" data-csrf="<?= sanitize($csrf) ?>">
          <?php foreach ($statuses as $st): ?>
          <option value="<?= $st ?>" <?= $orderDetail['status']===$st?'selected':'' ?>><?= getOrderStatusLabel($st) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="az-card-body">
        <div class="az-grid-2" style="margin-bottom:16px;">
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
                <td><span style="font-family:monospace;color:var(--accent);font-size:12px;"><?= sanitize($item['part_number']) ?></span></td>
                <td style="font-size:13px;"><?= sanitize($item['part_name']) ?></td>
                <td style="color:var(--text-muted);font-size:12px;"><?= sanitize($item['brand_name']) ?></td>
                <td style="text-align:center;font-family:monospace;"><?= $item['quantity'] ?></td>
                <td style="text-align:right;font-family:monospace;font-size:13px;color:var(--text-secondary);"><?= formatPrice($item['unit_price']) ?></td>
                <td style="text-align:right;font-family:monospace;color:var(--accent);"><?= formatPrice($item['unit_price']*$item['quantity']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div style="text-align:right;margin-top:14px;padding-top:14px;border-top:1px solid var(--border);">
          <span style="font-size:11px;color:var(--text-muted);">ИТОГО: </span>
          <span style="font-size:1.6rem;font-weight:900;color:var(--accent);"><?= formatPrice($orderDetail['total_amount']) ?></span>
        </div>
      </div>
      <div class="az-card-footer">
        <a href="<?= APP_URL ?>/admin/orders.php" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">← Все заказы</a>
      </div>
    </div>
    <?php endif; ?>

    <!-- Фильтры -->
    <form method="get" action="" class="az-card mb-16">
      <div class="az-card-body" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div class="az-form-group" style="margin:0;flex:1;min-width:140px;">
          <label class="az-form-label">Статус</label>
          <select name="status" class="az-form-select" style="font-size:12px;padding:7px;">
            <option value="">Все статусы</option>
            <?php foreach ($statuses as $st): ?>
            <option value="<?= $st ?>" <?= $filterStatus===$st?'selected':'' ?>><?= getOrderStatusLabel($st) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="az-form-group" style="margin:0;flex:2;min-width:180px;">
          <label class="az-form-label">Покупатель</label>
          <input type="text" name="user" class="az-form-input" style="font-size:12px;padding:7px;" placeholder="Имя или email" value="<?= sanitize($filterUser) ?>">
        </div>
        <button type="submit" class="az-admin-btn az-admin-btn-primary az-admin-btn-sm">Применить</button>
        <a href="<?= APP_URL ?>/admin/orders.php" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">Сбросить</a>
      </div>
    </form>

    <!-- Таблица заказов -->
    <div class="az-card">
      <div class="az-card-header"><h3>ЗАКАЗЫ (<?= $total ?>)</h3></div>
      <?php if (empty($orders)): ?>
      <div class="az-card-body"><div class="az-no-data"><div class="az-no-data-icon">📦</div><p>Заказов нет.</p></div></div>
      <?php else: ?>
      <div class="az-table-wrap">
        <table class="az-table">
          <thead><tr><th>#</th><th>Покупатель</th><th>Дата</th><th>Сумма</th><th>Статус</th><th>Изменить статус</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
              <td><span style="font-family:monospace;color:var(--accent);">#<?= $o['id'] ?></span></td>
              <td>
                <div style="font-size:13px;font-weight:500;"><?= sanitize($o['username']) ?></div>
                <div style="font-size:11px;color:var(--text-muted);"><?= sanitize($o['email']) ?></div>
              </td>
              <td style="color:var(--text-muted);font-size:12px;"><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></td>
              <td style="font-family:monospace;color:var(--accent);"><?= formatPrice($o['total_amount']) ?></td>
              <td><span class="az-status az-status-<?= $o['status'] ?>"><?= getOrderStatusLabel($o['status']) ?></span></td>
              <td>
                <select class="az-form-select" style="width:auto;font-size:11px;padding:4px 8px;"
                        data-status-update="<?= $o['id'] ?>" data-csrf="<?= sanitize($csrf) ?>">
                  <?php foreach ($statuses as $st): ?>
                  <option value="<?= $st ?>" <?= $o['status']===$st?'selected':'' ?>><?= getOrderStatusLabel($st) ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td><a href="?id=<?= $o['id'] ?>" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">Детали</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if ($pages > 1): ?>
      <div class="az-card-footer">
        <div class="az-pagination">
          <?php for ($i=1; $i<=$pages; $i++): $qp = array_merge($_GET,['page'=>$i]); ?>
          <a href="?<?= http_build_query($qp) ?>" class="az-page-link <?= $i==$page?'active':'' ?>"><?= $i ?></a>
          <?php endfor; ?>
        </div>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>

  </div>
</div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
