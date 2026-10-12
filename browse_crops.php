<?php
require 'auth.php';
requireRole('buyer');
require 'db_connect.php';
require 'review_helpers.php';
require_once 'lang.php';

$stmtV = $pdo->prepare("SELECT status FROM verified_buyers WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmtV->execute([$_SESSION['user_id']]);
$status = $stmtV->fetchColumn();

if ($status !== 'approved') {
    header("Location: buyer_dashboard.php");
    exit;
}

$stmt = $pdo->prepare("SELECT c.*, u.name AS farmer_name, u.district, u.upazila FROM crop_listings c
                        JOIN users u ON c.farmer_id = u.id
                        WHERE c.status = 'available' AND c.quantity_kg > 0
                        ORDER BY c.created_at DESC");
$stmt->execute();
$listings = $stmt->fetchAll();

$farmerRatings = getRatingsFor($pdo, 'crop', array_column($listings, 'farmer_id'));

include 'header.php';
?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'stock'): ?>
  <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i><?= t('browse_crops.stock_error') ?></div>
<?php endif; ?>

<div class="hero-band furrow">
  <span class="hero-eyebrow"><i class="bi bi-flower1"></i> <?= t('browse_crops.eyebrow') ?></span>
  <h1><?= t('browse_crops.title') ?></h1>
  <p><?= t('browse_crops.subtitle') ?></p>
  <?php if (count($listings) > 0): ?>
  <div class="search-bar mt-3" style="max-width:420px;">
    <i class="bi bi-search"></i>
    <input type="text" id="cropSearch" placeholder="<?= t('browse_crops.search_ph') ?>">
  </div>
  <?php endif; ?>
</div>

<?php if (count($listings) === 0): ?>
  <div class="empty-state">
    <i class="bi bi-clipboard-x"></i>
    <h5><?= t('browse_crops.empty_title') ?></h5>
    <p class="mb-0"><?= t('browse_crops.empty_sub') ?></p>
  </div>
<?php else: ?>
<div class="row" id="cropGrid">
  <?php foreach ($listings as $l): ?>
    <div class="col-md-4 mb-4 crop-item" data-search="<?= htmlspecialchars(strtolower($l['crop_name'] . ' ' . $l['farmer_name'] . ' ' . $l['upazila'] . ' ' . $l['district'])) ?>">
      <div class="card product-card">
        <div class="product-image">
          <?php if (!empty($l['image_path'])): ?>
            <img src="<?= htmlspecialchars($l['image_path']) ?>" alt="<?= htmlspecialchars($l['crop_name']) ?>">
          <?php else: ?>
            <i class="bi bi-flower1 placeholder-icon"></i>
          <?php endif; ?>
        </div>
        <div class="product-top">
          <span class="pill pill-clay mb-2"><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($l['upazila']) ?>, <?= htmlspecialchars($l['district']) ?></span>
          <div class="product-name"><?= htmlspecialchars($l['crop_name']) ?></div>
          <div class="product-meta"><i class="bi bi-person me-1"></i><?= htmlspecialchars($l['farmer_name']) ?>
            <?php if (isset($farmerRatings[$l['farmer_id']])): ?>
              &middot; <?= renderStars($farmerRatings[$l['farmer_id']]['avg'], $farmerRatings[$l['farmer_id']]['count']) ?>
            <?php endif; ?>
          </div>
        </div>
        <div class="product-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="product-price">৳<?= number_format($l['price_per_kg'], 2) ?> <small>/ kg</small></div>
            <span class="pill pill-green"><i class="bi bi-box-seam"></i> <?= htmlspecialchars($l['quantity_kg']) ?> <?= t('browse_crops.kg_avail') ?></span>
          </div>

          <div class="mt-auto js-cart-form" data-id="<?= $l['id'] ?>">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
              <div class="qty-stepper">
                <button type="button" class="js-qty-minus">&minus;</button>
                <input type="number" step="0.01" min="0.01" max="<?= $l['quantity_kg'] ?>" value="1" class="js-qty-input">
                <button type="button" class="js-qty-plus">&plus;</button>
              </div>
              <div class="text-end">
                <div class="text-muted" style="font-size:0.75rem;"><?= t('common.total') ?></div>
                <div class="line-total js-line-total" data-price="<?= $l['price_per_kg'] ?>">৳<?= number_format($l['price_per_kg'], 2) ?></div>
              </div>
            </div>
            <button type="button" class="btn btn-success w-100 js-add-to-cart"><i class="bi bi-cart-plus me-1"></i><?= t('browse_crops.add_to_cart') ?></button>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
const i18nAdded = <?= json_encode(t('common.added')) ?>;
const i18nCartError = <?= json_encode(t('common.cart_error')) ?>;
document.querySelectorAll('.js-cart-form').forEach(box => {
  const input = box.querySelector('.js-qty-input');
  const totalEl = box.querySelector('.js-line-total');
  const price = parseFloat(totalEl.dataset.price);
  const max = parseFloat(input.max);

  function updateTotal() {
    let val = parseFloat(input.value) || 0;
    if (val < 0.01) val = 0.01;
    if (val > max) val = max;
    input.value = val;
    totalEl.textContent = '৳' + (val * price).toFixed(2);
  }
  box.querySelector('.js-qty-minus').addEventListener('click', () => { input.value = Math.max(0.01, (parseFloat(input.value) || 1) - 1); updateTotal(); });
  box.querySelector('.js-qty-plus').addEventListener('click', () => { input.value = Math.min(max, (parseFloat(input.value) || 0) + 1); updateTotal(); });
  input.addEventListener('input', updateTotal);

  box.querySelector('.js-add-to-cart').addEventListener('click', function() {
    const btn = this;
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    fetch('crop_cart_add.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'listing_id=' + encodeURIComponent(box.dataset.id) + '&quantity=' + encodeURIComponent(input.value)
    })
    .then(r => r.json())
    .then(data => {
      if (data.ok) {
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>' + i18nAdded;
        const badge = document.getElementById('cropCartBadge');
        if (badge) { badge.textContent = data.cartCount; badge.classList.remove('d-none'); }
        setTimeout(() => { btn.innerHTML = originalHtml; btn.disabled = false; }, 1200);
      } else {
        alert(data.error || i18nCartError);
        btn.disabled = false;
      }
    })
    .catch(() => { btn.disabled = false; });
  });
});

const cropSearch = document.getElementById('cropSearch');
if (cropSearch) {
  cropSearch.addEventListener('input', () => {
    const term = cropSearch.value.toLowerCase().trim();
    document.querySelectorAll('.crop-item').forEach(item => {
      item.classList.toggle('d-none', !!term && !item.dataset.search.includes(term));
    });
  });
}
</script>

<?php include 'footer.php'; ?>
