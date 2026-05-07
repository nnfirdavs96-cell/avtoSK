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
        flashMessage('danger','CSRF error.'); redirect(APP_URL.'/manager/categories.php');
    }
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'delete') {
        $delId = (int)($_POST['id'] ?? 0);
        $db->prepare("UPDATE categories SET is_active=0 WHERE id=?")->execute([$delId]);
        flashMessage('success','Категория удалена.');
        redirect(APP_URL.'/manager/categories.php');
    }

    $name      = trim($_POST['name'] ?? '');
    $slug      = trim($_POST['slug'] ?? '');
    $parentId  = (int)($_POST['parent_id'] ?? 0) ?: null;
    $desc      = trim($_POST['description'] ?? '');
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $cid       = (int)($_POST['id'] ?? 0);

    if (!$name) $errors[] = 'Укажите название категории.';
    if (!$slug) {
        $slug = mb_strtolower(preg_replace('/[^a-z0-9]+/i', '-', transliterator_transliterate('Russian-Latin/BGN', $name)));
        $slug = trim($slug, '-');
    }

    if (empty($errors)) {
        $chk = $db->prepare("SELECT id FROM categories WHERE slug=? AND id!=?");
        $chk->execute([$slug, $cid]);
        if ($chk->fetch()) $errors[] = 'Такой slug уже существует.';
    }

    if (empty($errors)) {
        if ($cid) {
            $db->prepare(
                "UPDATE categories SET name=?,slug=?,parent_id=?,description=?,sort_order=? WHERE id=?"
            )->execute([$name, $slug, $parentId, $desc?:null, $sortOrder, $cid]);
            flashMessage('success','Категория обновлена.');
        } else {
            $db->prepare(
                "INSERT INTO categories (name,slug,parent_id,description,sort_order) VALUES (?,?,?,?,?)"
            )->execute([$name, $slug, $parentId, $desc?:null, $sortOrder]);
            flashMessage('success','Категория добавлена.');
        }
        redirect(APP_URL.'/manager/categories.php');
    }
    $action = $cid ? 'edit' : 'new';
    $editId = $cid;
}

$editCat = null;
if ($editId && $action === 'edit') {
    $s = $db->prepare("SELECT * FROM categories WHERE id=?");
    $s->execute([$editId]);
    $editCat = $s->fetch();
}

$allCats = $db->query("SELECT * FROM categories WHERE is_active=1 ORDER BY parent_id ASC, sort_order ASC, name ASC")->fetchAll();

$pageTitle = 'Категории';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/nav.php';
?>

<div class="az-admin-page">
<div class="az-admin-navbar">
  <a href="<?= APP_URL ?>/index.php" class="az-admin-logo">АВТО<span>ЗАПЧАСТЬ</span></a>
  <ul class="az-admin-nav-links">
    <li><a href="<?= APP_URL ?>/manager/categories.php?action=new" class="az-admin-btn az-admin-btn-primary az-admin-btn-sm">+ Добавить</a></li>
    <li><a href="<?= APP_URL ?>/auth/logout.php">Выйти</a></li>
  </ul>
</div>
<div class="dash-layout">
  <div class="dash-sidebar"><?php renderNav(); ?></div>
  <div class="dash-main">

    <?php if (in_array($action, ['new','edit'])): ?>
    <div class="dash-heading"><?= $action==='new' ? 'ДОБАВИТЬ КАТЕГОРИЮ' : 'РЕДАКТИРОВАТЬ КАТЕГОРИЮ' ?></div>

    <?php if (!empty($errors)): ?>
    <div class="az-alert az-alert-danger" style="margin-bottom:16px;"><?= implode('<br>', array_map('sanitize',$errors)) ?></div>
    <?php endif; ?>

    <div class="az-card" style="max-width:700px;">
      <div class="az-card-body">
        <form method="post" action="?action=<?= $action ?><?= $editId?'&id='.$editId:'' ?>">
          <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">
          <input type="hidden" name="action" value="<?= $action==='edit'?'edit':'add' ?>">
          <?php if ($editId): ?><input type="hidden" name="id" value="<?= $editId ?>"><?php endif; ?>

          <div class="az-grid-2">
            <div class="az-form-group">
              <label class="az-form-label">Название *</label>
              <input type="text" name="name" class="az-form-input" value="<?= sanitize($editCat['name'] ?? '') ?>" required>
            </div>
            <div class="az-form-group">
              <label class="az-form-label">Slug (URL-имя)</label>
              <input type="text" name="slug" class="az-form-input" value="<?= sanitize($editCat['slug'] ?? '') ?>" placeholder="auto-zapchasti">
            </div>
          </div>

          <div class="az-grid-2">
            <div class="az-form-group">
              <label class="az-form-label">Родительская категория</label>
              <select name="parent_id" class="az-form-select">
                <option value="">— Корневая —</option>
                <?php foreach ($allCats as $pc):
                  if ($editId && $pc['id'] == $editId) continue; ?>
                <option value="<?= $pc['id'] ?>" <?= ($editCat['parent_id'] ?? 0)==$pc['id']?'selected':'' ?>>
                  <?= sanitize($pc['name']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="az-form-group">
              <label class="az-form-label">Порядок сортировки</label>
              <input type="number" name="sort_order" class="az-form-input" min="0" value="<?= (int)($editCat['sort_order'] ?? 0) ?>">
            </div>
          </div>

          <div class="az-form-group">
            <label class="az-form-label">Описание</label>
            <textarea name="description" class="az-form-textarea" rows="3"><?= sanitize($editCat['description'] ?? '') ?></textarea>
          </div>

          <div style="display:flex;gap:10px;">
            <button type="submit" class="az-admin-btn az-admin-btn-primary az-admin-btn-lg"><?= $action==='new' ? 'ДОБАВИТЬ' : 'СОХРАНИТЬ' ?></button>
            <a href="<?= APP_URL ?>/manager/categories.php" class="az-admin-btn az-admin-btn-outline az-admin-btn-lg">Отмена</a>
          </div>
        </form>
      </div>
    </div>

    <?php else: ?>
    <div class="dash-heading">КАТЕГОРИИ (<?= count($allCats) ?>)</div>

    <div class="az-card">
      <div class="az-table-wrap">
        <table class="az-table">
          <thead><tr><th>ID</th><th>Название</th><th>Slug</th><th>Родитель</th><th>Порядок</th><th>Действия</th></tr></thead>
          <tbody>
            <?php
            $catMap = array_column($allCats, null, 'id');
            foreach ($allCats as $cat): ?>
            <tr>
              <td style="font-family:monospace;color:var(--text-muted);"><?= $cat['id'] ?></td>
              <td>
                <?php if ($cat['parent_id']): ?>
                <span style="color:var(--text-muted);margin-right:4px;">└</span>
                <?php endif; ?>
                <span style="font-weight:500;"><?= sanitize($cat['name']) ?></span>
              </td>
              <td style="font-family:monospace;font-size:12px;color:var(--text-muted);"><?= sanitize($cat['slug']) ?></td>
              <td style="font-size:12px;color:var(--text-muted);"><?= $cat['parent_id'] ? sanitize($catMap[$cat['parent_id']]['name'] ?? '—') : '—' ?></td>
              <td style="font-family:monospace;font-size:12px;"><?= $cat['sort_order'] ?></td>
              <td style="display:flex;gap:6px;">
                <a href="?action=edit&id=<?= $cat['id'] ?>" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">Ред.</a>
                <form method="post" action="" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                  <button type="submit" class="az-admin-btn az-admin-btn-danger az-admin-btn-sm" onclick="return confirm('Удалить категорию?')">Удалить</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
