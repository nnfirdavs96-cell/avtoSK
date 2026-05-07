<?php
require_once dirname(__DIR__) . '/config/config.php';
requireRole(['manager', 'superadmin']);

$db     = getDB();
$csrf   = generateCsrfToken();
$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);
$errors = [];

$brands     = getBrands();
$categories = getCategories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        flashMessage('danger','CSRF error.'); redirect(APP_URL.'/manager/parts.php');
    }
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'delete') {
        $delId = (int)($_POST['id'] ?? 0);
        $db->prepare("UPDATE parts SET is_active=0 WHERE id=?")->execute([$delId]);
        flashMessage('success','Товар удалён.');
        redirect(APP_URL.'/manager/parts.php');
    }

    $pnum   = trim($_POST['part_number'] ?? '');
    $name   = trim($_POST['name'] ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $brand  = (int)($_POST['brand_id'] ?? 0);
    $cat    = (int)($_POST['category_id'] ?? 0);
    $price  = (float)str_replace(',','.',$_POST['price'] ?? 0);
    $stock  = (int)($_POST['stock'] ?? 0);
    $weight = $_POST['weight'] ? (float)str_replace(',','.',$_POST['weight']) : null;
    $dims   = trim($_POST['dimensions'] ?? '');
    $pid    = (int)($_POST['id'] ?? 0);

    if (!$pnum)     $errors[] = 'Укажите номер детали.';
    if (!$name)     $errors[] = 'Укажите название.';
    if (!$brand)    $errors[] = 'Выберите бренд.';
    if (!$cat)      $errors[] = 'Выберите категорию.';
    if ($price <= 0)$errors[] = 'Укажите корректную цену.';

    if (empty($errors)) {
        $chk = $db->prepare("SELECT id FROM parts WHERE part_number=? AND id!=?");
        $chk->execute([$pnum, $pid]);
        if ($chk->fetch()) $errors[] = 'Такой артикул уже существует.';
    }

    if (empty($errors)) {
        if ($pid) {
            $db->prepare(
                "UPDATE parts SET part_number=?,name=?,description=?,brand_id=?,category_id=?,price=?,stock=?,weight=?,dimensions=?,updated_at=NOW() WHERE id=?"
            )->execute([$pnum,$name,$desc?:null,$brand,$cat,$price,$stock,$weight,$dims?:null,$pid]);
            flashMessage('success','Товар обновлён.');
        } else {
            $db->prepare(
                "INSERT INTO parts (part_number,name,description,brand_id,category_id,price,stock,weight,dimensions,images,created_by) VALUES (?,?,?,?,?,?,?,?,?,'[]',?)"
            )->execute([$pnum,$name,$desc?:null,$brand,$cat,$price,$stock,$weight,$dims?:null,$_SESSION['user_id']]);
            flashMessage('success','Товар добавлен.');
        }
        redirect(APP_URL.'/manager/parts.php');
    }
    $action = $pid ? 'edit' : 'new';
    $editId = $pid;
}

$editPart = null;
if ($editId && $action === 'edit') {
    $s = $db->prepare("SELECT * FROM parts WHERE id=?");
    $s->execute([$editId]);
    $editPart = $s->fetch();
}

// Список + поиск
$search  = trim($_GET['q'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$where   = ['p.is_active=1'];
$params  = [];
if ($search) { $where[] = '(p.part_number LIKE ? OR p.name LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
$whereSQL = 'WHERE '.implode(' AND ',$where);

$cntStmt = $db->prepare("SELECT COUNT(*) FROM parts p $whereSQL");
$cntStmt->execute($params);
$total  = (int)$cntStmt->fetchColumn();
$pages  = max(1, (int)ceil($total/$perPage));
$offset = ($page-1)*$perPage;

$partsStmt = $db->prepare(
    "SELECT p.*, b.name AS brand_name, c.name AS cat_name
     FROM parts p LEFT JOIN brands b ON b.id=p.brand_id LEFT JOIN categories c ON c.id=p.category_id
     $whereSQL ORDER BY p.updated_at DESC LIMIT $perPage OFFSET $offset"
);
$partsStmt->execute($params);
$parts = $partsStmt->fetchAll();

$pageTitle = 'Управление товарами';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/nav.php';
?>

<div class="az-admin-page">
<div class="az-admin-navbar">
  <a href="<?= APP_URL ?>/index.php" class="az-admin-logo">АВТО<span>ЗАПЧАСТЬ</span></a>
  <ul class="az-admin-nav-links">
    <li><a href="<?= APP_URL ?>/manager/parts.php?action=new" class="az-admin-btn az-admin-btn-primary az-admin-btn-sm">+ Добавить</a></li>
    <li><a href="<?= APP_URL ?>/auth/logout.php">Выйти</a></li>
  </ul>
</div>
<div class="dash-layout">
  <div class="dash-sidebar"><?php renderNav(); ?></div>
  <div class="dash-main">

    <?php if (in_array($action, ['new','edit'])): ?>
    <!-- Форма добавления/редактирования -->
    <div class="dash-heading"><?= $action==='new' ? 'ДОБАВИТЬ ТОВАР' : 'РЕДАКТИРОВАТЬ ТОВАР' ?></div>

    <?php if (!empty($errors)): ?>
    <div class="az-alert az-alert-danger" style="margin-bottom:16px;"><?= implode('<br>', array_map('sanitize',$errors)) ?></div>
    <?php endif; ?>

    <div class="az-card" style="max-width:800px;">
      <div class="az-card-body">
        <form method="post" action="?action=<?= $action ?><?= $editId?'&id='.$editId:'' ?>">
          <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">
          <input type="hidden" name="action" value="<?= $action==='edit'?'edit':'add' ?>">
          <?php if ($editId): ?><input type="hidden" name="id" value="<?= $editId ?>"><?php endif; ?>

          <div class="az-grid-2">
            <div class="az-form-group">
              <label class="az-form-label">Номер детали (артикул) *</label>
              <input type="text" name="part_number" class="az-form-input" value="<?= sanitize($editPart['part_number'] ?? '') ?>" placeholder="BKR6EK" required>
            </div>
            <div class="az-form-group">
              <label class="az-form-label">Цена (₽) *</label>
              <input type="number" name="price" class="az-form-input" step="0.01" min="0" value="<?= $editPart['price'] ?? '' ?>" required>
            </div>
          </div>

          <div class="az-form-group">
            <label class="az-form-label">Наименование *</label>
            <input type="text" name="name" class="az-form-input" value="<?= sanitize($editPart['name'] ?? '') ?>" required>
          </div>

          <div class="az-grid-2">
            <div class="az-form-group">
              <label class="az-form-label">Бренд *</label>
              <select name="brand_id" class="az-form-select" required>
                <option value="">— Выбрать —</option>
                <?php foreach ($brands as $b): ?>
                <option value="<?= $b['id'] ?>" <?= ($editPart['brand_id'] ?? 0)==$b['id']?'selected':'' ?>><?= sanitize($b['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="az-form-group">
              <label class="az-form-label">Категория *</label>
              <select name="category_id" class="az-form-select" required>
                <option value="">— Выбрать —</option>
                <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($editPart['category_id'] ?? 0)==$c['id']?'selected':'' ?>>
                  <?= $c['parent_id'] ? '└ ' : '' ?><?= sanitize($c['name']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="az-grid-2">
            <div class="az-form-group">
              <label class="az-form-label">Остаток (шт.)</label>
              <input type="number" name="stock" class="az-form-input" min="0" value="<?= (int)($editPart['stock'] ?? 0) ?>">
            </div>
            <div class="az-form-group">
              <label class="az-form-label">Вес (кг)</label>
              <input type="number" name="weight" class="az-form-input" step="0.001" min="0" value="<?= $editPart['weight'] ?? '' ?>" placeholder="0.500">
            </div>
          </div>

          <div class="az-form-group">
            <label class="az-form-label">Размеры (LxWxH мм)</label>
            <input type="text" name="dimensions" class="az-form-input" value="<?= sanitize($editPart['dimensions'] ?? '') ?>" placeholder="100x50x30">
          </div>

          <div class="az-form-group">
            <label class="az-form-label">Описание</label>
            <textarea name="description" class="az-form-textarea" rows="4"><?= sanitize($editPart['description'] ?? '') ?></textarea>
          </div>

          <div style="display:flex;gap:10px;">
            <button type="submit" class="az-admin-btn az-admin-btn-primary az-admin-btn-lg"><?= $action==='new' ? 'ДОБАВИТЬ ТОВАР' : 'СОХРАНИТЬ' ?></button>
            <a href="<?= APP_URL ?>/manager/parts.php" class="az-admin-btn az-admin-btn-outline az-admin-btn-lg">Отмена</a>
          </div>
        </form>
      </div>
    </div>

    <?php else: ?>
    <!-- Список товаров -->
    <div class="dash-heading">ТОВАРЫ (<?= $total ?>)</div>

    <form method="get" action="" style="margin-bottom:14px;display:flex;gap:10px;">
      <input type="text" name="q" class="az-form-input" style="max-width:320px;" placeholder="Поиск по артикулу или названию" value="<?= sanitize($search) ?>">
      <button type="submit" class="az-admin-btn az-admin-btn-primary az-admin-btn-sm">Найти</button>
      <?php if ($search): ?><a href="?" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">Сбросить</a><?php endif; ?>
    </form>

    <div class="az-card">
      <div class="az-table-wrap">
        <table class="az-table">
          <thead><tr><th>Артикул</th><th>Наименование</th><th>Бренд</th><th>Категория</th><th style="text-align:right;">Цена</th><th style="text-align:center;">Остаток</th><th>Действия</th></tr></thead>
          <tbody>
            <?php foreach ($parts as $p): ?>
            <tr>
              <td><span style="font-family:monospace;color:var(--accent);font-size:12px;"><?= sanitize($p['part_number']) ?></span></td>
              <td style="font-size:13px;"><?= sanitize(truncate($p['name'],50)) ?></td>
              <td style="font-size:12px;color:var(--text-muted);"><?= sanitize($p['brand_name']) ?></td>
              <td style="font-size:12px;color:var(--text-muted);"><?= sanitize($p['cat_name']) ?></td>
              <td style="text-align:right;font-family:monospace;color:var(--accent);"><?= formatPrice($p['price']) ?></td>
              <td style="text-align:center;">
                <span style="font-family:monospace;font-size:12px;color:<?= $p['stock']<=0?'var(--danger)':($p['stock']<=5?'var(--warning)':'var(--success)') ?>;"><?= $p['stock'] ?></span>
              </td>
              <td style="display:flex;gap:6px;">
                <a href="?action=edit&id=<?= $p['id'] ?>" class="az-admin-btn az-admin-btn-outline az-admin-btn-sm">Ред.</a>
                <form method="post" action="" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $p['id'] ?>">
                  <button type="submit" class="az-admin-btn az-admin-btn-danger az-admin-btn-sm" onclick="return confirm('Удалить товар?')">Удалить</button>
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
