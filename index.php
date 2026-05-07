<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Главная — Профессиональные автозапчасти';
$db = getDB();

$catStmt   = $db->query("SELECT * FROM categories WHERE is_active=1 AND parent_id IS NULL ORDER BY sort_order LIMIT 6");
$featCats  = $catStmt->fetchAll();

$brandStmt = $db->query("SELECT * FROM brands WHERE is_active=1 ORDER BY name LIMIT 8");
$featBrands= $brandStmt->fetchAll();

$newStmt = $db->query(
    "SELECT p.*, b.name AS brand_name, c.name AS category_name
     FROM parts p
     LEFT JOIN brands b ON b.id=p.brand_id
     LEFT JOIN categories c ON c.id=p.category_id
     WHERE p.is_active=1 ORDER BY p.created_at DESC LIMIT 8"
);
$newParts = $newStmt->fetchAll();

$popularStmt = $db->query(
    "SELECT p.*, b.name AS brand_name, c.name AS category_name
     FROM parts p
     LEFT JOIN brands b ON b.id=p.brand_id
     LEFT JOIN categories c ON c.id=p.category_id
     WHERE p.is_active=1 AND p.stock>0 ORDER BY p.price DESC LIMIT 8"
);
$popularParts = $popularStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- slider area start -->
<section class="slider_section mb-80">
  <div class="slider_area slider_carousel owl-carousel">
    <div class="single_slider d-flex align-items-center" data-bgimg="<?= APP_URL ?>/assets/img/slider/slider1.jpg">
      <div class="container">
        <div class="row">
          <div class="col-12">
            <div class="slider_content">
              <h1>Запчасти <span>для любого автомобиля</span></h1>
              <p>Оригинальные и аналоговые запчасти от ведущих мировых производителей. Более 50 000 позиций в наличии.</p>
              <a class="button" href="<?= APP_URL ?>/catalog/index.php">В каталог <i class="fa fa-angle-double-right"></i></a>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="single_slider d-flex align-items-center" data-bgimg="<?= APP_URL ?>/assets/img/slider/slider2.jpg">
      <div class="container">
        <div class="row">
          <div class="col-12">
            <div class="slider_content center">
              <h1>Быстрая доставка <span>по Москве за 24 часа</span></h1>
              <p>Гарантия качества на все товары. Подбор по номеру детали или VIN-коду автомобиля.</p>
              <a class="button" href="<?= APP_URL ?>/catalog/index.php">Смотреть каталог <i class="fa fa-angle-double-right"></i></a>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="single_slider d-flex align-items-center" data-bgimg="<?= APP_URL ?>/assets/img/slider/slider3.jpg">
      <div class="container">
        <div class="row">
          <div class="col-12">
            <div class="slider_content">
              <h1>Качественные <span>детали двигателя</span></h1>
              <p>Наличие товара на московском складе. Самовывоз или экспресс-доставка по всей России.</p>
              <a class="button" href="<?= APP_URL ?>/catalog/index.php">Найти запчасть <i class="fa fa-angle-double-right"></i></a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- slider area end -->

<!-- service area start -->
<div class="service_area bg_gray section_padding_50">
  <div class="container">
    <div class="row">
      <div class="col-lg-3 col-md-6 col-sm-6">
        <div class="single_service d-flex align-items-center">
          <div class="service_icon mr-15">
            <img src="<?= APP_URL ?>/assets/img/about/shipping1.png" alt="">
          </div>
          <div class="service_content">
            <h4>Бесплатная доставка</h4>
            <p>При заказе от 5 000 ₽</p>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6 col-sm-6">
        <div class="single_service d-flex align-items-center">
          <div class="service_icon mr-15">
            <img src="<?= APP_URL ?>/assets/img/about/shipping2.png" alt="">
          </div>
          <div class="service_content">
            <h4>Возврат 14 дней</h4>
            <p>Без лишних вопросов</p>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6 col-sm-6">
        <div class="single_service d-flex align-items-center">
          <div class="service_icon mr-15">
            <img src="<?= APP_URL ?>/assets/img/about/shipping3.png" alt="">
          </div>
          <div class="service_content">
            <h4>Безопасная оплата</h4>
            <p>100% защита транзакций</p>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6 col-sm-6">
        <div class="single_service d-flex align-items-center">
          <div class="service_icon mr-15">
            <img src="<?= APP_URL ?>/assets/img/about/shipping4.png" alt="">
          </div>
          <div class="service_content">
            <h4>Поддержка 24/7</h4>
            <p>Звоните в любое время</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- service area end -->

<!-- category area start -->
<div class="product_category_area section_padding_100">
  <div class="container">
    <div class="row">
      <div class="col-12">
        <div class="section_title">
          <h2>Категории запчастей</h2>
          <p>Выберите категорию и найдите нужную деталь</p>
        </div>
      </div>
    </div>
    <div class="row">
      <?php
      $catImages = [
        'dvigatel'          => 's-product/category1.jpg',
        'tormoznaya-sistema'=> 's-product/category2.jpg',
        'podveska'          => 's-product/category3.jpg',
        'elektrika'         => 's-product/category4.jpg',
        'kuzov'             => 's-product/category5.jpg',
        'transmissiya'      => 's-product/category6.jpg',
      ];
      foreach ($featCats as $cat):
        $img = $catImages[$cat['slug']] ?? 's-product/category1.jpg';
      ?>
      <div class="col-lg-2 col-md-4 col-sm-4 col-6">
        <div class="single_category">
          <div class="category_img">
            <a href="<?= APP_URL ?>/catalog/index.php?category=<?= sanitize($cat['slug']) ?>">
              <img src="<?= APP_URL ?>/assets/img/<?= $img ?>" alt="<?= sanitize($cat['name']) ?>">
            </a>
          </div>
          <div class="category_content">
            <h6><a href="<?= APP_URL ?>/catalog/index.php?category=<?= sanitize($cat['slug']) ?>"><?= sanitize($cat['name']) ?></a></h6>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<!-- category area end -->

<!-- new arrivals -->
<div class="product_area section_padding_100 bg_gray">
  <div class="container">
    <div class="row">
      <div class="col-12">
        <div class="section_title">
          <h2>Новые поступления</h2>
          <p>Свежие запчасти на складе</p>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-12">
        <div class="product_nav_tab">
          <ul class="nav" id="myTab" role="tablist">
            <li class="nav-item"><a class="nav-link active" href="#new_arrivals">Новинки</a></li>
            <li class="nav-item"><a class="nav-link" href="#popular">Популярные</a></li>
          </ul>
        </div>
      </div>
    </div>
    <div class="tab-content" id="myTabContent">
      <!-- New arrivals tab -->
      <div class="tab-pane fade show active" id="new_arrivals">
        <div class="row product_slick owl-carousel">
          <?php foreach ($newParts as $part):
            $stock = getStockStatus((int)$part['stock']);
          ?>
          <div class="col">
            <div class="az-part-card">
              <div class="az-part-card-img">
                <div class="az-part-card-img-placeholder">
                  <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1"><rect x="2" y="7" width="20" height="10" rx="1"/><path d="M8 7V5M16 7V5"/></svg>
                </div>
                <span class="az-part-number-badge"><?= sanitize($part['part_number']) ?></span>
              </div>
              <div class="az-part-card-body">
                <div class="az-part-card-brand"><?= sanitize($part['brand_name']) ?></div>
                <div class="az-part-card-name">
                  <a href="<?= APP_URL ?>/catalog/part.php?id=<?= $part['id'] ?>"><?= sanitize(truncate($part['name'],55)) ?></a>
                </div>
                <div class="az-part-card-price">
                  <?= formatPriceInCurrency((float)$part['price']) ?>
                  <span class="az-badge az-badge-<?= $stock['class'] ?>" style="font-size:11px;margin-left:6px;"><?= $stock['label'] ?></span>
                </div>
              </div>
              <div class="az-part-card-footer">
                <?php if (isLoggedIn()): ?>
                <button class="az-btn az-btn-primary az-btn-sm az-btn-block" data-add-cart="<?= $part['id'] ?>">В корзину</button>
                <?php else: ?>
                <a href="<?= APP_URL ?>/auth/login.php" class="az-btn az-btn-outline az-btn-sm az-btn-block">Войдите для заказа</a>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <!-- Popular tab -->
      <div class="tab-pane fade" id="popular">
        <div class="row product_slick owl-carousel">
          <?php foreach ($popularParts as $part):
            $stock = getStockStatus((int)$part['stock']);
          ?>
          <div class="col">
            <div class="az-part-card">
              <div class="az-part-card-img">
                <div class="az-part-card-img-placeholder">
                  <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1"><rect x="2" y="7" width="20" height="10" rx="1"/></svg>
                </div>
                <span class="az-part-number-badge"><?= sanitize($part['part_number']) ?></span>
              </div>
              <div class="az-part-card-body">
                <div class="az-part-card-brand"><?= sanitize($part['brand_name']) ?></div>
                <div class="az-part-card-name">
                  <a href="<?= APP_URL ?>/catalog/part.php?id=<?= $part['id'] ?>"><?= sanitize(truncate($part['name'],55)) ?></a>
                </div>
                <div class="az-part-card-price"><?= formatPriceInCurrency((float)$part['price']) ?></div>
              </div>
              <div class="az-part-card-footer">
                <?php if (isLoggedIn()): ?>
                <button class="az-btn az-btn-primary az-btn-sm az-btn-block" data-add-cart="<?= $part['id'] ?>">В корзину</button>
                <?php else: ?>
                <a href="<?= APP_URL ?>/auth/login.php" class="az-btn az-btn-outline az-btn-sm az-btn-block">Войти</a>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="row mt-30">
      <div class="col-12 text-center">
        <a href="<?= APP_URL ?>/catalog/index.php" class="btn btn-primary">Смотреть весь каталог</a>
      </div>
    </div>
  </div>
</div>
<!-- new arrivals end -->

<!-- banner area -->
<div class="banner_area section_padding_50">
  <div class="container">
    <div class="row">
      <div class="col-lg-4 col-md-6">
        <div class="single_banner">
          <img src="<?= APP_URL ?>/assets/img/bg/banner1.jpg" alt="Двигатель">
          <div class="banner_content">
            <h5>Детали</h5>
            <h2>ДВИГАТЕЛЯ</h2>
            <a href="<?= APP_URL ?>/catalog/index.php?category=dvigatel">Купить →</a>
          </div>
        </div>
      </div>
      <div class="col-lg-4 col-md-6">
        <div class="single_banner">
          <img src="<?= APP_URL ?>/assets/img/bg/banner2.jpg" alt="Тормоза">
          <div class="banner_content">
            <h5>Тормозная</h5>
            <h2>СИСТЕМА</h2>
            <a href="<?= APP_URL ?>/catalog/index.php?category=tormoznaya-sistema">Купить →</a>
          </div>
        </div>
      </div>
      <div class="col-lg-4 col-md-6">
        <div class="single_banner">
          <img src="<?= APP_URL ?>/assets/img/bg/banner3.jpg" alt="Подвеска">
          <div class="banner_content">
            <h5>Детали</h5>
            <h2>ПОДВЕСКИ</h2>
            <a href="<?= APP_URL ?>/catalog/index.php?category=podveska">Купить →</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- banner area end -->

<!-- brand area -->
<div class="brand_area section_padding_50 bg_gray">
  <div class="container">
    <div class="row">
      <div class="col-12">
        <div class="section_title">
          <h2>Бренды-производители</h2>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-12">
        <div class="brand_active owl-carousel">
          <?php foreach ($featBrands as $b): ?>
          <div class="single_brand">
            <a href="<?= APP_URL ?>/catalog/index.php?brand=<?= (int)$b['id'] ?>">
              <?php if ($b['logo_path']): ?>
              <img src="<?= APP_URL . '/' . sanitize($b['logo_path']) ?>" alt="<?= sanitize($b['name']) ?>">
              <?php else: ?>
              <div style="padding:14px;text-align:center;font-weight:700;font-size:16px;color:#666;">
                <?= sanitize($b['name']) ?>
              </div>
              <?php endif; ?>
            </a>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- brand area end -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>
