<?php
require_once dirname(__DIR__) . '/config/config.php';
requireRole(['manager', 'superadmin']);

$db = getDB();
$totalParts   = (int)$db->query("SELECT COUNT(*) FROM parts WHERE is_active=1")->fetchColumn();
$lowStock     = (int)$db->query("SELECT COUNT(*) FROM parts WHERE is_active=1 AND stock<=5 AND stock>0")->fetchColumn();
$outOfStock   = (int)$db->query("SELECT COUNT(*) FROM parts WHERE is_active=1 AND stock=0")->fetchColumn();
$totalBrands  = (int)$db->query("SELECT COUNT(*) FROM brands WHERE is_active=1")->fetchColumn();
$totalCats    = (int)$db->query("SELECT COUNT(*) FROM categories WHERE is_active=1")->fetchColumn();

$recentParts = $db->query(
    "SELECT p.*, b.name AS brand_name, c.name AS cat_name
     FROM parts p LEFT JOIN brands b ON b.id=p.brand_id LEFT JOIN categories c ON c.id=p.category_id
     WHERE p.is_active=1 ORDER BY p.updated_at DESC LIMIT 10"
)->fetchAll();

$pageTitle = 'Панель менеджера';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/nav.php';
?>

<div class="az-admin-page">
<div class="az-admin-navbar">
  <a href="<?= APP_URL ?>/index.php" class="az-admin-logo">АВТО<span>ЗАПЧАСТЬ</span></a>
  <ul class="az-admin-nav-links">
    <li><a href="<?= APP_URL ?>/manager/parts.php?action=new" class="az-admin-btn az-admin-btn-primary az-admin-btn-sm">+ Добавить товар</a></li>
    <li><a href="<?= APP_URL ?>/auth/logout.php">Выйти</a></li>
  </ul>
</div>
<div class="dash-layout">
  <div class="dash-sidebar"><?php renderNav(); ?></div>
  <div class="dash-main">
    <div class="dash-heading">ПАНЕЛЬ МЕНЕДЖЕРА</div>

    <div class="az-stat-grid">
      <div class="az-stat-card">
        <div class="az-stat-label">Всего товаров</div>
        <div class="az-stat-value az-stat-accent"><?= $totalParts ?></div>
      </div>
      <div class="az-stat-card">
        <div class="az-stat-label">Заканчиваются</div>
        <div class="az-stat-value" style="color:var(--warning);"><?= $lowStock ?></div>
        <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">≤5 штук</div>
      </div>
      <div class="az-stat-card">
        <div class="az-stat-label">Нет в наличии</div>
        <div class="az-stat-value" style="color:var(--danger);"><?= $outOfStock ?></div>
      </div>
      <div class="az-stat-card">
        <div class="az-stat-label">Бренды</div>
        <div class="az-stat-value az-stat-accent"><?= $totalBrands ?></div>
      </div>
      <div class="az-stat-card">
        <div class="az-stat-label">Категории</div>
        <div class="az-stat-value az-stat-accent"><?= $totalCats ?></div>
      </div>
    </div>

    <div class="az-card">
      <div class="az-card-header">
        <h3>ПОСЛЕДНИЕ ОБНОВЛЁННЫЕ ТОВАРЫ</h3>
        <a href="<?= APP_URL ?>/manager/parts.php" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">Все товары</a>
      </div>
      <div class="az-table-wrap">
        <table class="az-table">
          <thead><tr><th>Артикул</th><th>Наименование</th><th>Бренд</th><th>Категория</th><th style="text-align:right;">Цена</th><th style="text-align:center;">Остаток</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($recentParts as $p):
              $stock = getStockStatus((int)$p['stock']);
            ?>
            <tr>
              <td><span style="font-family:monospace;color:var(--accent);font-size:12px;"><?= sanitize($p['part_number']) ?></span></td>
              <td style="font-size:13px;"><?= sanitize(truncate($p['name'],45)) ?></td>
              <td style="color:var(--text-muted);font-size:12px;"><?= sanitize($p['brand_name']) ?></td>
              <td style="color:var(--text-muted);font-size:12px;"><?= sanitize($p['cat_name']) ?></td>
              <td style="text-align:right;font-family:monospace;color:var(--accent);"><?= formatPrice($p['price']) ?></td>
              <td style="text-align:center;">
                <span style="font-family:monospace;font-size:12px;color:<?= $p['stock']<=0?'var(--danger)':($p['stock']<=5?'var(--warning)':'var(--success)') ?>;">
                  <?= $p['stock'] ?>
                </span>
              </td>
              <td><a href="<?= APP_URL ?>/manager/parts.php?action=edit&id=<?= $p['id'] ?>" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">Ред.</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
