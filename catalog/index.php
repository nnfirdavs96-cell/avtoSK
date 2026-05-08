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

$orderMap = ['price_asc'=>'p.price ASC','price_desc'=>'p.price DESC','name_asc'=>'p.name ASC','newest'=>'p.created_at DESC'];
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

$allBrands = getBrands();
$pageTitle = $currentCat ? sanitize($currentCat['name']) : 'Каталог запчастей';

require_once dirname(__DIR__) . '/includes/header.php';
?>

<!--breadcrumb area start-->
<div class="breadcrumb_area">
  <div class="container">
    <div class="row">
      <div class="col-12">
        <div class="breadcrumb_content">
          <ul>
            <li><a href="<?= APP_URL ?>/index.php">Главная</a></li>
            <?php if ($currentCat): ?>
            <li><a href="<?= APP_URL ?>/catalog/index.php">Каталог</a></li>
            <li class="active"><?= sanitize($currentCat['name']) ?></li>
            <?php else: ?>
            <li class="active">Каталог</li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>
<!--breadcrumb area end-->

<!--shop main area start-->
<div class="shop_main_area mb-70">
  <div class="container">
    <div class="row">

      <!--sidebar start-->
      <div class="col-lg-3 col-md-4">
        <aside class="sidebar_widget">

          <!-- Categories -->
          <div class="widget_list">
            <h3 class="widget_title">Категории</h3>
            <div class="widget_categories">
              <ul>
                <?php
                $allCats = getCategories();
                foreach ($allCats as $c):
                  if ($c['parent_id'] !== null) continue;
                ?>
                <li class="<?= $catSlug===$c['slug']?'active':'' ?>">
                  <a href="<?= APP_URL ?>/catalog/index.php?category=<?= sanitize($c['slug']) ?><?= $brandId?'&brand='.$brandId:'' ?>">
                    <?= sanitize($c['name']) ?>
                  </a>
                </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>

          <!-- Price filter -->
          <div class="widget_list">
            <h3 class="widget_title">Цена (₽)</h3>
            <form method="get" action="<?= APP_URL ?>/catalog/index.php" id="filter-form">
              <?php if ($catSlug): ?><input type="hidden" name="category" value="<?= sanitize($catSlug) ?>"><?php endif; ?>
              <?php if ($brandId): ?><input type="hidden" name="brand" value="<?= $brandId ?>"><?php endif; ?>
              <input type="hidden" name="sort" value="<?= sanitize($sort) ?>">
              <div class="price_range_filter">
                <input type="number" name="price_min" placeholder="от" value="<?= $priceMin>0?$priceMin:'' ?>" min="0" class="price_input">
                <span>—</span>
                <input type="number" name="price_max" placeholder="до" value="<?= $priceMax>0?$priceMax:'' ?>" min="0" class="price_input">
              </div>
              <button type="submit" class="btn btn-sm btn-dark mt-2" style="width:100%;">Применить</button>
              <a href="<?= APP_URL ?>/catalog/index.php<?= $catSlug?'?category='.$catSlug:'' ?>" class="btn btn-sm btn-outline-secondary mt-1" style="width:100%;display:block;text-align:center;">Сбросить</a>
            </form>
          </div>

          <!-- Brands -->
          <div class="widget_list">
            <h3 class="widget_title">Бренды</h3>
            <div class="widget_categories">
              <ul>
                <?php foreach ($allBrands as $b): ?>
                <li class="<?= $brandId===(int)$b['id']?'active':'' ?>">
                  <a href="<?= APP_URL ?>/catalog/index.php?<?= $catSlug?'category='.$catSlug.'&':'' ?>brand=<?= $b['id'] ?>">
                    <?= sanitize($b['name']) ?>
                  </a>
                </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>

        </aside>
      </div>
      <!--sidebar end-->

      <!--shop content start-->
      <div class="col-lg-9 col-md-8">

        <!-- Toolbar -->
        <div class="shop_toolbar_wrapper">
          <div class="shop_toolbar_btn">
            <button class="btn-grid <?= $view==='grid'?'active':'' ?>" data-view="grid" title="Сетка">
              <i class="fa fa-th"></i>
            </button>
            <button class="btn-list <?= $view==='list'?'active':'' ?>" data-view="list" title="Список">
              <i class="fa fa-list"></i>
            </button>
          </div>
          <div class="shop_toolbar_result">
            <p>Показано <strong><?= count($parts) ?></strong> из <strong><?= $total ?></strong> товаров</p>
          </div>
          <div class="toolbar_sorter_right">
            <form id="sort-form" method="get" action="">
              <?php if ($catSlug):  ?><input type="hidden" name="category"  value="<?= sanitize($catSlug) ?>"><?php endif; ?>
              <?php if ($brandId):  ?><input type="hidden" name="brand"     value="<?= $brandId ?>"><?php endif; ?>
              <?php if ($priceMin): ?><input type="hidden" name="price_min" value="<?= $priceMin ?>"><?php endif; ?>
              <?php if ($priceMax): ?><input type="hidden" name="price_max" value="<?= $priceMax ?>"><?php endif; ?>
              <input type="hidden" name="view" value="<?= sanitize($view) ?>">
              <label>Сортировка:</label>
              <select name="sort" class="nice_Select" onchange="this.form.submit()">
                <option value="newest"     <?= $sort==='newest'    ?'selected':''?>>Новинки</option>
                <option value="price_asc"  <?= $sort==='price_asc' ?'selected':''?>>Цена ↑</option>
                <option value="price_desc" <?= $sort==='price_desc'?'selected':''?>>Цена ↓</option>
                <option value="name_asc"   <?= $sort==='name_asc'  ?'selected':''?>>Название А-Я</option>
              </select>
            </form>
          </div>
        </div>
        <!-- Toolbar end -->

        <?php if (empty($parts)): ?>
        <div class="text-center" style="padding:80px 0;">
          <i class="fa fa-search fa-3x" style="color:#ccc;margin-bottom:20px;display:block;"></i>
          <p style="color:#999;font-size:16px;">По вашему запросу ничего не найдено.</p>
          <a href="<?= APP_URL ?>/catalog/index.php" class="btn btn-dark mt-3">Сбросить фильтры</a>
        </div>

        <?php elseif ($view === 'list'): ?>
        <!-- List view -->
        <div class="shop_list_wrapper">
          <?php
          $li = 1;
          foreach ($parts as $p):
            $stock = getStockStatus((int)$p['stock']);
            $img = (($li-1)%12)+1; $li++;
          ?>
          <div class="row single_list_product align-items-center" style="border-bottom:1px solid #eee;padding:20px 0;margin:0;">
            <div class="col-md-3 col-sm-4">
              <div class="product_list_thumb">
                <a href="<?= APP_URL ?>/catalog/part.php?id=<?= $p['id'] ?>">
                  <img src="<?= APP_URL ?>/assets/img/product/product<?= $img ?>.jpg" alt="<?= sanitize($p['name']) ?>" style="width:100%;max-width:120px;">
                </a>
              </div>
            </div>
            <div class="col-md-6 col-sm-8">
              <div class="product_list_content">
                <p class="manufacture_product" style="font-size:12px;color:#999;margin-bottom:4px;"><?= sanitize($p['brand_name']) ?></p>
                <h4 style="font-size:15px;margin-bottom:8px;"><a href="<?= APP_URL ?>/catalog/part.php?id=<?= $p['id'] ?>"><?= sanitize($p['name']) ?></a></h4>
                <p style="font-size:12px;color:#777;font-family:monospace;">Арт: <?= sanitize($p['part_number']) ?></p>
                <span class="az-badge az-badge-<?= $stock['class'] ?>"><?= $stock['label'] ?></span>
              </div>
            </div>
            <div class="col-md-3 text-right">
              <div class="price_box" style="margin-bottom:12px;">
                <span class="current_price"><?= formatPriceInCurrency((float)$p['price']) ?></span>
              </div>
              <?php if (isLoggedIn()): ?>
              <button class="btn btn-dark btn-sm" data-add-cart="<?= $p['id'] ?>">В корзину</button>
              <?php else: ?>
              <a href="<?= APP_URL ?>/auth/login.php" class="btn btn-outline-dark btn-sm">Войти</a>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <?php else: ?>
        <!-- Grid view -->
        <div class="row shop_wrapper">
          <?php
          $gi = 1;
          foreach ($parts as $p):
            $stock = getStockStatus((int)$p['stock']);
            $img1 = (($gi-1)%12)+1;
            $img2 = ($gi%12)+1;
            $gi++;
          ?>
          <div class="col-lg-4 col-md-6 col-sm-6">
            <article class="single_product">
              <figure>
                <div class="product_thumb">
                  <a class="primary_img" href="<?= APP_URL ?>/catalog/part.php?id=<?= $p['id'] ?>">
                    <img src="<?= APP_URL ?>/assets/img/product/product<?= $img1 ?>.jpg" alt="<?= sanitize($p['name']) ?>">
                  </a>
                  <a class="secondary_img" href="<?= APP_URL ?>/catalog/part.php?id=<?= $p['id'] ?>">
                    <img src="<?= APP_URL ?>/assets/img/product/product<?= $img2 ?>.jpg" alt="<?= sanitize($p['name']) ?>">
                  </a>
                  <?php if ($p['stock'] > 0): ?>
                  <div class="label_product"><span class="label_new">В наличии</span></div>
                  <?php else: ?>
                  <div class="label_product"><span class="label_sale">Нет</span></div>
                  <?php endif; ?>
                  <div class="quick_button">
                    <a href="<?= APP_URL ?>/catalog/part.php?id=<?= $p['id'] ?>" title="Подробнее"><i class="icon-eye"></i></a>
                  </div>
                </div>
                <div class="product_content">
                  <div class="product_content_inner">
                    <p class="manufacture_product">
                      <a href="<?= APP_URL ?>/catalog/index.php?brand=<?= (int)$p['brand_id'] ?>"><?= sanitize($p['brand_name']) ?></a>
                    </p>
                    <h4 class="product_name">
                      <a href="<?= APP_URL ?>/catalog/part.php?id=<?= $p['id'] ?>"><?= sanitize(truncate($p['name'],55)) ?></a>
                    </h4>
                    <div class="product_rating"><ul>
                      <li><a href="#"><i class="ion-android-star-outline"></i></a></li>
                      <li><a href="#"><i class="ion-android-star-outline"></i></a></li>
                      <li><a href="#"><i class="ion-android-star-outline"></i></a></li>
                      <li><a href="#"><i class="ion-android-star-outline"></i></a></li>
                      <li><a href="#"><i class="ion-android-star-outline"></i></a></li>
                    </ul></div>
                    <div class="price_box">
                      <span class="current_price"><?= formatPriceInCurrency((float)$p['price']) ?></span>
                    </div>
                  </div>
                  <div class="action_links">
                    <ul>
                      <?php if (isLoggedIn()): ?>
                      <li class="add_to_cart"><a href="#" data-add-cart="<?= $p['id'] ?>" title="В корзину">В корзину</a></li>
                      <?php else: ?>
                      <li class="add_to_cart"><a href="<?= APP_URL ?>/auth/login.php">Войти</a></li>
                      <?php endif; ?>
                    </ul>
                  </div>
                </div>
              </figure>
            </article>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="shop_page_nav">
          <ul class="page_numbers">
            <?php
            $qp = $_GET;
            if ($page > 1) { $qp['page'] = $page-1; echo '<li><a href="?'.http_build_query($qp).'">‹</a></li>'; }
            for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++) {
                $qp['page'] = $i;
                $cls = $i===$page ? ' class="current"' : '';
                echo '<li'.$cls.'><a href="?'.http_build_query($qp).'">'.$i.'</a></li>';
            }
            if ($page < $totalPages) { $qp['page'] = $page+1; echo '<li><a href="?'.http_build_query($qp).'">›</a></li>'; }
            ?>
          </ul>
        </div>
        <?php endif; ?>

      </div>
      <!--shop content end-->

    </div>
  </div>
</div>
<!--shop main area end-->

<script>
document.querySelectorAll('[data-view]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var view = this.dataset.view;
        var url = new URL(window.location.href);
        url.searchParams.set('view', view);
        window.location.href = url.toString();
    });
});
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
