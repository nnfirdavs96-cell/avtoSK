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
<div class="az-admin-page">
<div class="az-admin-navbar">
  <a href="<?= APP_URL ?>/index.php" class="az-admin-logo">АВТО<span>ЗАПЧАСТЬ</span></a>
  <ul class="az-admin-nav-links"><li><a href="<?= APP_URL ?>/auth/logout.php">Выйти</a></li></ul>
</div>
<div class="dash-layout">
  <div class="dash-sidebar"><?php renderNav(); ?></div>
  <div class="dash-main">
    <div class="dash-heading">ПРОФИЛЬ</div>
    <?php if (!empty($errors)): ?>
    <div class="az-alert az-alert-danger"><?= implode('<br>', array_map('sanitize', $errors)) ?></div>
    <?php endif; ?>
    <div class="az-card" style="max-width:520px;">
      <div class="az-card-header"><h3>ДАННЫЕ АККАУНТА</h3></div>
      <div class="az-card-body">
        <form method="post" action="">
          <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">
          <div class="az-form-group">
            <label class="az-form-label">Имя пользователя</label>
            <input type="text" name="username" class="az-form-input" value="<?= sanitize($user['username']) ?>" required>
          </div>
          <div class="az-form-group">
            <label class="az-form-label">Email (только для чтения)</label>
            <input type="email" class="az-form-input" value="<?= sanitize($user['email']) ?>" readonly style="opacity:.6;">
          </div>
          <div class="az-form-group">
            <label class="az-form-label">Телефон</label>
            <input type="tel" name="phone" class="az-form-input" value="<?= sanitize($user['phone'] ?? '') ?>" placeholder="+7 (___) ___-__-__">
          </div>
          <hr style="border-color:var(--border);margin:16px 0;">
          <p style="font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:12px;">Смена пароля (не обязательно)</p>
          <div class="az-form-group">
            <label class="az-form-label">Старый пароль</label>
            <input type="password" name="old_password" class="az-form-input" placeholder="Текущий пароль">
          </div>
          <div class="az-form-group">
            <label class="az-form-label">Новый пароль</label>
            <input type="password" name="new_password" class="az-form-input" placeholder="Мин. 8 символов">
          </div>
          <button type="submit" class="az-admin-btn az-admin-btn-primary az-admin-btn-block">СОХРАНИТЬ</button>
        </form>
      </div>
    </div>
  </div>
</div>
</div>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
