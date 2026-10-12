<?php
require 'auth.php';
requireRole('consumer');
require 'db_connect.php';
require_once 'lang.php';

$stmt = $pdo->prepare("SELECT c.id AS cart_id, c.quantity, s.*, u.name AS seller_name, u.business_name FROM cart_items c
                        JOIN storefront_listings s ON c.storefront_id = s.id
                        JOIN users u ON s.buyer_id = u.id
                        WHERE c.consumer_id = ?
                        ORDER BY c.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll();

$total = array_sum(array_map(fn($i) => $i['quantity'] * $i['price_per_unit'], $cartItems));

include 'header.php';
?>

<div class="hero-band furrow">
  <span class="hero-eyebrow"><i class="bi bi-cart3"></i> <?= t('cart.eyebrow') ?></span>
  <h1><?= t('cart.title') ?></h1>
  <p><?= t('cart.subtitle') ?></p>
</div>

<?php if (count($cartItems) === 0): ?>
  <div class="empty-state">
    <i class="bi bi-cart-x"></i>
    <h5><?= t('cart.empty_title') ?></h5>
    <p class="mb-3"><?= t('cart.empty_sub') ?></p>
    <a href="market.php" class="btn btn-success"><i class="bi bi-basket me-1"></i><?= t('cart.browse_marketplace') ?></a>
  </div>
<?php else: ?>
<div class="row">
  <div class="col-lg-8">
    <div class="dash-panel" id="cartList">
      <?php foreach ($cartItems as $item): ?>
        <div class="tx-row cart-row" data-cart-id="<?= $item['cart_id'] ?>" data-price="<?= $item['price_per_unit'] ?>" data-stock="<?= $item['stock_qty'] ?>">
          <?php if (!empty($item['image_path'])): ?>
            <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="" style="width:56px;height:56px;object-fit:cover;border-radius:10px;flex-shrink:0;">
          <?php else: ?>
            <div class="tx-icon"><i class="bi bi-basket3"></i></div>
          <?php endif; ?>
          <div class="tx-main flex-grow-1">
            <div class="d-flex justify-content-between flex-wrap gap-2">
              <div>
                <div class="tx-title"><?= htmlspecialchars($item['product_name']) ?></div>
                <div class="tx-sub"><i class="bi bi-shop me-1"></i><?= htmlspecialchars($item['business_name'] ?: $item['seller_name']) ?> &middot; ৳<?= number_format($item['price_per_unit'], 2) ?> / <?= htmlspecialchars($item['unit_label'] ?: 'unit') ?></div>
              </div>
              <button type="button" class="btn btn-sm btn-outline-danger js-cart-remove" title="<?= t('common.remove') ?>"><i class="bi bi-trash"></i></button>
            </div>
            <div class="d-flex align-items-center justify-content-between mt-2">
              <div class="qty-stepper">
                <button type="button" class="js-qty-minus">&minus;</button>
                <input type="number" step="0.01" min="0.01" max="<?= $item['stock_qty'] ?>" value="<?= $item['quantity'] ?>" class="js-qty-input">
                <button type="button" class="js-qty-plus">&plus;</button>
              </div>
              <div class="line-total js-line-total">৳<?= number_format($item['quantity'] * $item['price_per_unit'], 2) ?></div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="dash-panel">
      <div class="dash-panel-title"><span><?= t('cart.order_summary') ?></span></div>
      <div class="order-summary mb-3">
        <div class="row-line"><span><?= t('cart.items') ?></span><span id="cartItemCount"><?= count($cartItems) ?></span></div>
        <div class="row-total"><span><?= t('common.total') ?></span><span id="cartGrandTotal">৳<?= number_format($total, 2) ?></span></div>
      </div>
      <a href="checkout_consumer.php" class="btn btn-success w-100 py-2" id="checkoutBtn"><i class="bi bi-bag-check me-1"></i><?= t('cart.checkout') ?></a>
      <a href="market.php" class="btn btn-outline-success w-100 mt-2"><i class="bi bi-arrow-left me-1"></i><?= t('cart.continue_shopping') ?></a>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
function recomputeGrandTotal() {
  let total = 0, count = 0;
  document.querySelectorAll('.cart-row').forEach(row => {
    const qty = parseFloat(row.querySelector('.js-qty-input').value) || 0;
    const price = parseFloat(row.dataset.price);
    total += qty * price;
    count++;
  });
  document.getElementById('cartGrandTotal').textContent = '৳' + total.toFixed(2);
  document.getElementById('cartItemCount').textContent = count;
  const checkoutBtn = document.getElementById('checkoutBtn');
  if (count === 0 && checkoutBtn) {
    window.location.reload();
  }
}

document.querySelectorAll('.cart-row').forEach(row => {
  const cartId = row.dataset.cartId;
  const input = row.querySelector('.js-qty-input');
  const max = parseFloat(row.dataset.stock);
  const price = parseFloat(row.dataset.price);
  const lineTotal = row.querySelector('.js-line-total');

  function pushUpdate(qty) {
    fetch('cart_update.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'cart_id=' + cartId + '&action=update&quantity=' + qty
    }).then(r => r.json()).then(data => {
      if (data.ok) recomputeGrandTotal();
    });
  }

  function updateLocal() {
    let val = parseFloat(input.value) || 0;
    if (val < 0.01) val = 0.01;
    if (val > max) val = max;
    input.value = val;
    lineTotal.textContent = '৳' + (val * price).toFixed(2);
  }

  row.querySelector('.js-qty-minus').addEventListener('click', () => {
    input.value = Math.max(0.01, (parseFloat(input.value) || 1) - 1);
    updateLocal();
    pushUpdate(input.value);
  });
  row.querySelector('.js-qty-plus').addEventListener('click', () => {
    input.value = Math.min(max, (parseFloat(input.value) || 0) + 1);
    updateLocal();
    pushUpdate(input.value);
  });
  input.addEventListener('change', () => { updateLocal(); pushUpdate(input.value); });

  row.querySelector('.js-cart-remove').addEventListener('click', () => {
    fetch('cart_update.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'cart_id=' + cartId + '&action=remove'
    }).then(r => r.json()).then(data => {
      if (data.ok) {
        row.remove();
        recomputeGrandTotal();
      }
    });
  });
});
</script>

<?php include 'footer.php'; ?>
