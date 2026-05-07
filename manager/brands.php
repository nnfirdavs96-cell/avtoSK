<?php
require_once dirname(__DIR__) . '/config/config.php';
requireRole(['manager', 'superadmin']);

$db     = getDB();
$csrf   = generateCsrfToken();
$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        flashMessage('danger','CSRF error.'); redirect(APP_URL.'/manager/brands.php');
    }
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'delete') {
        $delId = (int)($_POST['id'] ?? 0);
        $db->prepare("UPDATE brands SET is_active=0 WHERE id=?")->execute([$delId]);
        flashMessage('success','Бренд удалён.');
        redirect(APP_URL.'/manager/brands.php');
    }

    $name    = trim($_POST['name'] ?? '');
    $slug    = trim($_POST['slug'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $desc    = trim($_POST['description'] ?? '');
    $bid     = (int)($_POST['id'] ?? 0);

    if (!$name) $errors[] = 'Укажите название бренда.';
    if (!$slug) {
        $slug = mb_strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $slug = trim($slug, '-');
    }

    if (empty($errors)) {
        $chk = $db->prepare("SELECT id FROM brands WHERE slug=? AND id!=?");
        $chk->execute([$slug, $bid]);
        if ($chk->fetch()) $errors[] = 'Такой slug уже существует.';
    }

    if (empty($errors)) {
        if ($bid) {
            $db->prepare(
                "UPDATE brands SET name=?,slug=?,country=?,description=? WHERE id=?"
            )->execute([$name, $slug, $country?:null, $desc?:null, $bid]);
            flashMessage('success','Бренд обновлён.');
        } else {
            $db->prepare(
                "INSERT INTO brands (name,slug,country,description) VALUES (?,?,?,?)"
            )->execute([$name, $slug, $country?:null, $desc?:null]);
            flashMessage('success','Бренд добавлен.');
        }
        redirect(APP_URL.'/manager/brands.php');
    }
    $action = $bid ? 'edit' : 'new';
    $editId = $bid;
}

$editBrand = null;
if ($editId && $action === 'edit') {
    $s = $db->prepare("SELECT * FROM brands WHERE id=?");
    $s->execute([$editId]);
    $editBrand = $s->fetch();
}

$search  = trim($_GET['q'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$where   = ['is_active=1'];
$params  = [];
if ($search) { $where[] = '(name LIKE ? OR country LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
$whereSQL = 'WHERE '.implode(' AND ',$where);

$cntStmt = $db->prepare("SELECT COUNT(*) FROM brands $whereSQL");
$cntStmt->execute($params);
$total  = (int)$cntStmt->fetchColumn();
$pages  = max(1, (int)ceil($total/$perPage));
$offset = ($page-1)*$perPage;

$brandsStmt = $db->prepare("SELECT * FROM brands $whereSQL ORDER BY name ASC LIMIT $perPage OFFSET $offset");
$brandsStmt->execute($params);
$brands = $brandsStmt->fetchAll();

$pageTitle = 'Бренды';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/nav.php';
?>

<div class="az-admin-page">
<div class="az-admin-navbar">
  <a href="<?= APP_URL ?>/index.php" class="az-admin-logo">АВТО<span>ЗАПЧАСТЬ</span></a>
  <ul class="az-admin-nav-links">
    <li><a href="<?= APP_URL ?>/manager/brands.php?action=new" class="az-admin-btn az-admin-btn-primary az-admin-btn-sm">+ Добавить</a></li>
    <li><a href="<?= APP_URL ?>/auth/logout.php">Выйти</a></li>
  </ul>
</div>
<div class="dash-layout">
  <div class="dash-sidebar"><?php renderNav(); ?></div>
  <div class="dash-main">

    <?php if (in_array($action, ['new','edit'])): ?>
    <div class="dash-heading"><?= $action==='new' ? 'ДОБАВИТЬ БРЕНД' : 'РЕДАКТИРОВАТЬ БРЕНД' ?></div>

    <?php if (!empty($errors)): ?>
    <div class="az-alert az-alert-danger" style="margin-bottom:16px;"><?= implode('<br>', array_map('sanitize',$errors)) ?></div>
    <?php endif; ?>

    <div class="az-card" style="max-width:600px;">
      <div class="az-card-body">
        <form method="post" action="?action=<?= $action ?><?= $editId?'&id='.$editId:'' ?>">
          <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">
          <input type="hidden" name="action" value="<?= $action==='edit'?'edit':'add' ?>">
          <?php if ($editId): ?><input type="hidden" name="id" value="<?= $editId ?>"><?php endif; ?>

          <div class="az-grid-2">
            <div class="az-form-group">
              <label class="az-form-label">Название *</label>
              <input type="text" name="name" class="az-form-input" value="<?= sanitize($editBrand['name'] ?? '') ?>" required>
            </div>
            <div class="az-form-group">
              <label class="az-form-label">Slug</label>
              <input type="text" name="slug" class="az-form-input" value="<?= sanitize($editBrand['slug'] ?? '') ?>" placeholder="bosch">
            </div>
          </div>

          <div class="az-form-group">
            <label class="az-form-label">Страна производителя</label>
            <input type="text" name="country" class="az-form-input" value="<?= sanitize($editBrand['country'] ?? '') ?>" placeholder="Германия">
          </div>

          <div class="az-form-group">
            <label class="az-form-label">Описание</label>
            <textarea name="description" class="az-form-textarea" rows="3"><?= sanitize($editBrand['description'] ?? '') ?></textarea>
          </div>

          <div style="display:flex;gap:10px;">
            <button type="submit" class="az-admin-btn az-admin-btn-primary az-admin-btn-lg"><?= $action==='new' ? 'ДОБАВИТЬ' : 'СОХРАНИТЬ' ?></button>
            <a href="<?= APP_URL ?>/manager/brands.php" class="az-admin-btn az-admin-btn-outline az-admin-btn-lg">Отмена</a>
          </div>
        </form>
      </div>
    </div>

    <?php else: ?>
    <div class="dash-heading">БРЕНДЫ (<?= $total ?>)</div>

    <form method="get" action="" style="margin-bottom:14px;display:flex;gap:10px;">
      <input type="text" name="q" class="az-form-input" style="max-width:280px;" placeholder="Поиск по названию или стране" value="<?= sanitize($search) ?>">
      <button type="submit" class="az-admin-btn az-admin-btn-primary az-admin-btn-sm">Найти</button>
      <?php if ($search): ?><a href="?" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">Сбросить</a><?php endif; ?>
    </form>

    <div class="az-card">
      <div class="az-table-wrap">
        <table class="az-table">
          <thead><tr><th>ID</th><th>Название</th><th>Slug</th><th>Страна</th><th>Описание</th><th>Действия</th></tr></thead>
          <tbody>
            <?php foreach ($brands as $b): ?>
            <tr>
              <td style="font-family:monospace;color:var(--text-muted);"><?= $b['id'] ?></td>
              <td style="font-weight:500;"><?= sanitize($b['name']) ?></td>
              <td style="font-family:monospace;font-size:12px;color:var(--accent);"><?= sanitize($b['slug']) ?></td>
              <td style="font-size:12px;color:var(--text-muted);"><?= sanitize($b['country'] ?? '—') ?></td>
              <td style="font-size:12px;color:var(--text-muted);"><?= sanitize(truncate($b['description'] ?? '', 60)) ?></td>
              <td style="display:flex;gap:6px;">
                <a href="?action=edit&id=<?= $b['id'] ?>" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">Ред.</a>
                <form method="post" action="" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $b['id'] ?>">
                  <button type="submit" class="az-admin-btn az-admin-btn-danger az-admin-btn-sm" onclick="return confirm('Удалить бренд?')">Удалить</button>
                </form>
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
    <?php endif; ?>

  </div>
</div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
