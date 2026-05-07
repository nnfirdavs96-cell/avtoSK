<?php
require_once dirname(__DIR__) . '/config/config.php';
requireRole(['superadmin']);

$db   = getDB();
$csrf = generateCsrfToken();
$myId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        flashMessage('danger','CSRF error.'); redirect(APP_URL.'/superadmin/users.php');
    }
    $pAction = $_POST['action'] ?? '';
    $uid     = (int)($_POST['user_id'] ?? 0);

    if (!$uid || $uid === $myId) {
        flashMessage('danger','Нельзя изменить свой аккаунт здесь.'); redirect(APP_URL.'/superadmin/users.php');
    }

    if ($pAction === 'toggle_active') {
        $db->prepare("UPDATE users SET is_active = 1 - is_active WHERE id=?")->execute([$uid]);
        flashMessage('success','Статус пользователя изменён.');
    } elseif ($pAction === 'change_role') {
        $role = $_POST['role'] ?? '';
        if (in_array($role, ['buyer','manager','admin','superadmin'], true)) {
            $db->prepare("UPDATE users SET role=? WHERE id=?")->execute([$role, $uid]);
            flashMessage('success','Роль изменена на: '.$role);
        }
    } elseif ($pAction === 'delete') {
        $db->prepare("UPDATE users SET is_active=0 WHERE id=?")->execute([$uid]);
        flashMessage('success','Аккаунт деактивирован.');
    } elseif ($pAction === 'reset_password') {
        $newPw = $_POST['new_password'] ?? '';
        if (strlen($newPw) >= 6) {
            $hash = password_hash($newPw, PASSWORD_DEFAULT);
            $db->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([$hash, $uid]);
            flashMessage('success','Пароль сброшен.');
        } else {
            flashMessage('danger','Пароль должен быть не менее 6 символов.');
        }
    }
    redirect(APP_URL.'/superadmin/users.php');
}

// Detail view
$viewId   = (int)($_GET['id'] ?? 0);
$viewUser = null;
if ($viewId) {
    $s = $db->prepare("SELECT * FROM users WHERE id=?");
    $s->execute([$viewId]);
    $viewUser = $s->fetch();
}

$search  = trim($_GET['q'] ?? '');
$roleF   = $_GET['role'] ?? '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$where   = [];
$params  = [];
if ($search) { $where[] = '(username LIKE ? OR email LIKE ? OR phone LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($roleF)  { $where[] = 'role=?'; $params[] = $roleF; }
$whereSQL = $where ? 'WHERE '.implode(' AND ',$where) : '';

$cntStmt = $db->prepare("SELECT COUNT(*) FROM users $whereSQL");
$cntStmt->execute($params);
$total  = (int)$cntStmt->fetchColumn();
$pages  = max(1, (int)ceil($total/$perPage));
$offset = ($page-1)*$perPage;

$usersStmt = $db->prepare("SELECT * FROM users $whereSQL ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$usersStmt->execute($params);
$users = $usersStmt->fetchAll();

$roles = ['buyer','manager','admin','superadmin'];
$pageTitle = 'Управление пользователями';
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
    <div class="dash-heading">ПОЛЬЗОВАТЕЛИ (<?= $total ?>)</div>

    <?php if ($viewUser): ?>
    <!-- Детали пользователя -->
    <div class="az-card" style="max-width:600px;margin-bottom:20px;">
      <div class="az-card-header">
        <h3><?= sanitize($viewUser['username']) ?></h3>
        <span class="az-role az-role-<?= $viewUser['role'] ?>"><?= $viewUser['role'] ?></span>
      </div>
      <div class="az-card-body">
        <div class="az-grid-2" style="margin-bottom:16px;">
          <div>
            <div style="font-size:11px;color:var(--text-muted);margin-bottom:2px;">Email</div>
            <div><?= sanitize($viewUser['email']) ?></div>
          </div>
          <div>
            <div style="font-size:11px;color:var(--text-muted);margin-bottom:2px;">Телефон</div>
            <div><?= sanitize($viewUser['phone'] ?? '—') ?></div>
          </div>
          <div>
            <div style="font-size:11px;color:var(--text-muted);margin-bottom:2px;">Статус</div>
            <div style="color:<?= $viewUser['is_active']?'var(--success)':'var(--danger)' ?>;">
              <?= $viewUser['is_active'] ? '● Активен' : '● Заблокирован' ?>
            </div>
          </div>
          <div>
            <div style="font-size:11px;color:var(--text-muted);margin-bottom:2px;">Дата регистрации</div>
            <div><?= date('d.m.Y H:i', strtotime($viewUser['created_at'])) ?></div>
          </div>
        </div>

        <?php if ($viewUser['id'] !== $myId): ?>
        <!-- Сменить роль -->
        <form method="post" action="" style="margin-bottom:12px;display:flex;gap:10px;align-items:center;">
          <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">
          <input type="hidden" name="action" value="change_role">
          <input type="hidden" name="user_id" value="<?= $viewUser['id'] ?>">
          <label class="az-form-label" style="margin:0;white-space:nowrap;">Роль:</label>
          <select name="role" class="az-form-select" style="width:auto;">
            <?php foreach ($roles as $r): ?>
            <option value="<?= $r ?>" <?= $viewUser['role']===$r?'selected':'' ?>><?= $r ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="az-admin-btn az-admin-btn-primary az-admin-btn-sm">Изменить</button>
        </form>
        <!-- Сброс пароля -->
        <form method="post" action="" style="margin-bottom:12px;display:flex;gap:10px;align-items:center;">
          <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">
          <input type="hidden" name="action" value="reset_password">
          <input type="hidden" name="user_id" value="<?= $viewUser['id'] ?>">
          <input type="password" name="new_password" class="az-form-input" style="max-width:200px;" placeholder="Новый пароль" required>
          <button type="submit" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">Сбросить пароль</button>
        </form>
        <!-- Блок/разблок -->
        <form method="post" action="" style="display:inline;">
          <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">
          <input type="hidden" name="action" value="toggle_active">
          <input type="hidden" name="user_id" value="<?= $viewUser['id'] ?>">
          <button type="submit" class="az-admin-btn <?= $viewUser['is_active']?'az-admin-btn-danger':'az-admin-btn-success' ?> az-admin-btn-sm"
                  onclick="return confirm('<?= $viewUser['is_active']?'Заблокировать?':'Разблокировать?' ?>')">
            <?= $viewUser['is_active'] ? 'Заблокировать' : 'Разблокировать' ?>
          </button>
        </form>
        <?php endif; ?>
      </div>
      <div class="az-card-footer">
        <a href="<?= APP_URL ?>/superadmin/users.php" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">← Все пользователи</a>
      </div>
    </div>
    <?php endif; ?>

    <!-- Поиск и фильтры -->
    <form method="get" action="" style="margin-bottom:14px;display:flex;gap:10px;flex-wrap:wrap;">
      <input type="text" name="q" class="az-form-input" style="max-width:280px;" placeholder="Поиск по имени, email, телефону" value="<?= sanitize($search) ?>">
      <select name="role" class="az-form-select" style="width:auto;">
        <option value="">Все роли</option>
        <?php foreach ($roles as $r): ?>
        <option value="<?= $r ?>" <?= $roleF===$r?'selected':'' ?>><?= $r ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="az-admin-btn az-admin-btn-primary az-admin-btn-sm">Найти</button>
      <?php if ($search||$roleF): ?><a href="?" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">Сбросить</a><?php endif; ?>
    </form>

    <div class="az-card">
      <div class="az-table-wrap">
        <table class="az-table">
          <thead><tr><th>ID</th><th>Пользователь</th><th>Email</th><th>Роль</th><th>Телефон</th><th>Статус</th><th>Дата</th><th>Действия</th></tr></thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
              <td style="font-family:monospace;color:var(--text-muted);"><?= $u['id'] ?></td>
              <td style="font-weight:500;">
                <?= sanitize($u['username']) ?>
                <?= $u['id']==$myId?' <span style="font-size:10px;color:var(--accent);">(вы)</span>':'' ?>
              </td>
              <td style="color:var(--text-secondary);font-size:12px;"><?= sanitize($u['email']) ?></td>
              <td><span class="az-role az-role-<?= $u['role'] ?>"><?= $u['role'] ?></span></td>
              <td style="color:var(--text-muted);font-size:12px;"><?= sanitize($u['phone'] ?? '—') ?></td>
              <td>
                <?php if ($u['is_active']): ?>
                <span style="color:var(--success);font-size:12px;">● Активен</span>
                <?php else: ?>
                <span style="color:var(--danger);font-size:12px;">● Блок</span>
                <?php endif; ?>
              </td>
              <td style="color:var(--text-muted);font-size:11px;"><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
              <td style="display:flex;gap:5px;flex-wrap:wrap;">
                <a href="?id=<?= $u['id'] ?>" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">Детали</a>
                <?php if ($u['id'] !== $myId): ?>
                <form method="post" action="" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">
                  <input type="hidden" name="action" value="toggle_active">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <button type="submit" class="az-admin-btn az-admin-btn-sm <?= $u['is_active']?'az-admin-btn-danger':'az-admin-btn-success' ?>"
                          onclick="return confirm('<?= $u['is_active']?'Заблокировать?':'Разблокировать?' ?>')">
                    <?= $u['is_active'] ? 'Блок' : 'Разбл.' ?>
                  </button>
                </form>
                <form method="post" action="" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">
                  <input type="hidden" name="action" value="change_role">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <select name="role" class="az-form-select" style="width:auto;font-size:11px;padding:3px 5px;" onchange="this.form.submit()">
                    <?php foreach ($roles as $r): ?>
                    <option value="<?= $r ?>" <?= $u['role']===$r?'selected':'' ?>><?= $r ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if ($pages > 1): ?>
      <div class="az-card-footer">
        <div class="az-pagination">
          <?php for ($i=1;$i<=$pages;$i++): $qp=array_merge($_GET,['page'=>$i]); ?>
          <a href="?<?= http_build_query($qp) ?>" class="az-page-link <?= $i==$page?'active':'' ?>"><?= $i ?></a>
          <?php endfor; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
