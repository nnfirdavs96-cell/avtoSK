<?php
require_once dirname(__DIR__) . '/config/config.php';
requireRole(['superadmin']);

$db   = getDB();
$csrf = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        flashMessage('danger','CSRF error.'); redirect(APP_URL.'/superadmin/warehouse.php');
    }
    $pAction = $_POST['action'] ?? '';

    if ($pAction === 'clear_cache') {
        $db->exec("DELETE FROM warehouse_cache");
        flashMessage('success','Кэш склада очищен.');
        redirect(APP_URL.'/superadmin/warehouse.php');
    }

    if ($pAction === 'clear_part') {
        $partNum = trim($_POST['part_number'] ?? '');
        if ($partNum) {
            $db->prepare("DELETE FROM warehouse_cache WHERE part_number=?")->execute([$partNum]);
            flashMessage('success','Кэш для '.$partNum.' удалён.');
        }
        redirect(APP_URL.'/superadmin/warehouse.php');
    }

    if ($pAction === 'test_api') {
        $testPart = trim($_POST['test_part'] ?? 'TEST123');
        $result = getWarehouseStock($testPart);
        if ($result !== null) {
            flashMessage('success','API отвечает. Данные получены для: '.$testPart);
        } else {
            flashMessage('danger','API не отвечает или вернул ошибку. Проверьте настройки.');
        }
        redirect(APP_URL.'/superadmin/warehouse.php');
    }
}

$apiUrl    = getSetting('warehouse_api_url','');
$cacheHours= (int)getSetting('warehouse_cache_hours','6');

$cacheCount = (int)$db->query("SELECT COUNT(*) FROM warehouse_cache")->fetchColumn();
$cacheStmt  = $db->query(
    "SELECT wc.*, p.name AS part_name
     FROM warehouse_cache wc LEFT JOIN parts p ON p.part_number=wc.part_number
     ORDER BY wc.last_checked DESC LIMIT 30"
);
$cacheItems = $cacheStmt->fetchAll();

$expiredCount = (int)$db->query(
    "SELECT COUNT(*) FROM warehouse_cache WHERE last_checked < DATE_SUB(NOW(), INTERVAL ".$cacheHours." HOUR)"
)->fetchColumn();

$pageTitle = 'Склад Москвы';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/nav.php';
?>

<div class="az-admin-page">
<div class="az-admin-navbar">
  <a href="<?= APP_URL ?>/index.php" class="az-admin-logo">АВТО<span>ЗАПЧАСТЬ</span></a>
  <ul class="az-admin-nav-links">
    <li><a href="<?= APP_URL ?>/auth/logout.php">Выйти</a></li>
  </ul>
</div>
<div class="dash-layout">
  <div class="dash-sidebar"><?php renderNav(); ?></div>
  <div class="dash-main">
    <div class="dash-heading">СКЛАД МОСКВЫ</div>

    <!-- Статус -->
    <div class="az-stat-grid" style="margin-bottom:20px;">
      <div class="az-stat-card">
        <div class="az-stat-label">Записей в кэше</div>
        <div class="az-stat-value az-stat-accent"><?= $cacheCount ?></div>
      </div>
      <div class="az-stat-card">
        <div class="az-stat-label">Устаревших</div>
        <div class="az-stat-value" style="color:<?= $expiredCount>0?'var(--warning)':'var(--success)' ?>;"><?= $expiredCount ?></div>
        <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">TTL: <?= $cacheHours ?> ч.</div>
      </div>
      <div class="az-stat-card">
        <div class="az-stat-label">API URL</div>
        <div style="font-size:11px;color:var(--text-secondary);margin-top:4px;word-break:break-all;">
          <?= $apiUrl ? sanitize($apiUrl) : '<span style="color:var(--danger);">Не настроен</span>' ?>
        </div>
      </div>
    </div>

    <!-- Настройки API -->
    <div class="az-card" style="margin-bottom:20px;">
      <div class="az-card-header">
        <h3>НАСТРОЙКИ API</h3>
        <a href="<?= APP_URL ?>/superadmin/settings.php" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">Редактировать →</a>
      </div>
      <div class="az-card-body">
        <div class="az-grid-2">
          <div>
            <div style="font-size:11px;color:var(--text-muted);margin-bottom:4px;">API URL</div>
            <div style="font-family:monospace;font-size:12px;color:var(--text-secondary);word-break:break-all;">
              <?= $apiUrl ? sanitize($apiUrl) : '<span style="color:var(--danger);">Не задан</span>' ?>
            </div>
          </div>
          <div>
            <div style="font-size:11px;color:var(--text-muted);margin-bottom:4px;">TTL кэша</div>
            <div style="font-family:monospace;"><?= $cacheHours ?> часов</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Тест API -->
    <div class="az-card" style="margin-bottom:20px;">
      <div class="az-card-header"><h3>ТЕСТ API</h3></div>
      <div class="az-card-body">
        <form method="post" action="" style="display:flex;gap:10px;align-items:flex-end;">
          <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">
          <input type="hidden" name="action" value="test_api">
          <div class="az-form-group" style="margin:0;">
            <label class="az-form-label">Артикул для теста</label>
            <input type="text" name="test_part" class="az-form-input" style="max-width:200px;" placeholder="BKR6EK" value="">
          </div>
          <button type="submit" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm"
                  <?= !$apiUrl?'disabled title="Сначала настройте API URL"':'' ?>>Проверить</button>
        </form>
      </div>
    </div>

    <!-- Управление кэшем -->
    <div class="az-card" style="margin-bottom:20px;">
      <div class="az-card-header">
        <h3>КЭШ СКЛАДА (последние 30)</h3>
        <form method="post" action="" style="display:inline;">
          <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">
          <input type="hidden" name="action" value="clear_cache">
          <button type="submit" class="az-admin-btn az-admin-btn-danger az-admin-btn-sm"
                  onclick="return confirm('Очистить весь кэш склада?')">Очистить всё</button>
        </form>
      </div>
      <?php if (empty($cacheItems)): ?>
      <div class="az-card-body"><div class="az-no-data"><p>Кэш пуст. Данные будут загружены при первом обращении к товару.</p></div></div>
      <?php else: ?>
      <div class="az-table-wrap">
        <table class="az-table">
          <thead><tr><th>Артикул</th><th>Наименование</th><th>Склад (шт.)</th><th>Цена склада</th><th>Срок (ETA)</th><th>Проверено</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($cacheItems as $item):
              $isExpired = strtotime($item['last_checked']) < (time() - $cacheHours*3600);
            ?>
            <tr style="<?= $isExpired?'opacity:0.65;':'' ?>">
              <td><span style="font-family:monospace;color:var(--accent);font-size:12px;"><?= sanitize($item['part_number']) ?></span></td>
              <td style="font-size:12px;color:var(--text-muted);"><?= sanitize(truncate($item['part_name'] ?? '—', 40)) ?></td>
              <td style="font-family:monospace;text-align:center;color:<?= $item['warehouse_stock']>0?'var(--success)':'var(--danger)' ?>;">
                <?= (int)$item['warehouse_stock'] ?>
              </td>
              <td style="font-family:monospace;font-size:12px;">
                <?= $item['warehouse_price'] ? formatPrice((float)$item['warehouse_price']) : '—' ?>
              </td>
              <td style="font-size:12px;color:var(--text-muted);"><?= sanitize($item['warehouse_eta'] ?? '—') ?></td>
              <td style="font-size:11px;color:<?= $isExpired?'var(--warning)':'var(--text-muted)' ?>;">
                <?= date('d.m H:i', strtotime($item['last_checked'])) ?>
                <?= $isExpired?' ⚠️':'' ?>
              </td>
              <td>
                <form method="post" action="" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">
                  <input type="hidden" name="action" value="clear_part">
                  <input type="hidden" name="part_number" value="<?= sanitize($item['part_number']) ?>">
                  <button type="submit" class="az-admin-btn az-admin-btn-sm az-admin-btn-outline" title="Сбросить кэш">×</button>
                </form>
              </td>
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
