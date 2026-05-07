<?php
$cartCount   = isLoggedIn() ? getCartCount() : 0;
$currentUser = getCurrentUser();
$flash       = getFlashMessage();
$csrfToken   = generateCsrfToken();
$allCategories = getCategories();
$exchangeRates = getExchangeRates();
$activeCurrency = getCurrentCurrency();
$siteName    = getSetting('site_name', 'АвтоЗапчасть');
$sitePhone   = getSetting('site_phone', '+7 (800) 555-35-35');
$isAdmin     = hasRole(['admin','manager','superadmin']);
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

<!-- offcanvas menu -->
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
                <a href="#"><?= sanitize($activeCurrency) ?> <i class="ion-chevron-down"></i></a>
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
              <li><a href="<?= APP_URL ?>/buyer/index.php">Личный кабинет</a></li>
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
              <?php if ($isAdmin): ?>
              <li><a href="<?= APP_URL ?>/admin/index.php">Панель управления</a></li>
              <?php endif; ?>
              <?php if (isLoggedIn()): ?>
              <li><a href="<?= APP_URL ?>/buyer/orders.php">Мои заказы</a></li>
              <?php endif; ?>
            </ul>
          </div>
          <div class="offcanvas_footer">
            <span><a href="mailto:<?= sanitize(getSetting('site_email','info@avtozapchast.ru')) ?>"><i class="fa fa-envelope-o"></i> <?= sanitize(getSetting('site_email','info@avtozapchast.ru')) ?></a></span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- offcanvas menu end -->

<header>
  <div class="main_header">

    <!-- header top -->
    <div class="header_top">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-4 col-md-5">
            <div class="header_account">
              <ul>
                <?php if (count($exchangeRates) > 1): ?>
                <li class="currency">
                  <a href="#">
                    <?= sanitize($activeCurrency) ?> <?= sanitize($exchangeRates[$activeCurrency]['symbol'] ?? '') ?>
                    <i class="ion-chevron-down"></i>
                  </a>
                  <ul class="dropdown_currency">
                    <?php foreach ($exchangeRates as $code => $r): ?>
                    <li>
                      <a href="<?= APP_URL ?>/api/currency.php?set=<?= urlencode($code) ?>&back=<?= urlencode($_SERVER['REQUEST_URI']) ?>">
                        <?= sanitize($code) ?> — <?= sanitize($r['name']) ?>
                      </a>
                    </li>
                    <?php endforeach; ?>
                  </ul>
                </li>
                <?php endif; ?>
              </ul>
            </div>
          </div>
          <div class="col-lg-4 col-md-4 d-none d-md-block text-center">
            <div class="call_support">
              <p><i class="icon-phone-call"></i> <a href="tel:<?= sanitize(preg_replace('/[^+0-9]/','',$sitePhone)) ?>"><?= sanitize($sitePhone) ?></a></p>
            </div>
          </div>
          <div class="col-lg-4 col-md-3">
            <div class="header_top_links">
              <ul>
                <?php if (isLoggedIn()): ?>
                <li><a href="<?= APP_URL ?>/buyer/index.php">
                  <i class="ion-person"></i> <?= sanitize($currentUser['username']) ?>
                </a></li>
                <li><a href="<?= APP_URL ?>/auth/logout.php">Выйти</a></li>
                <?php else: ?>
                <li><a href="<?= APP_URL ?>/auth/login.php">Войти</a></li>
                <li><a href="<?= APP_URL ?>/auth/register.php">Регистрация</a></li>
                <?php endif; ?>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- header top end -->

    <!-- header middle -->
    <div class="header_middle sticky_header sticky-header">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-2 col-md-3 col-sm-4 col-6">
            <div class="logo">
              <a href="<?= APP_URL ?>/index.php">
                <img src="<?= APP_URL ?>/assets/img/logo/logo.png" alt="<?= sanitize($siteName) ?>" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
                <span class="logo-text" style="display:none;font-size:1.3rem;font-weight:900;color:#333;letter-spacing:1px;"><?= sanitize($siteName) ?></span>
              </a>
            </div>
          </div>
          <div class="col-lg-7 col-md-6 d-none d-md-block">
            <div class="search_container header-search">
              <form action="<?= APP_URL ?>/search/index.php" method="get" id="main-search-form">
                <div class="hover_category">
                  <select class="select_option" name="cat" id="header_cat">
                    <option value="">Все категории</option>
                    <?php foreach ($allCategories as $c): if ($c['parent_id'] !== null) continue; ?>
                    <option value="<?= sanitize($c['slug']) ?>"
                      <?= (isset($_GET['cat']) && $_GET['cat'] === $c['slug']) ? 'selected' : '' ?>>
                      <?= sanitize($c['name']) ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="search_box">
                  <input id="header-search" placeholder="Номер детали или название..." type="text" name="q"
                         value="<?= isset($_GET['q']) ? sanitize($_GET['q']) : '' ?>"
                         autocomplete="off">
                  <button type="submit">Поиск</button>
                </div>
              </form>
              <div class="search-dropdown-wrap" id="search-dropdown"></div>
            </div>
          </div>
          <div class="col-lg-3 col-md-3 col-sm-8 col-6">
            <div class="header_right">
              <ul>
                <?php if (isLoggedIn()): ?>
                <li class="user_account">
                  <a href="<?= APP_URL ?>/buyer/index.php" title="Личный кабинет">
                    <i class="ion-person"></i>
                    <span class="header_account_label">Кабинет</span>
                  </a>
                </li>
                <?php endif; ?>
                <li class="cart_area">
                  <a href="<?= APP_URL ?>/buyer/cart.php" class="cart_icon">
                    <i class="ion-bag"></i>
                    <?php if ($cartCount > 0): ?>
                    <span class="cart_quantity" id="cart-badge"><?= $cartCount ?></span>
                    <?php endif; ?>
                  </a>
                </li>
                <li class="canvas_open d-md-none">
                  <a href="javascript:void(0)"><i class="ion-navicon"></i></a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- header middle end -->

    <!-- header navigation -->
    <div class="header_navigation">
      <div class="container">
        <div class="row">
          <div class="col-12">
            <nav>
              <ul class="main_menu">
                <li><a href="<?= APP_URL ?>/index.php">Главная</a></li>
                <li class="menu-item-has-children">
                  <a href="<?= APP_URL ?>/catalog/index.php">Каталог <i class="ion-chevron-down"></i></a>
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
                  <a href="#">Управление <i class="ion-chevron-down"></i></a>
                  <ul class="sub-menu">
                    <?php if (hasRole(['manager','superadmin'])): ?>
                    <li><a href="<?= APP_URL ?>/manager/index.php">Менеджер</a></li>
                    <?php endif; ?>
                    <?php if (hasRole(['admin','superadmin'])): ?>
                    <li><a href="<?= APP_URL ?>/admin/index.php">Администратор</a></li>
                    <?php endif; ?>
                    <?php if (hasRole('superadmin')): ?>
                    <li><a href="<?= APP_URL ?>/superadmin/index.php">Супер-Администратор</a></li>
                    <?php endif; ?>
                  </ul>
                </li>
                <?php endif; ?>
              </ul>
            </nav>
          </div>
        </div>
      </div>
    </div>
    <!-- header navigation end -->

  </div>
</header>
<!-- header area end -->

<?php if ($flash): ?>
<div class="container az-flash" id="flash-container">
  <div class="az-alert az-alert-<?= sanitize($flash['type']) ?>">
    <?= sanitize($flash['message']) ?>
    <button class="az-alert-close" onclick="this.parentElement.parentElement.remove()">×</button>
  </div>
</div>
<?php endif; ?>

<main id="main-content">
