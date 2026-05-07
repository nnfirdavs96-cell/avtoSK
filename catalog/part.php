<?php
require_once dirname(__DIR__) . '/config/config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { flashMessage('danger','Товар не найден.'); redirect(APP_URL.'/catalog/index.php'); }

$db   = getDB();
$stmt = $db->prepare(
    "SELECT p.*, b.name AS brand_name, b.country AS brand_country, c.name AS category_name, c.slug AS category_slug
     FROM parts p
     LEFT JOIN brands b ON b.id=p.brand_id
     LEFT JOIN categories c ON c.id=p.category_id
     WHERE p.id=? AND p.is_active=1"
);
$stmt->execute([$id]);
$part = $stmt->fetch();
if (!$part) { flashMessage('danger','Товар не найден.'); redirect(APP_URL.'/catalog/index.php'); }

$relStmt = $db->prepare(
    "SELECT p.*, b.name AS brand_name
     FROM parts p LEFT JOIN brands b ON b.id=p.brand_id
     WHERE p.is_active=1 AND p.id!=? AND (p.category_id=? OR p.brand_id=?)
     LIMIT 4"
);
$relStmt->execute([$id, $part['category_id'], $part['brand_id']]);
$related = $relStmt->fetchAll();

$stock      = getStockStatus((int)$part['stock']);
$warehouse  = getWarehouseStock($part['part_number']);
$pageTitle  = sanitize($part['name']);
$activeCur  = getCurrentCurrency();

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="container az-part-detail">
  <!-- Breadcrumb -->
  <div class="az-breadcrumb">
    <a href="<?= APP_URL ?>/index.php">Главная</a><span>/</span>
    <a href="<?= APP_URL ?>/catalog/index.php">Каталог</a><span>/</span>
    <a href="<?= APP_URL ?>/catalog/index.php?category=<?= sanitize($part['category_slug']) ?>"><?= sanitize($part['category_name']) ?></a><span>/</span>
    <span><?= sanitize($part['part_number']) ?></span>
  </div>

  <div class="az-part-detail-grid">
    <!-- Image -->
    <div>
      <div class="az-part-img-box">
        <svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="#ddd" stroke-width="0.5">
          <rect x="2" y="7" width="20" height="10" rx="1"/>
          <path d="M8 7V5M16 7V5M4 12h2M18 12h2M8 12h8"/>
        </svg>
        <span class="az-part-number-big"><?= sanitize($part['part_number']) ?></span>
      </div>
    </div>

    <!-- Info -->
    <div>
      <div style="font-size:12px;text-transform:uppercase;letter-spacing:.08em;color:var(--az-primary);margin-bottom:6px;">
        <a href="<?= APP_URL ?>/catalog/index.php?category=<?= sanitize($part['category_slug']) ?>" style="color:inherit;"><?= sanitize($part['category_name']) ?></a>
      </div>
      <h1 style="font-size:1.6rem;font-weight:800;color:#333;margin-bottom:10px;"><?= sanitize($part['name']) ?></h1>
      <div class="d-flex align-items-center gap-10" style="margin-bottom:16px;flex-wrap:wrap;">
        <span class="az-badge" style="background:#eee;color:#555;font-size:12px;"><?= sanitize($part['brand_name']) ?></span>
        <span class="az-badge az-badge-<?= $stock['class'] ?>"><?= $stock['label'] ?></span>
        <?php if ($part['stock'] > 0): ?>
        <span style="font-size:12px;color:#999;"><?= (int)$part['stock'] ?> шт. на складе</span>
        <?php endif; ?>
      </div>

      <!-- Specs -->
      <div class="az-part-spec-grid">
        <div class="az-part-spec-item">
          <div class="az-part-spec-label">Номер детали</div>
          <div class="az-part-spec-val" style="color:var(--az-primary);"><?= sanitize($part['part_number']) ?></div>
        </div>
        <div class="az-part-spec-item">
          <div class="az-part-spec-label">Производитель</div>
          <div class="az-part-spec-val"><?= sanitize($part['brand_name']) ?></div>
        </div>
        <div class="az-part-spec-item">
          <div class="az-part-spec-label">Страна</div>
          <div class="az-part-spec-val"><?= sanitize($part['brand_country'] ?? '—') ?></div>
        </div>
        <div class="az-part-spec-item">
          <div class="az-part-spec-label">Категория</div>
          <div class="az-part-spec-val"><?= sanitize($part['category_name']) ?></div>
        </div>
        <?php if ($part['weight']): ?>
        <div class="az-part-spec-item">
          <div class="az-part-spec-label">Вес</div>
          <div class="az-part-spec-val"><?= sanitize($part['weight']) ?> кг</div>
        </div>
        <?php endif; ?>
        <?php if ($part['dimensions']): ?>
        <div class="az-part-spec-item">
          <div class="az-part-spec-label">Размеры (мм)</div>
          <div class="az-part-spec-val"><?= sanitize($part['dimensions']) ?></div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Warehouse stock -->
      <?php if ($warehouse): ?>
      <div class="az-warehouse-box">
        <div class="label">🏭 Склад Москва</div>
        <div class="info">
          Остаток: <strong><?= (int)$warehouse['warehouse_stock'] ?> шт.</strong>
          <?php if ($warehouse['warehouse_price']): ?>
          · Цена склада: <strong><?= formatPriceInCurrency((float)$warehouse['warehouse_price']) ?></strong>
          <?php endif; ?>
          <?php if ($warehouse['warehouse_eta']): ?>
          · Срок поставки: <strong><?= sanitize($warehouse['warehouse_eta']) ?></strong>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Price -->
      <div class="az-part-price">
        <?= formatPriceInCurrency((float)$part['price']) ?>
        <?php if ($activeCur !== 'RUB'): ?>
        <span class="az-price-converted">(<?= formatPrice($part['price']) ?>)</span>
        <?php endif; ?>
      </div>

      <?php if (isLoggedIn()): ?>
        <?php if ($part['stock'] > 0): ?>
        <div class="az-add-cart-form">
          <input type="number" id="qty-input" class="az-input az-qty-input" value="1" min="1" max="<?= min((int)$part['stock'],99) ?>">
          <button class="az-btn az-btn-primary" data-add-cart="<?= (int)$part['id'] ?>" id="add-cart-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
            В корзину
          </button>
        </div>
        <?php else: ?>
        <div class="az-alert az-alert-warning" style="margin-bottom:12px;">Товар временно отсутствует</div>
        <?php endif; ?>
      <?php else: ?>
      <a href="<?= APP_URL ?>/auth/login.php" class="az-btn az-btn-primary" style="font-size:15px;padding:12px 24px;">
        Войдите для заказа
      </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Description -->
  <?php if ($part['description']): ?>
  <div style="background:#fff;border:1px solid var(--az-border);border-radius:4px;padding:24px;margin-bottom:40px;">
    <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid #f0f0f0;">Описание</h3>
    <p style="color:#555;font-size:14px;line-height:1.8;"><?= nl2br(sanitize($part['description'])) ?></p>
  </div>
  <?php endif; ?>

  <!-- Related -->
  <?php if (!empty($related)): ?>
  <div>
    <h3 style="font-size:1.2rem;font-weight:700;margin-bottom:16px;color:#333;">Похожие товары</h3>
    <div class="az-shop-grid-4">
      <?php foreach ($related as $rel):
        $rs = getStockStatus((int)$rel['stock']);
      ?>
      <div class="az-part-card">
        <div class="az-part-card-img">
          <a href="<?= APP_URL ?>/catalog/part.php?id=<?= $rel['id'] ?>">
            <div class="az-part-card-img-placeholder">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1"><rect x="2" y="7" width="20" height="10" rx="1"/></svg>
            </div>
          </a>
          <span class="az-part-number-badge"><?= sanitize($rel['part_number']) ?></span>
        </div>
        <div class="az-part-card-body">
          <div class="az-part-card-brand"><?= sanitize($rel['brand_name']) ?></div>
          <div class="az-part-card-name"><a href="<?= APP_URL ?>/catalog/part.php?id=<?= $rel['id'] ?>"><?= sanitize(truncate($rel['name'],55)) ?></a></div>
          <div class="az-part-card-price"><?= formatPriceInCurrency((float)$rel['price']) ?></div>
        </div>
        <div class="az-part-card-footer">
          <?php if (isLoggedIn()): ?>
          <button class="az-btn az-btn-primary az-btn-sm az-btn-block" data-add-cart="<?= $rel['id'] ?>">В корзину</button>
          <?php else: ?>
          <a href="<?= APP_URL ?>/auth/login.php" class="az-btn az-btn-outline az-btn-sm az-btn-block">Войти</a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
document.getElementById('qty-input')?.addEventListener('change', function() {
  const btn = document.getElementById('add-cart-btn');
  if (btn) btn.dataset.qty = this.value;
});
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
