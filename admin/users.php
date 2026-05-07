<?php
require_once dirname(__DIR__) . '/config/config.php';
requireRole(['admin', 'superadmin']);

$db   = getDB();
$csrf = generateCsrfToken();
$myId = (int)$_SESSION['user_id'];
$myRole = $_SESSION['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        flashMessage('danger','CSRF error.'); redirect(APP_URL.'/admin/users.php');
    }
    $action = $_POST['action'] ?? '';
    $uid    = (int)($_POST['user_id'] ?? 0);
    if (!$uid || $uid === $myId) { flashMessage('danger','Нельзя изменить свой аккаунт здесь.'); redirect(APP_URL.'/admin/users.php'); }

    // Admin cannot touch superadmin
    $targetStmt = $db->prepare("SELECT role FROM users WHERE id=?");
    $targetStmt->execute([$uid]);
    $targetRole = $targetStmt->fetchColumn();
    if ($targetRole === 'superadmin' && $myRole !== 'superadmin') {
        flashMessage('danger','Недостаточно прав.'); redirect(APP_URL.'/admin/users.php');
    }

    if ($action === 'toggle_active') {
        $db->prepare("UPDATE users SET is_active = 1 - is_active WHERE id=?")->execute([$uid]);
        flashMessage('success','Статус пользователя изменён.');
    } elseif ($action === 'change_role' && $myRole === 'superadmin') {
        $role = $_POST['role'] ?? '';
        if (in_array($role, ['buyer','manager','admin','superadmin'], true)) {
            $db->prepare("UPDATE users SET role=? WHERE id=?")->execute([$role, $uid]);
            flashMessage('success','Роль изменена.');
        }
    }
    redirect(APP_URL.'/admin/users.php');
}

$search = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage= 20;
$where  = [];
$params = [];
if ($search) { $where[] = '(username LIKE ? OR email LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
$whereSQL = $where ? 'WHERE '.implode(' AND ',$where) : '';

$cntStmt = $db->prepare("SELECT COUNT(*) FROM users $whereSQL");
$cntStmt->execute($params);
$total  = (int)$cntStmt->fetchColumn();
$pages  = max(1, (int)ceil($total / $perPage));
$offset = ($page-1)*$perPage;

$usersStmt = $db->prepare("SELECT * FROM users $whereSQL ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$usersStmt->execute($params);
$users = $usersStmt->fetchAll();

$pageTitle = 'Пользователи';
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

    <!-- Поиск -->
    <form method="get" action="" style="margin-bottom:16px;display:flex;gap:10px;">
      <input type="text" name="q" class="az-form-input" style="max-width:320px;" placeholder="Поиск по имени или email" value="<?= sanitize($search) ?>">
      <button type="submit" class="az-admin-btn az-admin-btn-primary az-admin-btn-sm">Найти</button>
      <?php if ($search): ?><a href="?" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">Сбросить</a><?php endif; ?>
    </form>

    <div class="az-card">
      <div class="az-table-wrap">
        <table class="az-table">
          <thead><tr><th>ID</th><th>Пользователь</th><th>Email</th><th>Роль</th><th>Телефон</th><th>Статус</th><th>Дата</th><th>Действия</th></tr></thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
              <td style="font-family:monospace;color:var(--text-muted);"><?= $u['id'] ?></td>
              <td style="font-weight:500;"><?= sanitize($u['username']) ?><?= $u['id']==$myId?' <span style="font-size:10px;color:var(--accent);">(вы)</span>':'' ?></td>
              <td style="color:var(--text-secondary);font-size:12px;"><?= sanitize($u['email']) ?></td>
              <td><span class="az-role az-role-<?= $u['role'] ?>"><?= sanitize($u['role']) ?></span></td>
              <td style="color:var(--text-muted);font-size:12px;"><?= sanitize($u['phone'] ?? '—') ?></td>
              <td>
                <?php if ($u['is_active']): ?>
                <span style="color:var(--success);font-size:12px;">● Активен</span>
                <?php else: ?>
                <span style="color:var(--danger);font-size:12px;">● Блокирован</span>
                <?php endif; ?>
              </td>
              <td style="color:var(--text-muted);font-size:11px;"><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
              <td>
                <?php if ($u['id'] !== $myId && ($u['role'] !== 'superadmin' || $myRole === 'superadmin')): ?>
                <form method="post" action="" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">
                  <input type="hidden" name="action" value="toggle_active">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <button type="submit" class="az-admin-btn az-admin-btn-sm <?= $u['is_active']?'az-admin-btn-danger':'az-admin-btn-success' ?>"
                          onclick="return confirm('<?= $u['is_active']?'Заблокировать?':'Разблокировать?' ?>')">
                    <?= $u['is_active'] ? 'Блок' : 'Разбл.' ?>
                  </button>
                </form>
                <?php if ($myRole === 'superadmin'): ?>
                <form method="post" action="" style="display:inline;margin-left:4px;">
                  <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">
                  <input type="hidden" name="action" value="change_role">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <select name="role" class="az-form-select" style="width:auto;font-size:11px;padding:4px 6px;display:inline;" onchange="this.form.submit()">
                    <?php foreach (['buyer','manager','admin','superadmin'] as $r): ?>
                    <option value="<?= $r ?>" <?= $u['role']===$r?'selected':'' ?>><?= $r ?></option>
                    <?php endforeach; ?>
                  </select>
                  <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">
                </form>
                <?php endif; ?>
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
