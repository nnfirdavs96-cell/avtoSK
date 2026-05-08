<?php
require_once dirname(__DIR__) . '/config/config.php';

$q      = trim($_GET['q'] ?? '');
$cat    = trim($_GET['cat'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage= 16;

$parts = [];
$total = 0;
$totalPages = 1;

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
}

$pageTitle = $q ? 'Поиск: ' . sanitize($q) : 'Поиск';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<!--breadcrumb area start-->
<div class="breadcrumb_area">
  <div class="container">
    <div class="row"><div class="col-12">
      <div class="breadcrumb_content">
        <ul>
          <li><a href="<?= APP_URL ?>/index.php">Главная</a></li>
          <li class="active">Поиск</li>
        </ul>
      </div>
    </div></div>
  </div>
</div>
<!--breadcrumb area end-->

<!--search results area start-->
<div class="shop_main_area mb-70">
  <div class="container">

    <!-- Search form -->
    <div class="row" style="margin-bottom:30px;">
      <div class="col-lg-8 col-md-10 mx-auto">
        <form action="" method="get">
          <div style="display:flex;gap:0;border:2px solid #e74c3c;border-radius:4px;overflow:hidden;">
            <input type="text" name="q" value="<?= sanitize($q) ?>"
                   placeholder="Номер детали или название..."
                   style="flex:1;padding:12px 18px;border:none;outline:none;font-size:15px;color:#333;">
            <button type="submit" class="button" style="border-radius:0;padding:12px 28px;white-space:nowrap;">
              <i class="fa fa-search"></i> Найти
            </button>
          </div>
        </form>
      </div>
    </div>

    <?php if (mb_strlen($q) >= 1 && mb_strlen($q) < 2): ?>
    <div class="row"><div class="col-12">
      <p style="text-align:center;color:#888;padding:40px 0;">Введите минимум 2 символа для поиска.</p>
    </div></div>

    <?php elseif ($q && empty($parts)): ?>
    <div class="row"><div class="col-12 text-center" style="padding:80px 0;">
      <i class="fa fa-search fa-3x" style="color:#ccc;margin-bottom:20px;display:block;"></i>
      <p style="color:#999;font-size:16px;">По запросу «<?= sanitize($q) ?>» ничего не найдено.</p>
      <p style="color:#bbb;font-size:13px;">Попробуйте другой номер детали или название.</p>
      <a href="<?= APP_URL ?>/catalog/index.php" class="button" style="margin-top:20px;display:inline-block;padding:12px 30px;">В каталог</a>
    </div></div>

    <?php elseif (!empty($parts)): ?>

    <!-- Toolbar -->
    <div class="shop_toolbar_wrapper" style="margin-bottom:20px;">
      <div class="shop_toolbar_result">
        <p>По запросу «<strong><?= sanitize($q) ?></strong>» найдено <strong><?= $total ?></strong> результатов</p>
      </div>
    </div>

    <!-- Products grid -->
    <div class="row shop_wrapper">
      <?php
      $gi = 1;
      foreach ($parts as $p):
        $stock = getStockStatus((int)$p['stock']);
        $img1 = (($gi-1)%12)+1;
        $img2 = ($gi%12)+1;
        $gi++;
      ?>
      <div class="col-lg-3 col-md-4 col-sm-6">
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
                  <a href="<?= APP_URL ?>/catalog/index.php?brand=<?= (int)$p['brand_id'] ?>"><?= sanitize($p['brand_name'] ?? '') ?></a>
                </p>
                <h4 class="product_name">
                  <a href="<?= APP_URL ?>/catalog/part.php?id=<?= $p['id'] ?>"><?= sanitize(truncate($p['name'],55)) ?></a>
                </h4>
                <p style="font-size:11px;color:#aaa;font-family:monospace;margin-bottom:6px;"><?= sanitize($p['part_number']) ?></p>
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

    <?php else: ?>
    <!-- No query yet -->
    <div class="row"><div class="col-12 text-center" style="padding:60px 0;">
      <i class="fa fa-search fa-4x" style="color:#e74c3c;opacity:.4;margin-bottom:20px;display:block;"></i>
      <p style="color:#888;font-size:16px;">Введите номер детали или название для поиска.</p>
    </div></div>
    <?php endif; ?>

  </div>
</div>
<!--search results area end-->

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
