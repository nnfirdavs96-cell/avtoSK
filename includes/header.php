<?php
$cartCount   = isLoggedIn() ? getCartCount() : 0;
$currentUser = getCurrentUser();
$flash       = getFlashMessage();
$csrfToken   = generateCsrfToken();
$allCategories = getCategories();
$catTree       = getCategoryTree($allCategories);
$exchangeRates = getExchangeRates();
$activeCurrency = getCurrentCurrency();
$siteName    = getSetting('site_name', 'АвтоЗапчасть');
$sitePhone   = getSetting('site_phone', '+7 (800) 555-35-35');
$siteEmail   = getSetting('site_email', 'info@avtozapchast.ru');
$isAdmin     = hasRole(['admin','manager','superadmin']);

/* Mini-cart items (only if logged in) */
$miniCartItems = [];
$miniCartTotal = 0;
if (isLoggedIn()) {
    try {
        $db = getDB();
        $mcStmt = $db->prepare(
            "SELECT c.part_id, c.quantity, p.name, p.price, p.part_number
             FROM cart c JOIN parts p ON p.id=c.part_id
             WHERE c.user_id=? AND p.is_active=1 ORDER BY c.added_at DESC LIMIT 5"
        );
        $mcStmt->execute([$_SESSION['user_id']]);
        $miniCartItems = $mcStmt->fetchAll();
        $miniCartTotal = array_sum(array_map(fn($i)=>$i['price']*$i['quantity'], $miniCartItems));
    } catch (Exception $e) {}
}
?>
<!doctype html>
<html class="no-js" lang="ru">
<head>
  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' — ' : '' ?><?= sanitize($siteName) ?></title>
  <meta name="description" content="Профессиональный подбор и продажа автозапчастей">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="shortcut icon" type="image/x-icon" href="<?= APP_URL ?>/assets/img/favicon.ico">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/plugins.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>

<!--offcanvas menu area start-->
<div class="off_canvars_overlay"></div>
<div class="offcanvas_menu">
  <div class="container">
    <div class="row">
      <div class="col-12">
        <div class="canvas_open">
          <a href="javascript:void(0)"><i class="ion-navicon"></i></a>
        </div>
        <div class="offcanvas_menu_wrapper">
          <div class="canvas_close">
            <a href="javascript:void(0)"><i class="ion-android-close"></i></a>
          </div>
          <div class="call_support">
            <p><i class="icon-phone-call"></i> <span>Звоните: <a href="tel:<?= sanitize(preg_replace('/[^+0-9]/','',$sitePhone)) ?>"><?= sanitize($sitePhone) ?></a></span></p>
          </div>
          <div class="header_account">
            <ul>
              <?php if (count($exchangeRates) > 1): ?>
              <li class="currency">
                <a href="#"><?= sanitize($activeCurrency) ?> <?= sanitize($exchangeRates[$activeCurrency]['symbol'] ?? '') ?> <i class="ion-chevron-down"></i></a>
                <ul class="dropdown_currency">
                  <?php foreach ($exchangeRates as $code => $r): ?>
                  <li><a href="<?= APP_URL ?>/api/currency.php?set=<?= urlencode($code) ?>&back=<?= urlencode($_SERVER['REQUEST_URI']) ?>"><?= sanitize($code) ?> — <?= sanitize($r['name']) ?></a></li>
                  <?php endforeach; ?>
                </ul>
              </li>
              <?php endif; ?>
            </ul>
          </div>
          <div class="header_top_links">
            <ul>
              <?php if (isLoggedIn()): ?>
              <li><a href="<?= APP_URL ?>/buyer/index.php"><i class="ion-person"></i> <?= sanitize($currentUser['username']) ?></a></li>
              <li><a href="<?= APP_URL ?>/auth/logout.php">Выйти</a></li>
              <?php else: ?>
              <li><a href="<?= APP_URL ?>/auth/register.php">Регистрация</a></li>
              <li><a href="<?= APP_URL ?>/auth/login.php">Войти</a></li>
              <?php endif; ?>
              <li><a href="<?= APP_URL ?>/buyer/cart.php">Корзина<?= $cartCount > 0 ? " ($cartCount)" : '' ?></a></li>
            </ul>
          </div>
          <div class="search_container">
            <form action="<?= APP_URL ?>/search/index.php" method="get">
              <div class="hover_category">
                <select class="select_option" name="cat" id="mob_cat">
                  <option value="">Все категории</option>
                  <?php foreach ($allCategories as $c): if ($c['parent_id'] !== null) continue; ?>
                  <option value="<?= sanitize($c['slug']) ?>"><?= sanitize($c['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="search_box">
                <input placeholder="Поиск товара..." type="text" name="q" value="<?= isset($_GET['q']) ? sanitize($_GET['q']) : '' ?>">
                <button type="submit">Поиск</button>
              </div>
            </form>
          </div>
          <div id="menu" class="text-left">
            <ul class="offcanvas_main_menu">
              <li><a href="<?= APP_URL ?>/index.php">Главная</a></li>
              <li class="menu-item-has-children">
                <a href="<?= APP_URL ?>/catalog/index.php">Каталог</a>
                <ul class="sub-menu">
                  <?php foreach ($allCategories as $c): if ($c['parent_id'] !== null) continue; ?>
                  <li><a href="<?= APP_URL ?>/catalog/index.php?category=<?= sanitize($c['slug']) ?>"><?= sanitize($c['name']) ?></a></li>
                  <?php endforeach; ?>
                </ul>
              </li>
              <?php if (isLoggedIn()): ?>
              <li><a href="<?= APP_URL ?>/buyer/orders.php">Мои заказы</a></li>
              <?php endif; ?>
              <?php if ($isAdmin): ?>
              <li class="menu-item-has-children">
                <a href="#">Управление</a>
                <ul class="sub-menu">
                  <?php if (hasRole(['manager','superadmin'])): ?><li><a href="<?= APP_URL ?>/manager/index.php">Менеджер</a></li><?php endif; ?>
                  <?php if (hasRole(['admin','superadmin'])): ?><li><a href="<?= APP_URL ?>/admin/index.php">Администратор</a></li><?php endif; ?>
                  <?php if (hasRole('superadmin')): ?><li><a href="<?= APP_URL ?>/superadmin/index.php">Супер-Администратор</a></li><?php endif; ?>
                </ul>
              </li>
              <?php endif; ?>
            </ul>
          </div>
          <div class="offcanvas_footer">
            <span><a href="mailto:<?= sanitize($siteEmail) ?>"><i class="fa fa-envelope-o"></i> <?= sanitize($siteEmail) ?></a></span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!--offcanvas menu area end-->

<header>
  <div class="main_header">

    <!--header top start-->
    <div class="header_top">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-4 col-md-5">
            <div class="header_account">
              <ul>
                <?php if (count($exchangeRates) > 1): ?>
                <li class="currency">
                  <a href="#"><?= sanitize($activeCurrency) ?> <?= sanitize($exchangeRates[$activeCurrency]['symbol'] ?? '') ?> <i class="ion-chevron-down"></i></a>
                  <ul class="dropdown_currency">
                    <?php foreach ($exchangeRates as $code => $r): ?>
                    <li><a href="<?= APP_URL ?>/api/currency.php?set=<?= urlencode($code) ?>&back=<?= urlencode($_SERVER['REQUEST_URI']) ?>"><?= sanitize($code) ?> — <?= sanitize($r['name']) ?></a></li>
                    <?php endforeach; ?>
                  </ul>
                </li>
                <?php endif; ?>
              </ul>
            </div>
          </div>
          <div class="col-lg-8 col-md-7">
            <div class="header_top_links text-right">
              <ul>
                <?php if (isLoggedIn()): ?>
                <li><a href="<?= APP_URL ?>/buyer/index.php"><i class="ion-person"></i> <?= sanitize($currentUser['username']) ?></a></li>
                <li><a href="<?= APP_URL ?>/auth/logout.php">Выйти</a></li>
                <?php else: ?>
                <li><a href="<?= APP_URL ?>/auth/register.php">Регистрация</a></li>
                <li><a href="<?= APP_URL ?>/auth/login.php">Войти</a></li>
                <?php endif; ?>
                <li><a href="<?= APP_URL ?>/buyer/cart.php">Корзина</a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!--header top end-->

    <!--header middle start-->
    <div class="header_middle">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-2 col-md-4 col-sm-4 col-4">
            <div class="logo">
              <a href="<?= APP_URL ?>/index.php">
                <img src="<?= APP_URL ?>/assets/img/logo/logo.png" alt="<?= sanitize($siteName) ?>"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
                <span style="display:none;font-size:1.2rem;font-weight:900;color:#333;"><?= sanitize($siteName) ?></span>
              </a>
            </div>
          </div>
          <div class="col-lg-10 col-md-6 col-sm-6 col-6">
            <div class="header_right_box">
              <div class="search_container header-search" style="position:relative;">
                <form action="<?= APP_URL ?>/search/index.php" method="get" id="main-search-form">
                  <div class="hover_category">
                    <select class="select_option" name="cat" id="header_cat">
                      <option value="">Все категории</option>
                      <?php foreach ($allCategories as $c): if ($c['parent_id'] !== null) continue; ?>
                      <option value="<?= sanitize($c['slug']) ?>" <?= (isset($_GET['cat']) && $_GET['cat']===$c['slug'])?'selected':'' ?>><?= sanitize($c['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="search_box">
                    <input id="header-search" placeholder="Поиск товара или номера детали..." type="text" name="q"
                           value="<?= isset($_GET['q']) ? sanitize($_GET['q']) : '' ?>" autocomplete="off">
                    <button type="submit">Поиск</button>
                  </div>
                </form>
                <div class="search-dropdown-wrap" id="search-dropdown"></div>
              </div>
              <div class="header_configure_area">
                <div class="mini_cart_wrapper">
                  <a href="javascript:void(0)">
                    <i class="icon-shopping-bag2"></i>
                    <?php if ($miniCartTotal > 0): ?>
                    <span class="cart_price"><?= formatPriceInCurrency($miniCartTotal) ?> <i class="ion-ios-arrow-down"></i></span>
                    <?php endif; ?>
                    <span class="cart_count" id="cart-badge"><?= $cartCount ?></span>
                  </a>
                  <!--mini cart-->
                  <div class="mini_cart">
                    <div class="mini_cart_inner">
                      <div class="cart_close">
                        <div class="cart_text"><h3>Корзина</h3></div>
                        <div class="mini_cart_close"><a href="javascript:void(0)"><i class="icon-x"></i></a></div>
                      </div>
                      <?php if (empty($miniCartItems)): ?>
                      <div style="padding:20px;text-align:center;color:#999;font-size:13px;">Корзина пуста</div>
                      <?php else: ?>
                      <?php foreach ($miniCartItems as $mc): ?>
                      <div class="cart_item">
                        <div class="cart_img">
                          <a href="<?= APP_URL ?>/catalog/part.php?id=<?= (int)$mc['part_id'] ?>">
                            <img src="<?= APP_URL ?>/assets/img/s-product/product.jpg" alt="<?= sanitize($mc['name']) ?>">
                          </a>
                        </div>
                        <div class="cart_info">
                          <a href="<?= APP_URL ?>/catalog/part.php?id=<?= (int)$mc['part_id'] ?>"><?= sanitize(truncate($mc['name'],40)) ?></a>
                          <p>Кол-во: <?= (int)$mc['quantity'] ?> × <span><?= formatPriceInCurrency((float)$mc['price']) ?></span></p>
                        </div>
                        <div class="cart_remove">
                          <a href="#" data-cart-remove="<?= (int)$mc['part_id'] ?>"><i class="ion-android-close"></i></a>
                        </div>
                      </div>
                      <?php endforeach; ?>
                      <div class="mini_cart_table">
                        <div class="cart_total">
                          <span>Подытог:</span>
                          <span class="price"><?= formatPriceInCurrency($miniCartTotal) ?></span>
                        </div>
                        <div class="cart_total mt-10">
                          <span>Итого:</span>
                          <span class="price"><?= formatPriceInCurrency($miniCartTotal) ?></span>
                        </div>
                      </div>
                      <?php endif; ?>
                    </div>
                    <div class="mini_cart_footer">
                      <div class="cart_button">
                        <a href="<?= APP_URL ?>/buyer/cart.php">Перейти в корзину</a>
                      </div>
                      <?php if (!empty($miniCartItems)): ?>
                      <div class="cart_button">
                        <a class="active" href="<?= APP_URL ?>/buyer/cart.php">Оформить заказ</a>
                      </div>
                      <?php endif; ?>
                    </div>
                  </div>
                  <!--mini cart end-->
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!--header middle end-->

    <!--header bottom start-->
    <div class="header_bottom sticky-header">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-3">
            <div class="categories_menu">
              <div class="categories_title">
                <h2 class="categori_toggle">ВСЕ КАТЕГОРИИ</h2>
              </div>
              <div class="categories_menu_toggle">
                <ul>
                  <?php foreach ($catTree as $cat): ?>
                  <?php if (!empty($cat['children'])): ?>
                  <li class="menu_item_children">
                    <a href="<?= APP_URL ?>/catalog/index.php?category=<?= sanitize($cat['slug']) ?>"><?= sanitize($cat['name']) ?> <i class="fa fa-angle-right"></i></a>
                    <ul class="categories_mega_menu">
                      <?php foreach ($cat['children'] as $child): ?>
                      <li><a href="<?= APP_URL ?>/catalog/index.php?category=<?= sanitize($child['slug']) ?>"><?= sanitize($child['name']) ?></a></li>
                      <?php endforeach; ?>
                    </ul>
                  </li>
                  <?php else: ?>
                  <li><a href="<?= APP_URL ?>/catalog/index.php?category=<?= sanitize($cat['slug']) ?>"><?= sanitize($cat['name']) ?></a></li>
                  <?php endif; ?>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="main_menu menu_position text-left">
              <nav>
                <ul>
                  <li><a href="<?= APP_URL ?>/index.php">ГЛАВНАЯ</a></li>
                  <li class="mega_items">
                    <a href="<?= APP_URL ?>/catalog/index.php">КАТАЛОГ <i class="fa fa-angle-down"></i></a>
                    <div class="mega_menu">
                      <ul class="mega_menu_inner">
                        <?php foreach ($catTree as $cat): ?>
                        <li>
                          <a href="<?= APP_URL ?>/catalog/index.php?category=<?= sanitize($cat['slug']) ?>"><?= sanitize($cat['name']) ?></a>
                          <?php if (!empty($cat['children'])): ?>
                          <ul>
                            <?php foreach ($cat['children'] as $ch): ?>
                            <li><a href="<?= APP_URL ?>/catalog/index.php?category=<?= sanitize($ch['slug']) ?>"><?= sanitize($ch['name']) ?></a></li>
                            <?php endforeach; ?>
                          </ul>
                          <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                      </ul>
                    </div>
                  </li>
                  <?php if (isLoggedIn()): ?>
                  <li><a href="<?= APP_URL ?>/buyer/orders.php">МОИ ЗАКАЗЫ</a></li>
                  <?php endif; ?>
                  <?php if ($isAdmin): ?>
                  <li><a href="#">УПРАВЛЕНИЕ <i class="fa fa-angle-down"></i></a>
                    <ul class="sub_menu">
                      <?php if (hasRole(['manager','superadmin'])): ?><li><a href="<?= APP_URL ?>/manager/index.php">Менеджер</a></li><?php endif; ?>
                      <?php if (hasRole(['admin','superadmin'])): ?><li><a href="<?= APP_URL ?>/admin/index.php">Администратор</a></li><?php endif; ?>
                      <?php if (hasRole('superadmin')): ?><li><a href="<?= APP_URL ?>/superadmin/index.php">Супер-Администратор</a></li><?php endif; ?>
                    </ul>
                  </li>
                  <?php endif; ?>
                </ul>
              </nav>
            </div>
          </div>
          <div class="col-lg-3">
            <div class="call_support text-right">
              <p><i class="icon-phone-call"></i> <span>Звоните: <a href="tel:<?= sanitize(preg_replace('/[^+0-9]/','',$sitePhone)) ?>"><?= sanitize($sitePhone) ?></a></span></p>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!--header bottom end-->

  </div>
</header>
<!--header area end-->

<?php if ($flash): ?>
<div class="container az-flash" id="flash-container" style="margin-top:10px;">
  <div class="az-alert az-alert-<?= sanitize($flash['type']) ?>">
    <?= sanitize($flash['message']) ?>
    <button class="az-alert-close" onclick="this.parentElement.parentElement.remove()">×</button>
  </div>
</div>
<?php endif; ?>

<main id="main-content">
