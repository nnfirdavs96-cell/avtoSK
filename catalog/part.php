<?php
require_once dirname(__DIR__) . '/config/config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { flashMessage('danger','Товар не найден.'); redirect(APP_URL.'/catalog/index.php'); }

$db   = getDB();
$stmt = $db->prepare(
    "SELECT p.*, b.name AS brand_name, b.country AS brand_country, c.name AS category_name, c.slug AS category_slug
     FROM parts p
     LEFT JOIN brands b ON b.id=p.brand_id
     LEFT JOIN categories c ON c.id=p.category_id
     WHERE p.id=? AND p.is_active=1"
);
$stmt->execute([$id]);
$part = $stmt->fetch();
if (!$part) { flashMessage('danger','Товар не найден.'); redirect(APP_URL.'/catalog/index.php'); }

$relStmt = $db->prepare(
    "SELECT p.*, b.name AS brand_name FROM parts p LEFT JOIN brands b ON b.id=p.brand_id
     WHERE p.is_active=1 AND p.id!=? AND (p.category_id=? OR p.brand_id=?) LIMIT 6"
);
$relStmt->execute([$id, $part['category_id'], $part['brand_id']]);
$related = $relStmt->fetchAll();

$stock     = getStockStatus((int)$part['stock']);
$warehouse = getWarehouseStock($part['part_number']);
$pageTitle = $part['name'];
$activeCur = getCurrentCurrency();

/* Image for this product */
$prodImg = APP_URL . '/assets/img/product/product' . (($id % 12) ?: 12) . '.jpg';
$prodImg2 = APP_URL . '/assets/img/product/product' . ((($id+1) % 12) ?: 12) . '.jpg';

require_once dirname(__DIR__) . '/includes/header.php';
?>

<!--breadcrumb area start-->
<div class="breadcrumb_area">
  <div class="container">
    <div class="row"><div class="col-12">
      <div class="breadcrumb_content">
        <ul>
          <li><a href="<?= APP_URL ?>/index.php">Главная</a></li>
          <li><a href="<?= APP_URL ?>/catalog/index.php">Каталог</a></li>
          <li><a href="<?= APP_URL ?>/catalog/index.php?category=<?= sanitize($part['category_slug']) ?>"><?= sanitize($part['category_name']) ?></a></li>
          <li class="active"><?= sanitize($part['part_number']) ?></li>
        </ul>
      </div>
    </div></div>
  </div>
</div>
<!--breadcrumb area end-->

<!--product details area start-->
<div class="product_details_area mb-70">
  <div class="container">
    <div class="row">

      <!-- Product images -->
      <div class="col-lg-5 col-md-6">
        <div class="product_details_left">
          <div class="product-details-tab">
            <div class="tab-content product-details-large" id="myTabContent">
              <div class="tab-pane fade show active" id="tab1" role="tabpanel">
                <div class="product_details_images">
                  <img src="<?= $prodImg ?>" alt="<?= sanitize($part['name']) ?>">
                </div>
              </div>
              <div class="tab-pane fade" id="tab2" role="tabpanel">
                <div class="product_details_images">
                  <img src="<?= $prodImg2 ?>" alt="<?= sanitize($part['name']) ?>">
                </div>
              </div>
            </div>
            <div class="details-tab-inner">
              <ul class="nav product_details_tab" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab1" role="tab"><img src="<?= $prodImg ?>" alt="" style="width:70px;height:70px;object-fit:cover;"></a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab2" role="tab"><img src="<?= $prodImg2 ?>" alt="" style="width:70px;height:70px;object-fit:cover;"></a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Product info -->
      <div class="col-lg-7 col-md-6">
        <div class="product_d_right">

          <div class="product_category">
            <span>Категория: </span>
            <a href="<?= APP_URL ?>/catalog/index.php?category=<?= sanitize($part['category_slug']) ?>"><?= sanitize($part['category_name']) ?></a>
          </div>

          <h1><?= sanitize($part['name']) ?></h1>

          <div class="product_rating" style="margin:10px 0;">
            <ul>
              <li><a href="#"><i class="ion-android-star"></i></a></li>
              <li><a href="#"><i class="ion-android-star"></i></a></li>
              <li><a href="#"><i class="ion-android-star"></i></a></li>
              <li><a href="#"><i class="ion-android-star"></i></a></li>
              <li><a href="#"><i class="ion-android-star-outline"></i></a></li>
            </ul>
          </div>

          <div class="price_box" style="margin:16px 0;">
            <span class="current_price" style="font-size:28px;"><?= formatPriceInCurrency((float)$part['price']) ?></span>
            <?php if ($activeCur !== 'RUB'): ?>
            <span class="old_price" style="font-size:16px;"><?= formatPrice((float)$part['price']) ?></span>
            <?php endif; ?>
          </div>

          <!-- Availability -->
          <div style="margin-bottom:16px;">
            <span style="font-weight:600;">Наличие: </span>
            <span class="az-badge az-badge-<?= $stock['class'] ?>"><?= $stock['label'] ?></span>
            <?php if ($part['stock'] > 0): ?>
            <span style="font-size:13px;color:#777;margin-left:8px;"><?= (int)$part['stock'] ?> шт. на складе</span>
            <?php endif; ?>
          </div>

          <!-- Specs table -->
          <div class="product_d_table" style="margin-bottom:20px;">
            <table>
              <tbody>
                <tr><th>Номер детали</th><td style="color:#e74c3c;font-family:monospace;font-weight:700;"><?= sanitize($part['part_number']) ?></td></tr>
                <tr><th>Производитель</th><td><?= sanitize($part['brand_name']) ?></td></tr>
                <?php if ($part['brand_country']): ?><tr><th>Страна</th><td><?= sanitize($part['brand_country']) ?></td></tr><?php endif; ?>
                <tr><th>Категория</th><td><?= sanitize($part['category_name']) ?></td></tr>
                <?php if ($part['weight']): ?><tr><th>Вес</th><td><?= sanitize($part['weight']) ?> кг</td></tr><?php endif; ?>
                <?php if ($part['dimensions']): ?><tr><th>Размеры</th><td><?= sanitize($part['dimensions']) ?></td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Warehouse -->
          <?php if ($warehouse && $warehouse['warehouse_stock'] > 0): ?>
          <div class="az-warehouse-box" style="margin-bottom:16px;">
            <div class="label">🏭 Склад Москва</div>
            <div class="info">
              Остаток: <strong><?= (int)$warehouse['warehouse_stock'] ?> шт.</strong>
              <?php if ($warehouse['warehouse_price']): ?> · Цена: <strong><?= formatPriceInCurrency((float)$warehouse['warehouse_price']) ?></strong><?php endif; ?>
              <?php if ($warehouse['warehouse_eta']): ?> · Срок: <strong><?= sanitize($warehouse['warehouse_eta']) ?></strong><?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

          <!-- Add to cart -->
          <?php if (isLoggedIn()): ?>
            <?php if ($part['stock'] > 0): ?>
            <div class="product_count" style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
              <div class="cart_plus_minus" style="display:flex;align-items:center;border:1px solid #ddd;border-radius:4px;overflow:hidden;">
                <button type="button" class="dec qtybutton" style="padding:8px 14px;border:none;background:#f8f8f8;cursor:pointer;font-size:18px;" onclick="var i=document.getElementById('qty-input');i.value=Math.max(1,parseInt(i.value)-1)">-</button>
                <input type="number" id="qty-input" value="1" min="1" max="<?= min((int)$part['stock'],99) ?>" style="width:50px;text-align:center;border:none;border-left:1px solid #ddd;border-right:1px solid #ddd;padding:8px 0;font-size:16px;">
                <button type="button" class="inc qtybutton" style="padding:8px 14px;border:none;background:#f8f8f8;cursor:pointer;font-size:18px;" onclick="var i=document.getElementById('qty-input');i.value=Math.min(<?= min((int)$part['stock'],99) ?>,parseInt(i.value)+1)">+</button>
              </div>
              <button class="button" id="add-cart-btn" data-add-cart="<?= (int)$part['id'] ?>" style="padding:12px 30px;">
                <i class="fa fa-shopping-cart"></i> В корзину
              </button>
            </div>
            <?php else: ?>
            <div class="az-alert az-alert-warning" style="margin-bottom:16px;">Товар временно отсутствует</div>
            <?php endif; ?>
          <?php else: ?>
          <a href="<?= APP_URL ?>/auth/login.php" class="button" style="display:inline-block;padding:12px 30px;margin-bottom:16px;">
            <i class="fa fa-sign-in"></i> Войдите для заказа
          </a>
          <?php endif; ?>

        </div>
      </div>

    </div>

    <!-- Description tab -->
    <?php if ($part['description']): ?>
    <div class="row" style="margin-top:50px;">
      <div class="col-12">
        <div class="product_d_info">
          <ul class="nav product_info_button" role="tablist">
            <li><a class="active" data-bs-toggle="tab" href="#desc_tab" role="tab">Описание</a></li>
          </ul>
          <div class="tab-content product_d_table">
            <div class="tab-pane fade show active" id="desc_tab" role="tabpanel">
              <p style="color:#555;line-height:1.8;"><?= nl2br(sanitize($part['description'])) ?></p>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Related products -->
    <?php if (!empty($related)): ?>
    <div class="row" style="margin-top:60px;">
      <div class="col-12">
        <div class="section_title" style="margin-bottom:30px;">
          <h2><span>Похожие</span> товары</h2>
        </div>
      </div>
    </div>
    <div class="row shop_wrapper">
      <?php
      $ri = ($id % 12) + 1;
      foreach ($related as $rel):
        $rimg1 = (($ri-1)%12)+1; $ri++;
        $rimg2 = (($ri-1)%12)+1;
        $rs = getStockStatus((int)$rel['stock']);
      ?>
      <div class="col-lg-4 col-md-6 col-sm-6">
        <article class="single_product">
          <figure>
            <div class="product_thumb">
              <a class="primary_img" href="<?= APP_URL ?>/catalog/part.php?id=<?= $rel['id'] ?>">
                <img src="<?= APP_URL ?>/assets/img/product/product<?= $rimg1 ?>.jpg" alt="<?= sanitize($rel['name']) ?>">
              </a>
              <a class="secondary_img" href="<?= APP_URL ?>/catalog/part.php?id=<?= $rel['id'] ?>">
                <img src="<?= APP_URL ?>/assets/img/product/product<?= $rimg2 ?>.jpg" alt="<?= sanitize($rel['name']) ?>">
              </a>
              <div class="label_product"><span class="label_new"><?= $rs['label'] ?></span></div>
              <div class="quick_button"><a href="<?= APP_URL ?>/catalog/part.php?id=<?= $rel['id'] ?>" title="Подробнее"><i class="icon-eye"></i></a></div>
            </div>
            <div class="product_content">
              <div class="product_content_inner">
                <p class="manufacture_product"><a href="#"><?= sanitize($rel['brand_name']) ?></a></p>
                <h4 class="product_name"><a href="<?= APP_URL ?>/catalog/part.php?id=<?= $rel['id'] ?>"><?= sanitize(truncate($rel['name'],55)) ?></a></h4>
                <div class="price_box"><span class="current_price"><?= formatPriceInCurrency((float)$rel['price']) ?></span></div>
              </div>
              <div class="action_links"><ul>
                <?php if (isLoggedIn()): ?>
                <li class="add_to_cart"><a href="#" data-add-cart="<?= $rel['id'] ?>" title="В корзину">В корзину</a></li>
                <?php else: ?>
                <li class="add_to_cart"><a href="<?= APP_URL ?>/auth/login.php">Войти</a></li>
                <?php endif; ?>
              </ul></div>
            </div>
          </figure>
        </article>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
</div>
<!--product details area end-->

<script>
document.getElementById('qty-input')?.addEventListener('change', function() {
    var btn = document.getElementById('add-cart-btn');
    if (btn) btn.dataset.qty = this.value;
});
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
