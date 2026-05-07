<?php
require_once dirname(__DIR__) . '/config/config.php';
requireRole(['superadmin']);

$db   = getDB();
$csrf = generateCsrfToken();
$errors  = [];
$success = false;

// Load all settings
$settingsRaw = $db->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll();
$settings = array_column($settingsRaw, 'setting_value', 'setting_key');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        flashMessage('danger','CSRF error.'); redirect(APP_URL.'/superadmin/settings.php');
    }

    $allowed = [
        'site_name','site_email','site_phone','site_address',
        'default_currency','items_per_page',
        'social_vk','social_telegram','social_whatsapp',
        'warehouse_api_url','warehouse_api_token','warehouse_cache_hours',
        'meta_description','meta_keywords',
        'footer_about',
    ];

    $stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?");
    foreach ($allowed as $key) {
        $val = trim($_POST[$key] ?? '');
        $stmt->execute([$key, $val, $val]);
    }
    flashMessage('success','Настройки сохранены.');
    redirect(APP_URL.'/superadmin/settings.php');
}

$pageTitle = 'Настройки сайта';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/nav.php';
?>

<div class="az-admin-page">
<div class="az-admin-navbar">
  <a href="<?= APP_URL ?>/index.php" class="az-admin-logo">АВТО<span>ЗАПЧАСТЬ</span></a>
  <ul class="az-admin-nav-links">
    <li><a href="<?= APP_URL ?>/auth/logout.php">Выйти</a></li>
  </ul>
</div>
<div class="dash-layout">
  <div class="dash-sidebar"><?php renderNav(); ?></div>
  <div class="dash-main">
    <div class="dash-heading">НАСТРОЙКИ САЙТА</div>

    <form method="post" action="">
      <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">

      <!-- Основные -->
      <div class="az-card" style="margin-bottom:20px;">
        <div class="az-card-header"><h3>ОСНОВНЫЕ НАСТРОЙКИ</h3></div>
        <div class="az-card-body">
          <div class="az-grid-2">
            <div class="az-form-group">
              <label class="az-form-label">Название сайта</label>
              <input type="text" name="site_name" class="az-form-input" value="<?= sanitize($settings['site_name'] ?? 'АвтоЗапчасть') ?>">
            </div>
            <div class="az-form-group">
              <label class="az-form-label">Email</label>
              <input type="email" name="site_email" class="az-form-input" value="<?= sanitize($settings['site_email'] ?? '') ?>">
            </div>
            <div class="az-form-group">
              <label class="az-form-label">Телефон</label>
              <input type="text" name="site_phone" class="az-form-input" value="<?= sanitize($settings['site_phone'] ?? '') ?>" placeholder="+7 (800) 000-00-00">
            </div>
            <div class="az-form-group">
              <label class="az-form-label">Товаров на странице</label>
              <input type="number" name="items_per_page" class="az-form-input" min="4" max="100" value="<?= (int)($settings['items_per_page'] ?? 20) ?>">
            </div>
          </div>
          <div class="az-form-group">
            <label class="az-form-label">Адрес</label>
            <input type="text" name="site_address" class="az-form-input" value="<?= sanitize($settings['site_address'] ?? '') ?>" placeholder="г. Москва, ул. Автозаводская, 1">
          </div>
          <div class="az-form-group">
            <label class="az-form-label">О компании (футер)</label>
            <textarea name="footer_about" class="az-form-textarea" rows="3"><?= sanitize($settings['footer_about'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <!-- SEO -->
      <div class="az-card" style="margin-bottom:20px;">
        <div class="az-card-header"><h3>SEO</h3></div>
        <div class="az-card-body">
          <div class="az-form-group">
            <label class="az-form-label">Meta Description</label>
            <textarea name="meta_description" class="az-form-textarea" rows="2"><?= sanitize($settings['meta_description'] ?? '') ?></textarea>
          </div>
          <div class="az-form-group">
            <label class="az-form-label">Meta Keywords</label>
            <input type="text" name="meta_keywords" class="az-form-input" value="<?= sanitize($settings['meta_keywords'] ?? '') ?>" placeholder="автозапчасти, запчасти, детали">
          </div>
        </div>
      </div>

      <!-- Соцсети -->
      <div class="az-card" style="margin-bottom:20px;">
        <div class="az-card-header"><h3>СОЦИАЛЬНЫЕ СЕТИ</h3></div>
        <div class="az-card-body">
          <div class="az-grid-2">
            <div class="az-form-group">
              <label class="az-form-label">ВКонтакте</label>
              <input type="url" name="social_vk" class="az-form-input" value="<?= sanitize($settings['social_vk'] ?? '') ?>" placeholder="https://vk.com/...">
            </div>
            <div class="az-form-group">
              <label class="az-form-label">Telegram</label>
              <input type="url" name="social_telegram" class="az-form-input" value="<?= sanitize($settings['social_telegram'] ?? '') ?>" placeholder="https://t.me/...">
            </div>
            <div class="az-form-group">
              <label class="az-form-label">WhatsApp</label>
              <input type="url" name="social_whatsapp" class="az-form-input" value="<?= sanitize($settings['social_whatsapp'] ?? '') ?>" placeholder="https://wa.me/...">
            </div>
            <div class="az-form-group">
              <label class="az-form-label">Валюта по умолчанию</label>
              <select name="default_currency" class="az-form-select">
                <?php foreach (['RUB','USD','EUR','TJS','KZT'] as $cur): ?>
                <option value="<?= $cur ?>" <?= ($settings['default_currency'] ?? 'RUB')===$cur?'selected':'' ?>><?= $cur ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Склад Москвы -->
      <div class="az-card" style="margin-bottom:20px;">
        <div class="az-card-header"><h3>СКЛАД МОСКВЫ (API)</h3></div>
        <div class="az-card-body">
          <div class="az-form-group">
            <label class="az-form-label">URL API склада</label>
            <input type="url" name="warehouse_api_url" class="az-form-input" value="<?= sanitize($settings['warehouse_api_url'] ?? '') ?>" placeholder="https://api.warehouse.ru/v1/stock">
          </div>
          <div class="az-grid-2">
            <div class="az-form-group">
              <label class="az-form-label">Bearer Token</label>
              <input type="text" name="warehouse_api_token" class="az-form-input" value="<?= sanitize($settings['warehouse_api_token'] ?? '') ?>" placeholder="your-api-token-here">
            </div>
            <div class="az-form-group">
              <label class="az-form-label">Кэш (часов)</label>
              <input type="number" name="warehouse_cache_hours" class="az-form-input" min="1" max="72" value="<?= (int)($settings['warehouse_cache_hours'] ?? 6) ?>">
            </div>
          </div>
        </div>
      </div>

      <button type="submit" class="az-admin-btn az-admin-btn-primary az-admin-btn-lg">СОХРАНИТЬ НАСТРОЙКИ</button>
    </form>

  </div>
</div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
