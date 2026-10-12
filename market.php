<?php
require 'auth.php'; 
touchActivity();    
require 'db_connect.php';
require 'review_helpers.php';
require_once 'lang.php';

function categoryLabel($cat) {
    $map = ['Vegetables'=>'cat.vegetables','Fruits'=>'cat.fruits','Grains'=>'cat.grains','Dairy'=>'cat.dairy','Spices'=>'cat.spices','Other'=>'cat.other'];
    return isset($map[$cat]) ? t($map[$cat]) : htmlspecialchars($cat);
}

$sort = $_GET['sort'] ?? 'newest';
if ($sort === 'price_low') {
    $orderBy = 's.price_per_unit ASC';
} elseif ($sort === 'price_high') {
    $orderBy = 's.price_per_unit DESC';
} else {
    $orderBy = 's.created_at DESC';
}

$stmt = $pdo->prepare("SELECT s.*, u.name AS seller_name, u.business_name FROM storefront_listings s
                        JOIN users u ON s.buyer_id = u.id
                        WHERE s.stock_qty > 0
                        ORDER BY $orderBy");
$stmt->execute();
$products = $stmt->fetchAll();
$isConsumer = isset($_SESSION['role']) && $_SESSION['role'] === 'consumer';

$sellerRatings = getRatingsFor($pdo, 'storefront', array_column($products, 'buyer_id'));

$categoryCounts = [];
foreach ($products as $p) {
    $cat = $p['category'] ?: 'Other';
    $categoryCounts[$cat] = ($categoryCounts[$cat] ?? 0) + 1;
}
ksort($categoryCounts);

$favoriteIds = [];
if ($isConsumer) {
    $favStmt = $pdo->prepare("SELECT storefront_id FROM consumer_favorites WHERE consumer_id = ?");
    $favStmt->execute([$_SESSION['user_id']]);
    $favoriteIds = $favStmt->fetchAll(PDO::FETCH_COLUMN);
}

include 'header.php';
?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'stock'): ?>
  <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i><?= t('browse_crops.stock_error') ?></div>
<?php endif; ?>

<div class="hero-band furrow text-center">
  <span class="hero-eyebrow mx-auto"><i class="bi bi-basket2"></i> <?= t('market.eyebrow') ?></span>
  <h1 class="mx-auto"><?= t('market.title') ?></h1>
  <p class="mx-auto"><?= t('market.subtitle') ?></p>
  <?php if (count($products) > 0): ?>
  <div class="search-bar mt-3 mx-auto" style="max-width:420px;">
    <i class="bi bi-search"></i>
    <input type="text" id="marketSearch" placeholder="<?= t('market.search_ph') ?>">
  </div>
  <?php endif; ?>
</div>

<?php if (count($products) === 0): ?>
  <div class="empty-state">
    <i class="bi bi-basket"></i>
    <h5><?= t('market.empty_title') ?></h5>
    <p class="mb-0"><?= t('market.empty_sub') ?></p>
  </div>
<?php else: ?>

<div class="row">
  <div class="col-lg-3 mb-4">
    <div class="dash-panel">
      <div class="dash-panel-title"><span><i class="bi bi-funnel me-1"></i><?= t('market.categories') ?></span></div>
      <div class="filter-chip-row flex-column align-items-stretch gap-2" id="categoryFilters">
        <button type="button" class="filter-chip active text-start" data-category="all">
          <?= t('market.all_products') ?> <span class="float-end text-muted"><?= count($products) ?></span>
        </button>
        <?php foreach ($categoryCounts as $cat => $cnt): ?>
          <button type="button" class="filter-chip text-start" data-category="<?= htmlspecialchars(strtolower($cat)) ?>">
            <?= categoryLabel($cat) ?> <span class="float-end text-muted"><?= $cnt ?></span>
          </button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>


  <div class="col-lg-9">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <h5 class="mb-0" style="font-family:'Poppins',sans-serif;"><?= t('market.available_produce') ?></h5>
      <form method="GET" id="sortForm">
        <select name="sort" class="form-select form-select-sm" style="width:auto;" onchange="document.getElementById('sortForm').submit()">
          <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>><?= t('market.sort_newest') ?></option>
          <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>><?= t('market.sort_price_low') ?></option>
          <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>><?= t('market.sort_price_high') ?></option>
        </select>
      </form>
    </div>

    <div class="row" id="marketGrid">
      <?php foreach ($products as $p): ?>
        <?php $cat = $p['category'] ?: 'Other'; ?>
        <div class="col-md-6 col-xl-4 mb-4 market-item" data-category="<?= htmlspecialchars(strtolower($cat)) ?>" data-search="<?= htmlspecialchars(strtolower($p['product_name'] . ' ' . ($p['business_name'] ?: $p['seller_name']))) ?>">
          <div class="card product-card">
            <div class="product-image">
              <?php if (!empty($p['image_path'])): ?>
                <img src="<?= htmlspecialchars($p['image_path']) ?>" alt="<?= htmlspecialchars($p['product_name']) ?>">
              <?php else: ?>
                <i class="bi bi-basket3 placeholder-icon"></i>
              <?php endif; ?>
              <span class="category-badge"><?= categoryLabel($cat) ?></span>
              <?php if ($isConsumer): ?>
                <button type="button" class="favorite-btn js-fav-btn <?= in_array($p['id'], $favoriteIds) ? 'active' : '' ?>" data-id="<?= $p['id'] ?>">
                  <i class="bi <?= in_array($p['id'], $favoriteIds) ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                </button>
              <?php endif; ?>
            </div>
            <div class="product-top">
              <span class="pill pill-gold mb-2"><i class="bi bi-patch-check-fill"></i> <?= t('market.verified_seller') ?></span>
              <div class="product-name"><?= htmlspecialchars($p['product_name']) ?></div>
              <div class="product-meta"><i class="bi bi-shop me-1"></i><?= htmlspecialchars($p['business_name'] ?: $p['seller_name']) ?>
                <?php if (isset($sellerRatings[$p['buyer_id']])): ?>
                  &middot; <?= renderStars($sellerRatings[$p['buyer_id']]['avg'], $sellerRatings[$p['buyer_id']]['count']) ?>
                <?php endif; ?>
              </div>
            </div>
            <div class="product-body">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="product-price">৳<?= number_format($p['price_per_unit'], 2) ?> <small>/ <?= htmlspecialchars($p['unit_label'] ?: t('common.unit')) ?></small></div>
                <span class="pill pill-green"><i class="bi bi-box-seam"></i> <?= htmlspecialchars($p['stock_qty']) ?> <?= t('market.in_stock') ?></span>
              </div>

              <?php if ($isConsumer): ?>
                <div class="mt-auto js-cart-form" data-id="<?= $p['id'] ?>">
                  <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <div class="qty-stepper">
                      <button type="button" class="js-qty-minus">&minus;</button>
                      <input type="number" step="0.01" min="0.01" max="<?= $p['stock_qty'] ?>" value="1" class="js-qty-input">
                      <button type="button" class="js-qty-plus">&plus;</button>
                    </div>
                    <div class="text-end">
                      <div class="text-muted" style="font-size:0.75rem;"><?= t('common.total') ?></div>
                      <div class="line-total js-line-total" data-price="<?= $p['price_per_unit'] ?>">৳<?= number_format($p['price_per_unit'], 2) ?></div>
                    </div>
                  </div>
                  <button type="button" class="btn btn-success w-100 js-add-to-cart"><i class="bi bi-cart-plus me-1"></i><?= t('browse_crops.add_to_cart') ?></button>
                </div>
              <?php else: ?>
                <a href="login.php" class="btn btn-outline-success w-100 mt-auto"><i class="bi bi-box-arrow-in-right me-1"></i><?= t('market.login_as_consumer') ?></a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <p class="text-muted text-center d-none" id="marketEmptyMsg"><i class="bi bi-search me-1"></i><?= t('market.no_match') ?></p>
  </div>
</div>
<?php endif; ?>

<script>
const i18nAdded2 = <?= json_encode(t('common.added')) ?>;
const i18nCartError2 = <?= json_encode(t('common.cart_error')) ?>;
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
    fetch('cart_add.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'storefront_id=' + encodeURIComponent(box.dataset.id) + '&quantity=' + encodeURIComponent(input.value)
    })
    .then(r => r.json())
    .then(data => {
      if (data.ok) {
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>' + i18nAdded2;
        const badge = document.getElementById('cartBadge');
        if (badge) { badge.textContent = data.cartCount; badge.classList.remove('d-none'); }
        setTimeout(() => { btn.innerHTML = originalHtml; btn.disabled = false; }, 1200);
      } else {
        alert(data.error || i18nCartError2);
        btn.disabled = false;
      }
    })
    .catch(() => { btn.disabled = false; });
  });
});

const marketSearch = document.getElementById('marketSearch');
const marketItems = document.querySelectorAll('.market-item');
const marketEmptyMsg = document.getElementById('marketEmptyMsg');
const categoryChips = document.querySelectorAll('#categoryFilters .filter-chip');
let activeCategory = 'all';

function applyMarketFilters() {
  const term = (marketSearch?.value || '').toLowerCase().trim();
  let visible = 0;
  marketItems.forEach(item => {
    const matchesCategory = activeCategory === 'all' || item.dataset.category === activeCategory;
    const matchesSearch = !term || item.dataset.search.includes(term);
    const show = matchesCategory && matchesSearch;
    item.classList.toggle('d-none', !show);
    if (show) visible++;
  });
  if (marketEmptyMsg) marketEmptyMsg.classList.toggle('d-none', visible !== 0);
}

if (marketSearch) marketSearch.addEventListener('input', applyMarketFilters);
categoryChips.forEach(chip => {
  chip.addEventListener('click', () => {
    categoryChips.forEach(c => c.classList.remove('active'));
    chip.classList.add('active');
    activeCategory = chip.dataset.category;
    applyMarketFilters();
  });
});

document.querySelectorAll('.js-fav-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const id = btn.dataset.id;
    fetch('toggle_favorite.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'storefront_id=' + encodeURIComponent(id)
    })
    .then(r => r.json())
    .then(data => {
      if (!data.ok) return;
      const icon = btn.querySelector('i');
      if (data.favorited) {
        btn.classList.add('active');
        icon.classList.remove('bi-heart');
        icon.classList.add('bi-heart-fill');
      } else {
        btn.classList.remove('active');
        icon.classList.remove('bi-heart-fill');
        icon.classList.add('bi-heart');
      }
    })
    .catch(() => {});
  });
});
</script>

<?php include 'footer.php'; ?>
