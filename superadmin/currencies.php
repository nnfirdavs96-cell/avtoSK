<?php
require_once dirname(__DIR__) . '/config/config.php';
requireRole(['superadmin']);

$db   = getDB();
$csrf = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        flashMessage('danger','CSRF error.'); redirect(APP_URL.'/superadmin/currencies.php');
    }
    $pAction = $_POST['action'] ?? '';

    if ($pAction === 'update_cbr') {
        $ok = updateExchangeRatesFromCBR();
        if ($ok) {
            flashMessage('success','Курсы валют обновлены с сайта ЦБ РФ.');
        } else {
            flashMessage('danger','Не удалось получить данные от ЦБ РФ. Проверьте подключение.');
        }
        redirect(APP_URL.'/superadmin/currencies.php');
    }

    if ($pAction === 'save_rates') {
        $currencies = $_POST['currencies'] ?? [];
        $stmt = $db->prepare("UPDATE exchange_rates SET rate=?, is_active=? WHERE currency=?");
        foreach ($currencies as $cur => $data) {
            $rate     = (float)str_replace(',', '.', $data['rate'] ?? 1);
            $isActive = isset($data['is_active']) ? 1 : 0;
            if ($cur === 'RUB') { $rate = 1.000000; $isActive = 1; }
            $stmt->execute([$rate, $isActive, strtoupper($cur)]);
        }
        flashMessage('success','Курсы обновлены вручную.');
        redirect(APP_URL.'/superadmin/currencies.php');
    }

    if ($pAction === 'add_currency') {
        $cur    = strtoupper(trim($_POST['currency'] ?? ''));
        $name   = trim($_POST['name'] ?? '');
        $symbol = trim($_POST['symbol'] ?? '');
        $rate   = (float)str_replace(',', '.', $_POST['rate'] ?? 1);
        if ($cur && $name && $rate > 0) {
            $chk = $db->prepare("SELECT id FROM exchange_rates WHERE currency=?");
            $chk->execute([$cur]);
            if ($chk->fetch()) {
                flashMessage('danger','Такая валюта уже существует.');
            } else {
                $db->prepare("INSERT INTO exchange_rates (currency,name,symbol,rate) VALUES (?,?,?,?)")->execute([$cur,$name,$symbol,$rate]);
                flashMessage('success','Валюта добавлена.');
            }
        } else {
            flashMessage('danger','Заполните все поля.');
        }
        redirect(APP_URL.'/superadmin/currencies.php');
    }
}

$rates = $db->query("SELECT * FROM exchange_rates ORDER BY FIELD(currency,'RUB','USD','EUR','TJS','KZT'), currency")->fetchAll();
$lastUpdate = $db->query("SELECT MAX(updated_at) FROM exchange_rates")->fetchColumn();

$pageTitle = 'Курсы валют';
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
    <div class="dash-heading">КУРСЫ ВАЛЮТ</div>

    <!-- Обновление с ЦБ РФ -->
    <div class="az-card" style="margin-bottom:20px;">
      <div class="az-card-header">
        <div>
          <h3>ЦЕНТРАЛЬНЫЙ БАНК РФ</h3>
          <?php if ($lastUpdate): ?>
          <div style="font-size:11px;color:var(--text-muted);">
            Последнее обновление: <?= date('d.m.Y H:i', strtotime($lastUpdate)) ?>
          </div>
          <?php endif; ?>
        </div>
        <form method="post" action="">
          <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">
          <input type="hidden" name="action" value="update_cbr">
          <button type="submit" class="az-admin-btn az-admin-btn-primary az-admin-btn-sm"
                  onclick="return confirm('Получить актуальные курсы с cbr.ru?')">
            Обновить с ЦБ РФ
          </button>
        </form>
      </div>
      <div class="az-card-body" style="font-size:13px;color:var(--text-muted);">
        Нажмите кнопку для автоматического получения курсов USD, EUR и других валют
        с официального сайта Центрального банка России (cbr.ru).
      </div>
    </div>

    <!-- Ручное редактирование -->
    <div class="az-card" style="margin-bottom:20px;">
      <div class="az-card-header"><h3>КУРСЫ (руб. за 1 единицу валюты)</h3></div>
      <div class="az-card-body">
        <form method="post" action="">
          <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">
          <input type="hidden" name="action" value="save_rates">
          <div class="az-table-wrap">
            <table class="az-table">
              <thead><tr><th>Код</th><th>Название</th><th>Символ</th><th>Курс к рублю</th><th>Активна</th></tr></thead>
              <tbody>
                <?php foreach ($rates as $r): ?>
                <tr>
                  <td><span style="font-family:monospace;font-weight:700;color:var(--accent);"><?= sanitize($r['currency']) ?></span></td>
                  <td style="font-size:13px;"><?= sanitize($r['name']) ?></td>
                  <td style="font-family:monospace;font-size:15px;"><?= sanitize($r['symbol']) ?></td>
                  <td>
                    <?php if ($r['currency'] === 'RUB'): ?>
                    <input type="text" value="1.000000" class="az-form-input" style="max-width:140px;font-family:monospace;" disabled>
                    <input type="hidden" name="currencies[RUB][rate]" value="1">
                    <input type="hidden" name="currencies[RUB][is_active]" value="1">
                    <?php else: ?>
                    <input type="number" name="currencies[<?= $r['currency'] ?>][rate]"
                           class="az-form-input" style="max-width:140px;font-family:monospace;"
                           step="0.000001" min="0.000001"
                           value="<?= number_format((float)$r['rate'], 6, '.', '') ?>">
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($r['currency'] === 'RUB'): ?>
                    <span style="color:var(--success);font-size:12px;">● Всегда</span>
                    <?php else: ?>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                      <input type="checkbox" name="currencies[<?= $r['currency'] ?>][is_active]"
                             value="1" <?= $r['is_active']?'checked':'' ?> style="width:16px;height:16px;">
                      <span style="font-size:12px;color:var(--text-muted);"><?= $r['is_active']?'Активна':'Выключена' ?></span>
                    </label>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div style="margin-top:16px;">
            <button type="submit" class="az-admin-btn az-admin-btn-primary az-admin-btn-sm">Сохранить курсы</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Добавить валюту -->
    <div class="az-card">
      <div class="az-card-header"><h3>ДОБАВИТЬ ВАЛЮТУ</h3></div>
      <div class="az-card-body">
        <form method="post" action="">
          <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">
          <input type="hidden" name="action" value="add_currency">
          <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
            <div class="az-form-group" style="margin:0;">
              <label class="az-form-label">Код (ISO)</label>
              <input type="text" name="currency" class="az-form-input" style="max-width:90px;" placeholder="GBP" maxlength="3" style="text-transform:uppercase;">
            </div>
            <div class="az-form-group" style="margin:0;">
              <label class="az-form-label">Название</label>
              <input type="text" name="name" class="az-form-input" style="max-width:180px;" placeholder="Фунт стерлингов">
            </div>
            <div class="az-form-group" style="margin:0;">
              <label class="az-form-label">Символ</label>
              <input type="text" name="symbol" class="az-form-input" style="max-width:70px;" placeholder="£" maxlength="5">
            </div>
            <div class="az-form-group" style="margin:0;">
              <label class="az-form-label">Курс (к руб.)</label>
              <input type="number" name="rate" class="az-form-input" style="max-width:130px;" step="0.000001" min="0.000001" placeholder="128.50">
            </div>
            <button type="submit" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">Добавить</button>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
