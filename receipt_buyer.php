<?php
require 'auth.php';
requireRole('buyer');
require 'db_connect.php';
require_once 'lang.php';

function orderStatusLabel($status) {
    $map = ['pending'=>'order_status.pending','accepted'=>'fulfillment.accepted','out_for_delivery'=>'fulfillment.out_for_delivery','completed'=>'fulfillment.completed','rejected'=>'order_status.rejected','cancelled'=>'order_status.cancelled'];
    return isset($map[$status]) ? t($map[$status]) : htmlspecialchars($status);
}

$order_id = $_GET['order_id'] ?? null;
if (!$order_id) {
    header("Location: buyer_dashboard.php");
    exit;
}

$stmt = $pdo->prepare("SELECT o.*, c.crop_name, c.price_per_kg, u.name AS farmer_name FROM orders o
                        JOIN crop_listings c ON o.listing_id = c.id
                        JOIN users u ON c.farmer_id = u.id
                        WHERE o.id = ? AND o.buyer_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: buyer_dashboard.php");
    exit;
}

$total = $order['paid_amount'] ?? ($order['quantity_kg'] * $order['price_per_kg']);

include 'header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card receipt-card p-4">
      <div class="text-center mb-3">
        <?php if ($order['payment_status'] === 'paid'): ?>
          <i class="bi bi-check-circle-fill text-success" style="font-size:2.5rem;"></i>
          <h3 class="page-title mt-2 mb-0"><?= t('receipt.payment_successful') ?></h3>
        <?php else: ?>
          <i class="bi bi-hourglass-split text-warning" style="font-size:2.5rem;"></i>
          <h3 class="page-title mt-2 mb-0"><?= t('receipt.payment_pending') ?></h3>
        <?php endif; ?>
      </div>

      <table class="table table-borderless mb-0">
        <tr><td class="text-muted"><?= t('receipt.order_id') ?></td><td class="text-end">#<?= (int)$order['id'] ?></td></tr>
        <tr><td class="text-muted"><?= t('col.crop') ?></td><td class="text-end"><?= htmlspecialchars($order['crop_name']) ?></td></tr>
        <tr><td class="text-muted"><?= t('role.farmer') ?></td><td class="text-end"><?= htmlspecialchars($order['farmer_name']) ?></td></tr>
        <tr><td class="text-muted"><?= t('col.quantity') ?></td><td class="text-end"><?= htmlspecialchars($order['quantity_kg']) ?> kg</td></tr>
        <tr><td class="text-muted"><?= t('receipt.price_per_kg') ?></td><td class="text-end">৳<?= number_format($order['price_per_kg'], 2) ?></td></tr>
        <?php if ($order['payment_method']): ?>
        <tr><td class="text-muted"><?= t('receipt.payment_method') ?></td><td class="text-end"><?= htmlspecialchars(strtoupper($order['payment_method'])) ?></td></tr>
        <?php endif; ?>
        <?php if ($order['transaction_ref']): ?>
        <tr><td class="text-muted"><?= t('receipt.transaction_ref') ?></td><td class="text-end"><code><?= htmlspecialchars($order['transaction_ref']) ?></code></td></tr>
        <?php endif; ?>
        <?php if ($order['paid_at']): ?>
        <tr><td class="text-muted"><?= t('receipt.paid_at') ?></td><td class="text-end"><?= htmlspecialchars($order['paid_at']) ?></td></tr>
        <?php endif; ?>
        <tr><td class="text-muted"><?= t('receipt.order_status') ?></td><td class="text-end"><span class="badge bg-secondary"><?= orderStatusLabel($order['status']) ?></span></td></tr>
      </table>
      <hr>
      <div class="d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><?= t('receipt.amount_paid') ?></span>
        <span class="receipt-total">৳<?= number_format($total, 2) ?></span>
      </div>

      <a href="buyer_dashboard.php" class="btn btn-success w-100 mt-4"><?= t('common.back_to_dashboard') ?></a>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
