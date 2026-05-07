<?php
require_once dirname(__DIR__) . '/config/config.php';

if (isLoggedIn()) {
    $r = $_SESSION['role'] ?? 'buyer';
    redirect(APP_URL . match($r) {
        'superadmin' => '/superadmin/index.php',
        'admin'      => '/admin/index.php',
        'manager'    => '/manager/index.php',
        default      => '/buyer/index.php',
    });
}

$errors   = [];
$emailVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Ошибка безопасности. Обновите страницу.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $emailVal = $email;
        if (empty($email) || empty($password)) {
            $errors[] = 'Введите email и пароль.';
        } else {
            $db   = getDB();
            $stmt = $db->prepare("SELECT id, username, email, password_hash, role, is_active FROM users WHERE email=?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if (!$user || !password_verify($password, $user['password_hash'])) {
                $errors[] = 'Неверный email или пароль.';
            } elseif (!$user['is_active']) {
                $errors[] = 'Ваш аккаунт деактивирован.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role']    = $user['role'];
                unset($_SESSION['user_data']);
                flashMessage('success', 'Добро пожаловать, ' . $user['username'] . '!');
                $redirect = $_GET['redirect'] ?? '';
                if ($redirect && str_starts_with($redirect, '/')) redirect(APP_URL . $redirect);
                redirect(APP_URL . match($user['role']) {
                    'superadmin' => '/superadmin/index.php',
                    'admin'      => '/admin/index.php',
                    'manager'    => '/manager/index.php',
                    default      => '/buyer/index.php',
                });
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
  <title>Вход | <?= sanitize(getSetting('site_name','АвтоЗапчасть')) ?></title>
  <link rel="shortcut icon" href="<?= APP_URL ?>/assets/img/favicon.ico">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/plugins.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body style="background:#f8f9fa;">
<div class="az-auth-wrap">
  <div class="az-auth-box">
    <a href="<?= APP_URL ?>/index.php" class="az-auth-logo">
      АВТО<span>ЗАПЧАСТЬ</span>
    </a>
    <div class="az-auth-title">Вход</div>
    <div class="az-auth-subtitle">Введите данные вашего аккаунта</div>

    <?php if (!empty($errors)): ?>
    <ul class="az-error-list">
      <?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <form method="post" action="">
      <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
      <div class="az-form-group">
        <label class="az-form-label">Email</label>
        <input type="email" name="email" class="az-input" value="<?= sanitize($emailVal) ?>" placeholder="admin@avtozapchast.ru" required autofocus>
      </div>
      <div class="az-form-group">
        <label class="az-form-label">Пароль</label>
        <input type="password" name="password" class="az-input" placeholder="••••••••" required>
      </div>
      <button type="submit" class="az-btn az-btn-primary az-btn-block" style="margin-top:8px;padding:12px;">ВОЙТИ</button>
    </form>

    <div class="az-auth-footer">
      Нет аккаунта? <a href="<?= APP_URL ?>/auth/register.php">Зарегистрироваться</a>
    </div>

    <div class="az-demo-accounts">
      <div class="az-demo-label">Демо-аккаунты (пароль: Password123!)</div>
      <div class="az-demo-row"><span>superadmin@avtozapchast.ru</span><span class="az-role az-role-superadmin">superadmin</span></div>
      <div class="az-demo-row"><span>admin@avtozapchast.ru</span><span class="az-role az-role-admin">admin</span></div>
      <div class="az-demo-row"><span>manager@avtozapchast.ru</span><span class="az-role az-role-manager">manager</span></div>
      <div class="az-demo-row"><span>buyer@avtozapchast.ru</span><span class="az-role az-role-buyer">buyer</span></div>
    </div>
  </div>
</div>
</body>
</html>
