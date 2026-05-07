</main><!-- /#main-content -->
<?php
$siteEmail   = getSetting('site_email',   'info@avtozapchast.ru');
$sitePhone   = getSetting('site_phone',   '+7 (800) 555-35-35');
$siteAddress = getSetting('site_address', 'г. Москва');
$workHours   = getSetting('working_hours','Пн–Пт: 9:00–20:00');
$tgLink      = getSetting('social_telegram','#');
$waLink      = getSetting('social_whatsapp','#');
?>
<footer class="footer_area">
  <div class="footer_top section_padding_100">
    <div class="container">
      <div class="row">
        <!-- About -->
        <div class="col-lg-3 col-md-6 col-sm-12">
          <div class="single_footer_widget">
            <div class="footer-logo mb-20">
              <a href="<?= APP_URL ?>/index.php">
                <img src="<?= APP_URL ?>/assets/img/logo/logo.png" alt="<?= sanitize(getSetting('site_name','АвтоЗапчасть')) ?>"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
                <span class="logo-text" style="display:none;font-size:1.4rem;font-weight:900;color:#fff;"><?= sanitize(getSetting('site_name','АвтоЗапчасть')) ?></span>
              </a>
            </div>
            <p>Профессиональный подбор и продажа автозапчастей. Оригинальные и аналоговые запчасти от ведущих мировых производителей.</p>
            <div class="footer_social mt-20">
              <a href="<?= sanitize($tgLink) ?>" title="Telegram"><i class="fa fa-telegram"></i></a>
              <a href="<?= sanitize($waLink) ?>" title="WhatsApp"><i class="fa fa-whatsapp"></i></a>
            </div>
          </div>
        </div>

        <!-- Catalog -->
        <div class="col-lg-2 col-md-6 col-sm-12">
          <div class="single_footer_widget">
            <h4>Каталог</h4>
            <ul class="footer_menu">
              <?php
              $cats = getCategories();
              foreach ($cats as $c):
                if ($c['parent_id'] !== null) continue;
              ?>
              <li><a href="<?= APP_URL ?>/catalog/index.php?category=<?= sanitize($c['slug']) ?>"><?= sanitize($c['name']) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>

        <!-- Info -->
        <div class="col-lg-2 col-md-6 col-sm-12">
          <div class="single_footer_widget">
            <h4>Информация</h4>
            <ul class="footer_menu">
              <li><a href="<?= APP_URL ?>/index.php">Главная</a></li>
              <li><a href="<?= APP_URL ?>/catalog/index.php">Все товары</a></li>
              <li><a href="<?= APP_URL ?>/auth/login.php">Войти</a></li>
              <li><a href="<?= APP_URL ?>/auth/register.php">Регистрация</a></li>
              <?php if (isLoggedIn()): ?>
              <li><a href="<?= APP_URL ?>/buyer/orders.php">Мои заказы</a></li>
              <?php endif; ?>
            </ul>
          </div>
        </div>

        <!-- Contacts -->
        <div class="col-lg-3 col-md-6 col-sm-12">
          <div class="single_footer_widget">
            <h4>Контакты</h4>
            <ul class="footer_contact">
              <li><i class="fa fa-phone"></i> <a href="tel:<?= sanitize(preg_replace('/[^+0-9]/','',$sitePhone)) ?>"><?= sanitize($sitePhone) ?></a></li>
              <li><i class="fa fa-envelope-o"></i> <a href="mailto:<?= sanitize($siteEmail) ?>"><?= sanitize($siteEmail) ?></a></li>
              <li><i class="fa fa-map-marker"></i> <?= sanitize($siteAddress) ?></li>
              <li><i class="fa fa-clock-o"></i> <?= sanitize($workHours) ?></li>
            </ul>
          </div>
        </div>

        <!-- Payment -->
        <div class="col-lg-2 col-md-6 col-sm-12">
          <div class="single_footer_widget">
            <h4>Оплата</h4>
            <img src="<?= APP_URL ?>/assets/img/icon/payment.png" alt="Способы оплаты" class="img-fluid mt-10">
          </div>
        </div>

      </div>
    </div>
  </div>

  <div class="footer_bottom">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-6 col-md-6">
          <p>&copy; <?= date('Y') ?> <?= sanitize(getSetting('site_name','АвтоЗапчасть')) ?>. Все права защищены.</p>
        </div>
        <div class="col-lg-6 col-md-6 text-right">
          <div class="footer_menu_bottom">
            <ul>
              <li><a href="<?= APP_URL ?>/catalog/index.php">Каталог</a></li>
              <li><a href="<?= APP_URL ?>/buyer/cart.php">Корзина</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</footer>

<!-- newsletter popup -->
<div class="newletter-popup" id="newsletter-popup">
  <div id="boxes" class="newletter-container">
    <div id="dialog" class="window">
      <div id="popup2">
        <span class="b-close" id="popup-close"><span>×</span></span>
      </div>
      <div class="box">
        <div class="newletter-title"><h2>Подписка на рассылку</h2></div>
        <div class="box-content newleter-content">
          <label class="newletter-label">Введите e-mail, чтобы получать акции и новости.</label>
          <div id="frm_subscribe">
            <form id="subscribe_popup">
              <input type="email" name="subscribe_pemail" placeholder="Введите ваш e-mail..." required>
              <div id="notification"></div>
              <button type="submit" class="theme-btn-outlined"><span>Подписаться</span></button>
            </form>
            <div class="subscribe-bottom">
              <input type="checkbox" id="newsletter_popup_dont_show_again">
              <label for="newsletter_popup_dont_show_again">Больше не показывать</label>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- newsletter popup end -->

<script src="<?= APP_URL ?>/assets/js/plugins.js"></script>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>
</html>
