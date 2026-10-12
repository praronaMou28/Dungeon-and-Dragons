<?php
require 'auth.php';
requireRole('buyer');
require 'db_connect.php';
require_once 'lang.php';

$group = $_GET['group'] ?? '';
if (!$group) {
    header("Location: buyer_dashboard.php");
    exit;
}

$stmt = $pdo->prepare("SELECT o.*, l.crop_name, l.image_path, l.price_per_kg, u.name AS farmer_name FROM orders o
                        JOIN crop_listings l ON o.listing_id = l.id
                        JOIN users u ON l.farmer_id = u.id
                        WHERE o.group_ref = ? AND o.buyer_id = ?
                        ORDER BY o.id ASC");
$stmt->execute([$group, $_SESSION['user_id']]);
$items = $stmt->fetchAll();

if (count($items) === 0) {
    header("Location: buyer_dashboard.php");
    exit;
}

$total = array_sum(array_map(fn($i) => $i['paid_amount'], $items));
$first = $items[0];

include 'header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-7">

    <div class="checkout-steps">
      <div class="checkout-step done"><span class="dot"><i class="bi bi-check"></i></span> <?= t('checkout.step_cart') ?></div>
      <div class="checkout-connector"></div>
      <div class="checkout-step done"><span class="dot"><i class="bi bi-check"></i></span> <?= t('checkout.step_delivery') ?></div>
      <div class="checkout-connector"></div>
      <div class="checkout-step active"><span class="dot">3</span> <?= t('checkout.step_done') ?></div>
    </div>

    <div class="card p-4 text-center mb-3">
      <div class="mx-auto mb-2" style="width:64px;height:64px;border-radius:50%;background:rgba(47,143,78,0.14);display:flex;align-items:center;justify-content:center;">
        <i class="bi bi-check-lg" style="font-size:2rem;color:var(--paddy);"></i>
      </div>
      <h4 style="font-family:'Poppins',sans-serif;"><?= t('order_confirmation_buyer.title') ?></h4>
      <p class="text-muted mb-1"><?= t('order_confirmation.order_ref') ?>: <strong><?= htmlspecialchars($group) ?></strong></p>
      <p class="text-muted mb-0">
        <?php if ($first['payment_status'] === 'cod'): ?>
          <?= t('order_confirmation_buyer.cod_note', ['amount' => number_format($total, 2)]) ?>
        <?php elseif ($first['payment_status'] === 'paid'): ?>
          <?= t('order_confirmation.paid_note', ['amount' => number_format($total, 2), 'method' => strtoupper($first['payment_method'])]) ?>
        <?php else: ?>
          <?= t('order_confirmation_buyer.unpaid_note', ['amount' => number_format($total, 2), 'method' => strtoupper($first['payment_method'])]) ?>
        <?php endif; ?>
      </p>
      <p class="text-muted mb-0" style="font-size:0.8rem;"><?= t('order_confirmation_buyer.pending_note') ?></p>
    </div>

    <div class="card p-4 mb-3">
      <h6 class="mb-3"><i class="bi bi-geo-alt me-1"></i><?= t('order_confirmation.delivering_to') ?></h6>
      <p class="mb-1"><?= htmlspecialchars($first['delivery_address']) ?></p>
      <p class="text-muted mb-0"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($first['delivery_phone']) ?><?= $first['delivery_note'] ? ' &middot; ' . htmlspecialchars($first['delivery_note']) : '' ?></p>
    </div>

    <div class="dash-panel mb-3">
      <div class="dash-panel-title"><span><?= t('order_confirmation.items') ?></span></div>
      <?php foreach ($items as $item): ?>
        <div class="tx-row">
          <?php if (!empty($item['image_path'])): ?>
            <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="" style="width:44px;height:44px;object-fit:cover;border-radius:8px;flex-shrink:0;">
          <?php else: ?>
            <div class="tx-icon"><i class="bi bi-flower1"></i></div>
          <?php endif; ?>
          <div class="tx-main">
            <div class="tx-title"><?= htmlspecialchars($item['crop_name']) ?></div>
            <div class="tx-sub"><i class="bi bi-person me-1"></i><?= htmlspecialchars($item['farmer_name']) ?> &middot; <?= htmlspecialchars($item['quantity_kg']) ?> kg &middot; ৳<?= number_format($item['paid_amount'], 2) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <a href="buyer_dashboard.php" class="btn btn-success w-100 py-2"><i class="bi bi-speedometer2 me-1"></i><?= t('order_confirmation.go_to_orders') ?></a>
  </div>
</div>

<?php include 'footer.php'; ?>
