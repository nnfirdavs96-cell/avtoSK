<?php
require_once dirname(__DIR__) . '/config/config.php';

$q      = trim($_GET['q'] ?? '');
$cat    = trim($_GET['cat'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage= 16;

$parts = [];
$total = 0;

if (mb_strlen($q) >= 2) {
    $db     = getDB();
    $param  = '%' . $q . '%';
    $where  = ["p.is_active=1", "(p.part_number LIKE ? OR p.name LIKE ?)"];
    $params = [$param, $param];
    if ($cat) {
        $cs = $db->prepare("SELECT id FROM categories WHERE slug=? AND is_active=1");
        $cs->execute([$cat]);
        $catRow = $cs->fetch();
        if ($catRow) {
            $sub = $db->prepare("SELECT id FROM categories WHERE parent_id=? AND is_active=1");
            $sub->execute([$catRow['id']]);
            $subIds = array_column($sub->fetchAll(),'id');
            $subIds[] = $catRow['id'];
            $in = implode(',', array_fill(0, count($subIds),'?'));
            $where[]  = "p.category_id IN ($in)";
            $params   = array_merge($params, $subIds);
        }
    }
    $whereSQL = 'WHERE ' . implode(' AND ', $where);
    $cntStmt  = $db->prepare("SELECT COUNT(*) FROM parts p $whereSQL");
    $cntStmt->execute($params);
    $total      = (int)$cntStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($total/$perPage));
    $page       = min($page, $totalPages);
    $offset     = ($page-1)*$perPage;
    $st = $db->prepare(
        "SELECT p.*, b.name AS brand_name, c.name AS category_name, c.slug AS category_slug
         FROM parts p
         LEFT JOIN brands b ON b.id=p.brand_id
         LEFT JOIN categories c ON c.id=p.category_id
         $whereSQL
         ORDER BY CASE WHEN p.part_number=? THEN 0 WHEN p.part_number LIKE ? THEN 1 ELSE 2 END, p.name
         LIMIT $perPage OFFSET $offset"
    );
    $allParams  = array_merge($params, [$q, $q.'%']);
    $st->execute($allParams);
    $parts = $st->fetchAll();
} else {
    $totalPages = 1;
}

$pageTitle = $q ? 'Поиск: ' . sanitize($q) : 'Поиск';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="container" style="padding:24px 0 40px;">
  <h1 style="font-size:1.5rem;font-weight:800;color:#333;margin-bottom:16px;">
    <?php if ($q): ?>
    Результаты поиска: «<?= sanitize($q) ?>»
    <?php else: ?>
    Поиск
    <?php endif; ?>
  </h1>

  <!-- Search form -->
  <form action="" method="get" style="margin-bottom:24px;">
    <div style="display:flex;gap:10px;max-width:600px;">
      <input type="text" name="q" class="az-input" value="<?= sanitize($q) ?>"
             placeholder="Номер детали или название..." style="flex:1;">
      <button type="submit" class="az-btn az-btn-primary">Искать</button>
    </div>
  </form>

  <?php if (mb_strlen($q) < 2 && $q): ?>
  <div class="az-alert az-alert-warning">Введите минимум 2 символа для поиска.</div>
  <?php elseif ($q && empty($parts)): ?>
  <div class="az-alert az-alert-info">По запросу «<?= sanitize($q) ?>» ничего не найдено. Попробуйте другой номер или название.</div>
  <?php elseif (!empty($parts)): ?>
  <p style="color:#999;font-size:13px;margin-bottom:16px;">Найдено: <?= $total ?> позиций</p>
  <div class="az-shop-grid-4">
    <?php foreach ($parts as $p):
      $stock = getStockStatus((int)$p['stock']);
    ?>
    <div class="az-part-card">
      <div class="az-part-card-img">
        <a href="<?= APP_URL ?>/catalog/part.php?id=<?= $p['id'] ?>">
          <div class="az-part-card-img-placeholder">
            <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1"><rect x="2" y="7" width="20" height="10" rx="1"/></svg>
          </div>
        </a>
        <span class="az-part-number-badge"><?= sanitize($p['part_number']) ?></span>
      </div>
      <div class="az-part-card-body">
        <div class="az-part-card-brand"><?= sanitize($p['brand_name']) ?></div>
        <div class="az-part-card-name"><a href="<?= APP_URL ?>/catalog/part.php?id=<?= $p['id'] ?>"><?= sanitize(truncate($p['name'],55)) ?></a></div>
        <div class="d-flex align-items-center gap-10" style="margin-bottom:4px;">
          <span class="az-part-card-price"><?= formatPriceInCurrency((float)$p['price']) ?></span>
          <span class="az-badge az-badge-<?= $stock['class'] ?>"><?= $stock['label'] ?></span>
        </div>
      </div>
      <div class="az-part-card-footer">
        <?php if (isLoggedIn()): ?>
        <button class="az-btn az-btn-primary az-btn-sm az-btn-block" data-add-cart="<?= $p['id'] ?>">В корзину</button>
        <?php else: ?>
        <a href="<?= APP_URL ?>/auth/login.php" class="az-btn az-btn-outline az-btn-sm az-btn-block">Войти</a>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if ($totalPages > 1): ?>
  <div class="az-pagination">
    <?php $qp = $_GET;
    if ($page > 1): $qp['page'] = $page-1; ?><a href="?<?= http_build_query($qp) ?>" class="az-page-link">‹</a><?php endif; ?>
    <?php for ($i=max(1,$page-2);$i<=min($totalPages,$page+2);$i++): $qp['page']=$i; ?>
    <a href="?<?= http_build_query($qp) ?>" class="az-page-link <?= $i==$page?'active':'' ?>"><?= $i ?></a>
    <?php endfor; ?>
    <?php if ($page < $totalPages): $qp['page']=$page+1; ?><a href="?<?= http_build_query($qp) ?>" class="az-page-link">›</a><?php endif; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
