<?php
require_once dirname(__DIR__) . '/config/config.php';
if (isLoggedIn()) redirect(APP_URL . '/buyer/index.php');

$errors = [];
$vals   = ['username'=>'','email'=>'','phone'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Ошибка безопасности.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';
        $vals     = compact('username','email','phone');
        if (mb_strlen($username) < 3 || mb_strlen($username) > 80) $errors[] = 'Имя: от 3 до 80 символов.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))             $errors[] = 'Некорректный email.';
        if (mb_strlen($password) < 8)                               $errors[] = 'Пароль: минимум 8 символов.';
        if ($password !== $confirm)                                  $errors[] = 'Пароли не совпадают.';
        if (empty($errors)) {
            $db  = getDB();
            $chk = $db->prepare("SELECT id FROM users WHERE email=?");
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $errors[] = 'Email уже зарегистрирован.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $db->prepare("INSERT INTO users (username,email,password_hash,role,phone) VALUES (?,?,?,'buyer',?)")
                   ->execute([$username, $email, $hash, $phone ?: null]);
                flashMessage('success','Регистрация успешна! Войдите в систему.');
                redirect(APP_URL . '/auth/login.php');
            }
        }
    }
}
$csrfToken = generateCsrfToken();
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Регистрация | <?= sanitize(getSetting('site_name','АвтоЗапчасть')) ?></title>
  <link rel="shortcut icon" href="<?= APP_URL ?>/assets/img/favicon.ico">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/plugins.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body style="background:#f8f9fa;padding:40px 16px;">
<div class="az-auth-wrap">
  <div class="az-auth-box" style="max-width:520px;">
    <a href="<?= APP_URL ?>/index.php" class="az-auth-logo">АВТО<span>ЗАПЧАСТЬ</span></a>
    <div class="az-auth-title">Регистрация</div>
    <div class="az-auth-subtitle">Создайте покупательский аккаунт</div>

    <?php if (!empty($errors)): ?>
    <ul class="az-error-list">
      <?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <form method="post" action="">
      <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
      <div class="az-form-row">
        <div class="az-form-group">
          <label class="az-form-label">Имя пользователя *</label>
          <input type="text" name="username" class="az-input" value="<?= sanitize($vals['username']) ?>" placeholder="ivanov_auto" required>
        </div>
        <div class="az-form-group">
          <label class="az-form-label">Телефон</label>
          <input type="tel" name="phone" class="az-input" value="<?= sanitize($vals['phone']) ?>" placeholder="+7 (___) ___-__-__">
        </div>
      </div>
      <div class="az-form-group">
        <label class="az-form-label">Email *</label>
        <input type="email" name="email" class="az-input" value="<?= sanitize($vals['email']) ?>" placeholder="your@email.ru" required>
      </div>
      <div class="az-form-row">
        <div class="az-form-group">
          <label class="az-form-label">Пароль *</label>
          <input type="password" name="password" class="az-input" placeholder="Мин. 8 символов" required>
        </div>
        <div class="az-form-group">
          <label class="az-form-label">Повтор пароля *</label>
          <input type="password" name="password_confirm" class="az-input" placeholder="••••••••" required>
        </div>
      </div>
      <button type="submit" class="az-btn az-btn-primary az-btn-block" style="padding:12px;">СОЗДАТЬ АККАУНТ</button>
    </form>
    <div class="az-auth-footer">
      Уже есть аккаунт? <a href="<?= APP_URL ?>/auth/login.php">Войти</a>
    </div>
  </div>
</div>
</body>
</html>
