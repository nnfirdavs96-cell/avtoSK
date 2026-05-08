</main>
<?php
$siteEmail   = getSetting('site_email',   'info@avtozapchast.ru');
$sitePhone   = getSetting('site_phone',   '+7 (800) 555-35-35');
$siteAddress = getSetting('site_address', 'г. Москва');
$workHours   = getSetting('working_hours','Пн–Пт: 9:00–20:00');
$siteName2   = getSetting('site_name',    'АвтоЗапчасть');
$tgLink      = getSetting('social_telegram','#');
$waLink      = getSetting('social_whatsapp','#');
?>

<!--newsletter area start-->
<div class="newsletter_area">
  <div class="container">
    <div class="newsletter_inner">
      <div class="row">
        <div class="col-lg-4 col-md-6">
          <div class="newsletter_container">
            <h3>Мы в соцсетях</h3>
            <p>Следите за нами в социальных сетях для получения акций и новостей.</p>
            <div class="footer_social">
              <ul>
                <li><a class="facebook" href="<?= sanitize($tgLink) ?>"><i class="icon-facebook"></i></a></li>
                <li><a class="twitter" href="#"><i class="icon-twitter2"></i></a></li>
                <li><a class="youtube" href="#"><i class="icon-youtube"></i></a></li>
                <li><a class="instagram2" href="<?= sanitize($waLink) ?>"><i class="icon-instagram2"></i></a></li>
              </ul>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="newsletter_container">
            <h3>Подписка на рассылку</h3>
            <p>Подпишитесь и получайте скидки и новости о новых поступлениях.</p>
            <div class="subscribe_form">
              <form id="mc-form" class="mc-form footer-newsletter">
                <input id="mc-email" type="email" autocomplete="off" placeholder="Введите ваш e-mail...">
                <button id="mc-submit">Подписаться</button>
              </form>
              <div class="mailchimp-alerts text-centre">
                <div class="mailchimp-submitting"></div>
                <div class="mailchimp-success"></div>
                <div class="mailchimp-error"></div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-md-7">
          <div class="newsletter_container col_3">
            <h3>СКАЧАТЬ ПРИЛОЖЕНИЕ</h3>
            <p>Приложение <?= sanitize($siteName2) ?> уже доступно в Google Play и App Store.</p>
            <div class="app_img">
              <ul>
                <li><a href="#"><img src="<?= APP_URL ?>/assets/img/icon/icon-app.jpg" alt="Google Play"></a></li>
                <li><a href="#"><img src="<?= APP_URL ?>/assets/img/icon/icon1-app.jpg" alt="App Store"></a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!--newsletter area end-->

<!--footer area start-->
<footer class="footer_widgets">
  <!--shipping area start-->
  <div class="shipping_area">
    <div class="container">
      <div class="shipping_inner">
        <div class="single_shipping">
          <div class="shipping_icone"><img src="<?= APP_URL ?>/assets/img/about/shipping1.png" alt=""></div>
          <div class="shipping_content"><h4>Бесплатная доставка</h4><p>При заказе от 5 000 ₽</p></div>
        </div>
        <div class="single_shipping">
          <div class="shipping_icone"><img src="<?= APP_URL ?>/assets/img/about/shipping2.png" alt=""></div>
          <div class="shipping_content"><h4>Возврат 14 дней</h4><p>Без лишних вопросов</p></div>
        </div>
        <div class="single_shipping">
          <div class="shipping_icone"><img src="<?= APP_URL ?>/assets/img/about/shipping3.png" alt=""></div>
          <div class="shipping_content"><h4>Безопасная оплата</h4><p>100% защита транзакций</p></div>
        </div>
        <div class="single_shipping">
          <div class="shipping_icone"><img src="<?= APP_URL ?>/assets/img/about/shipping4.png" alt=""></div>
          <div class="shipping_content"><h4>Поддержка 24/7</h4><p>Звоните в любое время</p></div>
        </div>
        <div class="single_shipping">
          <div class="shipping_icone"><img src="<?= APP_URL ?>/assets/img/about/shipping5.png" alt=""></div>
          <div class="shipping_content"><h4>Гарантия качества</h4><p>На все товары</p></div>
        </div>
      </div>
    </div>
  </div>
  <!--shipping area end-->

  <div class="footer_top">
    <div class="container">
      <div class="row">
        <div class="col-lg-3">
          <div class="widgets_container">
            <h3>КОНТАКТЫ</h3>
            <div class="footer_contact">
              <div class="footer_contact_inner">
                <div class="contact_icone"><img src="<?= APP_URL ?>/assets/img/icon/icon-phone.png" alt=""></div>
                <div class="contact_text">
                  <p>Телефон:<br><strong><a href="tel:<?= sanitize(preg_replace('/[^+0-9]/','',$sitePhone)) ?>"><?= sanitize($sitePhone) ?></a></strong></p>
                </div>
              </div>
              <p><?= sanitize($siteName2) ?> — профессиональный магазин автозапчастей.</p>
              <p><?= sanitize($siteAddress) ?><br><?= sanitize($siteEmail) ?></p>
              <p><?= sanitize($workHours) ?></p>
            </div>
          </div>
        </div>
        <div class="col-lg-9">
          <div class="footer_col_container">
            <div class="widgets_container widget_menu">
              <h3>Каталог</h3>
              <div class="footer_menu">
                <ul>
                  <?php
                  $footerCats = getCategories();
                  foreach ($footerCats as $c):
                    if ($c['parent_id'] !== null) continue;
                  ?>
                  <li><a href="<?= APP_URL ?>/catalog/index.php?category=<?= sanitize($c['slug']) ?>"><?= sanitize($c['name']) ?></a></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
            <div class="widgets_container widget_menu">
              <h3>Информация</h3>
              <div class="footer_menu">
                <ul>
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
            <div class="widgets_container widget_menu">
              <h3>Покупателям</h3>
              <div class="footer_menu">
                <ul>
                  <li><a href="<?= APP_URL ?>/buyer/index.php">Мой аккаунт</a></li>
                  <li><a href="<?= APP_URL ?>/buyer/cart.php">Корзина</a></li>
                  <li><a href="<?= APP_URL ?>/buyer/orders.php">История заказов</a></li>
                  <li><a href="<?= APP_URL ?>/auth/register.php">Регистрация</a></li>
                </ul>
              </div>
            </div>
            <div class="widgets_container widget_menu">
              <h3>Оплата и доставка</h3>
              <div class="footer_menu">
                <ul>
                  <li><a href="#">Способы оплаты</a></li>
                  <li><a href="#">Условия доставки</a></li>
                  <li><a href="#">Регионы доставки</a></li>
                  <li><a href="#">Возврат товара</a></li>
                  <li><a href="#">Вопросы и ответы</a></li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="footer_bottom">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-6 col-md-6">
          <div class="copyright_area">
            <p>Copyright &copy; <?= date('Y') ?> <a href="<?= APP_URL ?>/index.php"><?= sanitize($siteName2) ?></a>. Все права защищены.</p>
          </div>
        </div>
        <div class="col-lg-6 col-md-6">
          <div class="footer_payment text-right">
            <img src="<?= APP_URL ?>/assets/img/icon/payment.png" alt="Способы оплаты">
          </div>
        </div>
      </div>
    </div>
  </div>
</footer>
<!--footer area end-->

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
