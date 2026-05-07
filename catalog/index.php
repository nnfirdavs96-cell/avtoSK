<?php
require_once dirname(__DIR__) . '/config/config.php';
$pageTitle = 'Каталог запчастей';
$db = getDB();

$catSlug  = trim($_GET['category'] ?? '');
$brandId  = (int)($_GET['brand'] ?? 0);
$priceMin = (float)($_GET['price_min'] ?? 0);
$priceMax = (float)($_GET['price_max'] ?? 0);
$sort     = in_array($_GET['sort'] ?? '', ['price_asc','price_desc','name_asc','newest']) ? $_GET['sort'] : 'newest';
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = (int)getSetting('items_per_page','12');
$view     = ($_GET['view'] ?? 'grid') === 'list' ? 'list' : 'grid';

$currentCat = null;
if ($catSlug) {
    $s = $db->prepare("SELECT * FROM categories WHERE slug=? AND is_active=1");
    $s->execute([$catSlug]);
    $currentCat = $s->fetch();
}

$where = ['p.is_active=1'];
$params = [];

if ($currentCat) {
    $sub = $db->prepare("SELECT id FROM categories WHERE parent_id=? AND is_active=1");
    $sub->execute([$currentCat['id']]);
    $subIds = array_column($sub->fetchAll(), 'id');
    $subIds[] = $currentCat['id'];
    $in = implode(',', array_fill(0, count($subIds), '?'));
    $where[]  = "p.category_id IN ($in)";
    $params   = array_merge($params, $subIds);
}
if ($brandId)  { $where[] = 'p.brand_id=?';  $params[] = $brandId; }
if ($priceMin) { $where[] = 'p.price>=?';     $params[] = $priceMin; }
if ($priceMax) { $where[] = 'p.price<=?';     $params[] = $priceMax; }

$whereSQL = 'WHERE ' . implode(' AND ', $where);
$cntStmt  = $db->prepare("SELECT COUNT(*) FROM parts p $whereSQL");
$cntStmt->execute($params);
$total      = (int)$cntStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$orderMap = [
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'name_asc'   => 'p.name ASC',
    'newest'     => 'p.created_at DESC',
];
$orderSQL = $orderMap[$sort] ?? 'p.created_at DESC';

$partsStmt = $db->prepare(
    "SELECT p.*, b.name AS brand_name, c.name AS category_name
     FROM parts p
     LEFT JOIN brands b ON b.id=p.brand_id
     LEFT JOIN categories c ON c.id=p.category_id
     $whereSQL ORDER BY $orderSQL LIMIT $perPage OFFSET $offset"
);
$partsStmt->execute($params);
$parts = $partsStmt->fetchAll();

$allCategories = getCategories();
$allBrands     = getBrands();

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="container" style="padding-top:24px;">
  <!-- breadcrumb -->
  <div class="az-breadcrumb">
    <a href="<?= APP_URL ?>/index.php">Главная</a><span>/</span>
    <?php if ($currentCat): ?>
    <a href="<?= APP_URL ?>/catalog/index.php">Каталог</a><span>/</span>
    <span><?= sanitize($currentCat['name']) ?></span>
    <?php else: ?>
    <span>Каталог</span>
    <?php endif; ?>
  </div>

  <div class="az-catalog-layout">
    <!-- Sidebar -->
    <aside class="az-sidebar">
      <form method="get" action="" id="filter-form">
        <?php if ($view!=='grid'): ?><input type="hidden" name="view" value="<?= sanitize($view) ?>"><?php endif; ?>

        <div class="az-filter-section">
          <div class="az-filter-title">Категории</div>
          <ul class="az-filter-list">
            <li><a href="<?= APP_URL ?>/catalog/index.php" class="<?= !$catSlug?'active':'' ?>">Все категории</a></li>
            <?php foreach ($allCategories as $c): if ($c['parent_id']!==null) continue; ?>
            <li>
              <a href="?category=<?= sanitize($c['slug']) ?><?= $brandId?'&brand='.$brandId:'' ?>"
                 class="<?= $catSlug===$c['slug']?'active':'' ?>">
                <?= sanitize($c['name']) ?>
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div class="az-filter-section">
          <div class="az-filter-title">Бренды</div>
          <ul class="az-filter-list">
            <li><a href="?<?= $catSlug?'category='.$catSlug:'' ?>" class="<?= !$brandId?'active':'' ?>">Все бренды</a></li>
            <?php foreach ($allBrands as $b): ?>
            <li>
              <a href="?<?= $catSlug?'category='.$catSlug.'&':'' ?>brand=<?= $b['id'] ?>"
                 class="<?= $brandId===(int)$b['id']?'active':'' ?>">
                <?= sanitize($b['name']) ?>
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div class="az-filter-section">
          <div class="az-filter-title">Цена (₽)</div>
          <div class="az-price-inputs">
            <input type="number" name="price_min" class="az-input" style="font-size:13px;padding:7px;" placeholder="от" value="<?= $priceMin>0?$priceMin:'' ?>" min="0">
            <span class="az-price-sep">—</span>
            <input type="number" name="price_max" class="az-input" style="font-size:13px;padding:7px;" placeholder="до" value="<?= $priceMax>0?$priceMax:'' ?>" min="0">
          </div>
          <?php if ($catSlug): ?><input type="hidden" name="category" value="<?= sanitize($catSlug) ?>"><?php endif; ?>
          <?php if ($brandId): ?><input type="hidden" name="brand" value="<?= $brandId ?>"><?php endif; ?>
          <input type="hidden" name="sort" value="<?= sanitize($sort) ?>">
          <button type="submit" class="az-btn az-btn-primary az-btn-sm az-btn-block" style="margin-top:10px;">Применить</button>
          <a href="<?= APP_URL ?>/catalog/index.php" class="az-btn az-btn-outline az-btn-sm az-btn-block" style="margin-top:6px;">Сбросить</a>
        </div>
      </form>
    </aside>

    <!-- Main -->
    <div>
      <div class="d-flex align-items-center justify-content-between mb-16" style="flex-wrap:wrap;gap:10px;">
        <div>
          <h1 style="font-size:1.5rem;font-weight:800;color:#333;">
            <?= $currentCat ? sanitize($currentCat['name']) : 'Все запчасти' ?>
          </h1>
          <span class="text-muted" style="font-size:13px;"><?= $total ?> позиций</span>
        </div>
      </div>

      <!-- Toolbar -->
      <div class="az-catalog-toolbar">
        <form id="sort-form" method="get" action="" style="display:inline;">
          <?php if ($catSlug):  ?><input type="hidden" name="category"  value="<?= sanitize($catSlug) ?>"><?php endif; ?>
          <?php if ($brandId):  ?><input type="hidden" name="brand"     value="<?= $brandId ?>"><?php endif; ?>
          <?php if ($priceMin): ?><input type="hidden" name="price_min" value="<?= $priceMin ?>"><?php endif; ?>
          <?php if ($priceMax): ?><input type="hidden" name="price_max" value="<?= $priceMax ?>"><?php endif; ?>
          <input type="hidden" name="view" value="<?= sanitize($view) ?>">
          <select name="sort" class="az-select" style="width:auto;font-size:13px;padding:7px 10px;" onchange="this.form.submit()">
            <option value="newest"     <?= $sort==='newest'    ?'selected':''?>>Новинки</option>
            <option value="price_asc"  <?= $sort==='price_asc' ?'selected':''?>>Цена ↑</option>
            <option value="price_desc" <?= $sort==='price_desc'?'selected':''?>>Цена ↓</option>
            <option value="name_asc"   <?= $sort==='name_asc'  ?'selected':''?>>Название А-Я</option>
          </select>
        </form>
        <div class="az-view-toggle" style="margin-left:auto;">
          <a href="?<?= http_build_query(array_merge($_GET,['view'=>'grid'])) ?>" class="az-view-btn <?= $view==='grid'?'active':'' ?>" title="Сетка">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
          </a>
          <a href="?<?= http_build_query(array_merge($_GET,['view'=>'list'])) ?>" class="az-view-btn <?= $view==='list'?'active':'' ?>" title="Список">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3" y2="6"/><line x1="3" y1="12" x2="3" y2="12"/><line x1="3" y1="18" x2="3" y2="18"/></svg>
          </a>
        </div>
      </div>

      <?php if (empty($parts)): ?>
      <div class="text-center" style="padding:60px 0;">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <p style="color:#999;margin-top:12px;">По вашему запросу ничего не найдено.</p>
        <a href="<?= APP_URL ?>/catalog/index.php" class="az-btn az-btn-outline az-btn-sm" style="margin-top:12px;">Сбросить фильтры</a>
      </div>

      <?php elseif ($view==='list'): ?>
      <div class="az-parts-list">
        <?php foreach ($parts as $p):
          $stock = getStockStatus((int)$p['stock']);
        ?>
        <div class="az-part-list-item">
          <div class="az-part-list-thumb">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1"><rect x="2" y="7" width="20" height="10" rx="1"/></svg>
          </div>
          <div class="az-part-list-info">
            <div class="az-part-list-num"><?= sanitize($p['part_number']) ?></div>
            <div class="az-part-list-name">
              <a href="<?= APP_URL ?>/catalog/part.php?id=<?= $p['id'] ?>"><?= sanitize($p['name']) ?></a>
            </div>
            <div class="az-part-list-brand"><?= sanitize($p['brand_name']) ?> · <?= sanitize($p['category_name']) ?></div>
          </div>
          <div class="d-flex align-items-center gap-10" style="flex-shrink:0;">
            <span class="az-badge az-badge-<?= $stock['class'] ?>"><?= $stock['label'] ?></span>
            <span class="az-part-list-price"><?= formatPriceInCurrency((float)$p['price']) ?></span>
            <?php if (isLoggedIn()): ?>
            <button class="az-btn az-btn-primary az-btn-sm" data-add-cart="<?= $p['id'] ?>">В корзину</button>
            <?php else: ?>
            <a href="<?= APP_URL ?>/auth/login.php" class="az-btn az-btn-outline az-btn-sm">Войти</a>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <?php else: ?>
      <div class="az-shop-grid-4">
        <?php foreach ($parts as $p):
          $stock = getStockStatus((int)$p['stock']);
        ?>
        <div class="az-part-card">
          <div class="az-part-card-img">
            <a href="<?= APP_URL ?>/catalog/part.php?id=<?= $p['id'] ?>">
              <div class="az-part-card-img-placeholder">
                <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1"><rect x="2" y="7" width="20" height="10" rx="1"/><path d="M8 7V5M16 7V5"/></svg>
              </div>
            </a>
            <span class="az-part-number-badge"><?= sanitize($p['part_number']) ?></span>
          </div>
          <div class="az-part-card-body">
            <div class="az-part-card-brand"><?= sanitize($p['brand_name']) ?></div>
            <div class="az-part-card-name">
              <a href="<?= APP_URL ?>/catalog/part.php?id=<?= $p['id'] ?>"><?= sanitize(truncate($p['name'],55)) ?></a>
            </div>
            <div class="d-flex align-items-center gap-10" style="margin-bottom:4px;">
              <span class="az-part-card-price"><?= formatPriceInCurrency((float)$p['price']) ?></span>
              <span class="az-badge az-badge-<?= $stock['class'] ?>"><?= $stock['label'] ?></span>
            </div>
          </div>
          <div class="az-part-card-footer">
            <?php if (isLoggedIn()): ?>
            <button class="az-btn az-btn-primary az-btn-sm az-btn-block" data-add-cart="<?= $p['id'] ?>">В корзину</button>
            <?php else: ?>
            <a href="<?= APP_URL ?>/auth/login.php" class="az-btn az-btn-outline az-btn-sm az-btn-block">Войдите для заказа</a>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
      <div class="az-pagination">
        <?php
        $qp = $_GET;
        if ($page > 1): $qp['page'] = $page - 1; ?>
        <a href="?<?= http_build_query($qp) ?>" class="az-page-link">‹</a>
        <?php endif; ?>
        <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++):
          $qp['page'] = $i; ?>
        <a href="?<?= http_build_query($qp) ?>" class="az-page-link <?= $i==$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): $qp['page'] = $page+1; ?>
        <a href="?<?= http_build_query($qp) ?>" class="az-page-link">›</a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
