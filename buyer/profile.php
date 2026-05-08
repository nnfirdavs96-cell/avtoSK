<?php
require_once dirname(__DIR__) . '/config/config.php';
requireRole('buyer');
$user   = getCurrentUser();
$db     = getDB();
$csrf   = generateCsrfToken();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { flashMessage('danger','CSRF error.'); redirect(APP_URL.'/buyer/profile.php'); }
    $username = trim($_POST['username'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $oldPass  = $_POST['old_password'] ?? '';
    $newPass  = $_POST['new_password'] ?? '';
    if (mb_strlen($username) < 3) $errors[] = 'Имя: минимум 3 символа.';
    if (empty($errors)) {
        $db->prepare("UPDATE users SET username=?, phone=?, updated_at=NOW() WHERE id=?")->execute([$username, $phone ?: null, $user['id']]);
        if ($oldPass || $newPass) {
            if (!password_verify($oldPass, $user['password_hash'])) {
                $errors[] = 'Старый пароль неверен.';
            } elseif (mb_strlen($newPass) < 8) {
                $errors[] = 'Новый пароль: минимум 8 символов.';
            } else {
                $hash = password_hash($newPass, PASSWORD_DEFAULT);
                $db->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([$hash, $user['id']]);
            }
        }
        if (empty($errors)) {
            unset($_SESSION['user_data']);
            flashMessage('success','Профиль обновлён.');
            redirect(APP_URL.'/buyer/profile.php');
        }
    }
}
$user = getCurrentUser();
$pageTitle = 'Мой профиль';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/nav.php';
?>

<!--breadcrumb area start-->
<div class="breadcrumb_area">
  <div class="container">
    <div class="row"><div class="col-12">
      <div class="breadcrumb_content">
        <ul>
          <li><a href="<?= APP_URL ?>/index.php">Главная</a></li>
          <li><a href="<?= APP_URL ?>/buyer/index.php">Кабинет</a></li>
          <li class="active">Профиль</li>
        </ul>
      </div>
    </div></div>
  </div>
</div>
<!--breadcrumb area end-->

<div class="account_area">
  <div class="container">
    <div class="row">

      <!-- Sidebar -->
      <div class="col-lg-3 col-md-4">
        <div class="account_sidebar">
          <div class="widget_list">
            <h3 class="widget_title">Мой аккаунт</h3>
            <?php renderNav(); ?>
          </div>
        </div>
      </div>

      <!-- Main content -->
      <div class="col-lg-9 col-md-8">

        <div style="font-size:18px;font-weight:700;color:#333;margin-bottom:24px;padding-bottom:12px;border-bottom:2px solid #e74c3c;">
          Мой профиль
        </div>

        <?php if (!empty($errors)): ?>
        <div style="background:#fff3f3;border:1px solid #f5c6c6;border-radius:4px;padding:14px 18px;margin-bottom:20px;color:#c0392b;font-size:13px;">
          <?= implode('<br>', array_map('sanitize', $errors)) ?>
        </div>
        <?php endif; ?>

        <div class="account_card" style="max-width:560px;">
          <div class="account_card_header">
            <h3>Данные аккаунта</h3>
          </div>
          <div class="account_card_body">
            <form method="post" action="">
              <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">

              <div class="account_form_group">
                <label class="account_form_label">Имя пользователя *</label>
                <input type="text" name="username" class="account_form_input" value="<?= sanitize($user['username']) ?>" required>
              </div>

              <div class="account_form_group">
                <label class="account_form_label">Email (только для чтения)</label>
                <input type="email" class="account_form_input" value="<?= sanitize($user['email']) ?>" readonly>
              </div>

              <div class="account_form_group">
                <label class="account_form_label">Телефон</label>
                <input type="tel" name="phone" class="account_form_input" value="<?= sanitize($user['phone'] ?? '') ?>" placeholder="+7 (___) ___-__-__">
              </div>

              <hr style="border:none;border-top:1px solid #f0f0f0;margin:20px 0;">

              <p style="font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:#888;margin-bottom:14px;">Смена пароля (не обязательно)</p>

              <div class="account_form_group">
                <label class="account_form_label">Старый пароль</label>
                <input type="password" name="old_password" class="account_form_input" placeholder="Текущий пароль">
              </div>

              <div class="account_form_group">
                <label class="account_form_label">Новый пароль</label>
                <input type="password" name="new_password" class="account_form_input" placeholder="Минимум 8 символов">
              </div>

              <button type="submit" class="button account_btn_block" style="width:100%;padding:14px;font-size:14px;">
                Сохранить изменения
              </button>
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
