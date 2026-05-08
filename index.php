<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Главная — Профессиональные автозапчасти';
$db = getDB();

$allCats  = getCategories();
$rootCats = array_values(array_filter($allCats, fn($c) => $c['parent_id'] === null));

$featBrands = $db->query("SELECT * FROM brands WHERE is_active=1 ORDER BY name LIMIT 16")->fetchAll();

$newParts = $db->query(
    "SELECT p.*, b.name AS brand_name FROM parts p
     LEFT JOIN brands b ON b.id=p.brand_id
     WHERE p.is_active=1 ORDER BY p.created_at DESC LIMIT 10"
)->fetchAll();

$popularParts = $db->query(
    "SELECT p.*, b.name AS brand_name FROM parts p
     LEFT JOIN brands b ON b.id=p.brand_id
     WHERE p.is_active=1 AND p.stock>0 ORDER BY p.price DESC LIMIT 10"
)->fetchAll();

$featuredParts = $db->query(
    "SELECT p.*, b.name AS brand_name FROM parts p
     LEFT JOIN brands b ON b.id=p.brand_id
     WHERE p.is_active=1 ORDER BY p.id ASC LIMIT 10"
)->fetchAll();

require_once __DIR__ . '/includes/header.php';

function renderProductPairs(array $parts, string $appUrl, int $startImg = 1): void {
    $chunks = array_chunk($parts, 2);
    $i = $startImg;
    foreach ($chunks as $pair) {
        echo '<div class="col-lg-3"><div class="product_items">';
        foreach ($pair as $p) {
            $img1 = (($i - 1) % 12) + 1;
            $img2 = ($i % 12) + 1;
            $i++;
            $url   = $appUrl . '/catalog/part.php?id=' . (int)$p['id'];
            $name  = sanitize(truncate($p['name'], 55));
            $brand = sanitize($p['brand_name'] ?? '');
            $price = formatPriceInCurrency((float)$p['price']);
            $cartBtn = isLoggedIn()
                ? '<li class="add_to_cart"><a href="#" data-add-cart="'.(int)$p['id'].'" title="В корзину">В корзину</a></li>'
                : '<li class="add_to_cart"><a href="'.$appUrl.'/auth/login.php">Войти</a></li>';
            echo <<<HTML
<article class="single_product">
  <figure>
    <div class="product_thumb">
      <a class="primary_img" href="{$url}"><img src="{$appUrl}/assets/img/product/product{$img1}.jpg" alt="{$name}"></a>
      <a class="secondary_img" href="{$url}"><img src="{$appUrl}/assets/img/product/product{$img2}.jpg" alt="{$name}"></a>
      <div class="label_product"><span class="label_new">В наличии</span></div>
      <div class="quick_button"><a href="{$url}" title="Подробнее"><i class="icon-eye"></i></a></div>
    </div>
    <div class="product_content">
      <div class="product_content_inner">
        <p class="manufacture_product"><a href="#">{$brand}</a></p>
        <h4 class="product_name"><a href="{$url}">{$name}</a></h4>
        <div class="product_rating"><ul>
          <li><a href="#"><i class="ion-android-star-outline"></i></a></li>
          <li><a href="#"><i class="ion-android-star-outline"></i></a></li>
          <li><a href="#"><i class="ion-android-star-outline"></i></a></li>
          <li><a href="#"><i class="ion-android-star-outline"></i></a></li>
          <li><a href="#"><i class="ion-android-star-outline"></i></a></li>
        </ul></div>
        <div class="price_box"><span class="current_price">{$price}</span></div>
      </div>
      <div class="action_links"><ul>{$cartBtn}</ul></div>
    </div>
  </figure>
</article>
HTML;
        }
        echo '</div></div>';
    }
}
?>

<!--top tags area start-->
<div class="top_tags_area">
  <div class="container">
    <div class="row"><div class="col-12">
      <div class="tags_content">
        <ul>
          <li><span>Категории:</span></li>
          <?php foreach ($rootCats as $c): ?>
          <li><a href="<?= APP_URL ?>/catalog/index.php?category=<?= sanitize($c['slug']) ?>"><?= sanitize($c['name']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div></div>
  </div>
</div>
<!--top tags area end-->

<!--slider area start-->
<section class="slider_section mb-80">
  <div class="slider_area slider_carousel owl-carousel">
    <div class="single_slider d-flex align-items-center" data-bgimg="<?= APP_URL ?>/assets/img/slider/slider1.jpg">
      <div class="container"><div class="row"><div class="col-12">
        <div class="slider_content">
          <h1>Большая распродажа <span>автозапчастей</span></h1>
          <p>Оригинальные и аналоговые запчасти от ведущих мировых производителей. Более 50 000 позиций в наличии.</p>
          <a class="button" href="<?= APP_URL ?>/catalog/index.php">В каталог <i class="fa fa-angle-double-right"></i></a>
        </div>
      </div></div></div>
    </div>
    <div class="single_slider d-flex align-items-center" data-bgimg="<?= APP_URL ?>/assets/img/slider/slider2.jpg">
      <div class="container"><div class="row"><div class="col-12">
        <div class="slider_content center">
          <h1>Запчасти <span>для любого автомобиля</span></h1>
          <p>Быстрая доставка по всей России. Подбор по номеру детали или VIN-коду.</p>
          <a class="button" href="<?= APP_URL ?>/catalog/index.php">Смотреть каталог <i class="fa fa-angle-double-right"></i></a>
        </div>
      </div></div></div>
    </div>
    <div class="single_slider d-flex align-items-center" data-bgimg="<?= APP_URL ?>/assets/img/slider/slider3.jpg">
      <div class="container"><div class="row"><div class="col-12">
        <div class="slider_content">
          <h1>Качественные <span>детали двигателя</span></h1>
          <p>Наличие товара на московском складе. Самовывоз или экспресс-доставка.</p>
          <a class="button" href="<?= APP_URL ?>/catalog/index.php">Найти запчасть <i class="fa fa-angle-double-right"></i></a>
        </div>
      </div></div></div>
    </div>
  </div>
</section>
<!--slider area end-->

<!--banner area start-->
<div class="banner_area mb-80">
  <div class="container">
    <div class="row">
      <div class="col-12">
        <div class="welcome_title">
          <h3>ДОБРО ПОЖАЛОВАТЬ В <?= strtoupper(sanitize(getSetting('site_name','АВТОЗАПЧАСТЬ'))) ?></h3>
          <h2>ВАШИ <span>ЗАПЧАСТИ ОНЛАЙН</span></h2>
          <p>Широкий ассортимент. Быстрая доставка. Гарантия качества.</p>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-4 col-md-4">
        <figure class="single_banner"><div class="banner_thumb">
          <a href="<?= APP_URL ?>/catalog/index.php"><img src="<?= APP_URL ?>/assets/img/bg/banner1.jpg" alt="Двигатель"></a>
        </div></figure>
      </div>
      <div class="col-lg-4 col-md-4">
        <figure class="single_banner"><div class="banner_thumb">
          <a href="<?= APP_URL ?>/catalog/index.php"><img src="<?= APP_URL ?>/assets/img/bg/banner2.jpg" alt="Тормоза"></a>
        </div></figure>
      </div>
      <div class="col-lg-4 col-md-4">
        <figure class="single_banner"><div class="banner_thumb">
          <a href="<?= APP_URL ?>/catalog/index.php"><img src="<?= APP_URL ?>/assets/img/bg/banner3.jpg" alt="Подвеска"></a>
        </div></figure>
      </div>
    </div>
  </div>
</div>
<!--banner area end-->

<!--Categories product area start-->
<div class="categories_product_area mb-80">
  <div class="container">
    <div class="row"><div class="col-12">
      <div class="categories_product_inner categories_column7 owl-carousel">
        <?php
        $catImgFiles = ['category1.jpg','category2.jpg','category3.jpg','category4.jpg','category5.jpg','category6.jpg','category7.jpg'];
        $ci = 0;
        foreach ($rootCats as $cat):
          $cimg = $catImgFiles[$ci++ % 7];
        ?>
        <div class="single_categories_product">
          <div class="categories_product_thumb">
            <a href="<?= APP_URL ?>/catalog/index.php?category=<?= sanitize($cat['slug']) ?>">
              <img src="<?= APP_URL ?>/assets/img/s-product/<?= $cimg ?>" alt="<?= sanitize($cat['name']) ?>">
            </a>
          </div>
          <div class="categories_product_content">
            <h4><a href="<?= APP_URL ?>/catalog/index.php?category=<?= sanitize($cat['slug']) ?>"><?= sanitize($cat['name']) ?></a></h4>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div></div>
  </div>
</div>
<!--Categories product area end-->

<!--home section bg area start-->
<div class="home_section_bg">

  <!--product area start-->
  <div class="product_area">
    <div class="container">
      <div class="row"><div class="col-12">
        <div class="section_title">
          <h2><span>Наши</span> товары</h2>
          <p>Качественные запчасти для вашего автомобиля.</p>
        </div>
        <div class="product_tab_btn">
          <ul class="nav" role="tablist" id="nav-tab">
            <li><a class="active" data-bs-toggle="tab" href="#Sellers" role="tab">Лидеры продаж</a></li>
            <li><a data-bs-toggle="tab" href="#Featured" role="tab">Рекомендуемые</a></li>
            <li><a data-bs-toggle="tab" href="#Arrivals" role="tab">Новинки</a></li>
          </ul>
        </div>
      </div></div>

      <div class="tab-content">
        <div class="tab-pane fade show active" id="Sellers" role="tabpanel">
          <div class="row">
            <div class="product_carousel product_column5 owl-carousel">
              <?php renderProductPairs($popularParts, APP_URL, 1); ?>
            </div>
          </div>
        </div>
        <div class="tab-pane fade" id="Featured" role="tabpanel">
          <div class="row">
            <div class="product_carousel product_column5 owl-carousel">
              <?php renderProductPairs($featuredParts, APP_URL, 5); ?>
            </div>
          </div>
        </div>
        <div class="tab-pane fade" id="Arrivals" role="tabpanel">
          <div class="row">
            <div class="product_carousel product_column5 owl-carousel">
              <?php renderProductPairs($newParts, APP_URL, 9); ?>
            </div>
          </div>
        </div>
      </div>

      <div class="row mt-30"><div class="col-12 text-center">
        <a href="<?= APP_URL ?>/catalog/index.php" class="btn btn-primary px-30">Смотреть весь каталог</a>
      </div></div>
    </div>
  </div>
  <!--product area end-->

  <!--blog area start-->
  <div class="blog_area">
    <div class="container">
      <div class="row"><div class="col-12">
        <div class="section_title">
          <h2><span>Полезные</span> статьи</h2>
          <p>Советы по обслуживанию и ремонту автомобиля.</p>
        </div>
      </div></div>
      <div class="blog_carousel owl-carousel">
        <?php
        $blogs = [
          ['img'=>'blog1.jpg','title'=>'Как выбрать тормозные диски: полное руководство','tag'=>'Советы','date'=>'Март 2025'],
          ['img'=>'blog2.jpg','title'=>'Замена масла своими руками: советы специалиста','tag'=>'Советы','date'=>'Февраль 2025'],
          ['img'=>'blog3.jpg','title'=>'Признаки износа ремня ГРМ и когда его менять','tag'=>'Советы','date'=>'Январь 2025'],
          ['img'=>'blog4.jpg','title'=>'Как выбрать тормозные колодки: полное руководство','tag'=>'Советы','date'=>'Декабрь 2024'],
        ];
        foreach ($blogs as $bl):
        ?>
        <article class="single_blog">
          <figure>
            <div class="blog_thumb"><a href="#"><img src="<?= APP_URL ?>/assets/img/blog/<?= $bl['img'] ?>" alt="<?= $bl['title'] ?>"></a></div>
            <figcaption class="blog_content">
              <h4><a href="#"><?= $bl['title'] ?></a></h4>
              <div class="post_meta"><p><a href="#"><?= $bl['tag'] ?></a> / <?= $bl['date'] ?></p></div>
              <div class="post_desc"><p>Читайте наши советы по обслуживанию и ремонту автомобиля...</p></div>
              <footer class="post_readmore"><a href="#">Читать далее</a></footer>
            </figcaption>
          </figure>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <!--blog area end-->

</div>
<!--home section bg area end-->

<!--brand area start-->
<div class="brand_area">
  <div class="container">
    <div class="col-12">
      <div class="brand_container owl-carousel">
        <?php
        $bImgNums = range(1,8);
        $bi = 0;
        if (!empty($featBrands)):
          $pairs = array_chunk($featBrands, 2);
          foreach ($pairs as $pair):
        ?>
        <div class="brand_list">
          <?php foreach ($pair as $b):
            $bSrc = $b['logo_path'] ? APP_URL.'/'.sanitize($b['logo_path'])
                                    : APP_URL.'/assets/img/brand/brand'.($bImgNums[$bi++%8]).'.jpg';
          ?>
          <div class="single_brand">
            <a href="<?= APP_URL ?>/catalog/index.php?brand=<?= (int)$b['id'] ?>">
              <img src="<?= $bSrc ?>" alt="<?= sanitize($b['name']) ?>">
            </a>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endforeach; else: ?>
        <?php for ($n=1;$n<=8;$n+=2): ?>
        <div class="brand_list">
          <div class="single_brand"><a href="<?= APP_URL ?>/catalog/index.php"><img src="<?= APP_URL ?>/assets/img/brand/brand<?= $n ?>.jpg" alt=""></a></div>
          <div class="single_brand"><a href="<?= APP_URL ?>/catalog/index.php"><img src="<?= APP_URL ?>/assets/img/brand/brand<?= $n+1 ?>.jpg" alt=""></a></div>
        </div>
        <?php endfor; endif; ?>
      </div>
    </div>
  </div>
</div>
<!--brand area end-->

<?php require_once __DIR__ . '/includes/footer.php'; ?>
