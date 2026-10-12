<?php
require 'auth.php';
requireRole('consumer');
require 'db_connect.php';
require_once 'lang.php';

function orderStatusLabel($status) {
    $map = ['pending'=>'order_status.pending','placed'=>'fulfillment.placed','confirmed'=>'fulfillment.confirmed','accepted'=>'fulfillment.accepted','out_for_delivery'=>'fulfillment.out_for_delivery','completed'=>'fulfillment.completed','rejected'=>'order_status.rejected','cancelled'=>'order_status.cancelled'];
    return isset($map[$status]) ? t($map[$status]) : htmlspecialchars($status);
}

$order_id = $_GET['order_id'] ?? null;
if (!$order_id) {
    header("Location: consumer_dashboard.php");
    exit;
}

$stmt = $pdo->prepare("SELECT co.*, s.product_name, s.price_per_unit, u.name AS seller_name, u.business_name FROM consumer_orders co
                        JOIN storefront_listings s ON co.storefront_id = s.id
                        JOIN users u ON s.buyer_id = u.id
                        WHERE co.id = ? AND co.consumer_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: consumer_dashboard.php");
    exit;
}

$total = $order['paid_amount'] ?? ($order['quantity'] * $order['price_per_unit']);
$isCod = $order['payment_status'] === 'cod';

include 'header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card receipt-card p-4">
      <div class="text-center mb-3">
        <?php if ($isCod): ?>
          <i class="bi bi-truck text-warning" style="font-size:2.5rem;"></i>
          <h3 class="page-title mt-2 mb-0"><?= t('receipt.order_confirmed_cod') ?></h3>
        <?php elseif ($order['payment_status'] === 'paid'): ?>
          <i class="bi bi-check-circle-fill text-success" style="font-size:2.5rem;"></i>
          <h3 class="page-title mt-2 mb-0"><?= t('receipt.payment_successful') ?></h3>
        <?php else: ?>
          <i class="bi bi-hourglass-split text-warning" style="font-size:2.5rem;"></i>
          <h3 class="page-title mt-2 mb-0"><?= t('receipt.payment_pending') ?></h3>
        <?php endif; ?>
      </div>

      <table class="table table-borderless mb-0">
        <tr><td class="text-muted"><?= t('receipt.order_id') ?></td><td class="text-end">#<?= (int)$order['id'] ?></td></tr>
        <tr><td class="text-muted"><?= t('col.product') ?></td><td class="text-end"><?= htmlspecialchars($order['product_name']) ?></td></tr>
        <tr><td class="text-muted"><?= t('receipt.sold_by') ?></td><td class="text-end"><?= htmlspecialchars($order['business_name'] ?: $order['seller_name']) ?></td></tr>
        <tr><td class="text-muted"><?= t('col.quantity') ?></td><td class="text-end"><?= htmlspecialchars($order['quantity']) ?></td></tr>
        <tr><td class="text-muted"><?= t('receipt.price_per_unit') ?></td><td class="text-end">৳<?= number_format($order['price_per_unit'], 2) ?></td></tr>
        <tr>
          <td class="text-muted"><?= t('receipt.payment_method') ?></td>
          <td class="text-end"><?= $isCod ? t('checkout.cod') : htmlspecialchars(strtoupper($order['payment_method'] ?? '-')) ?></td>
        </tr>
        <?php if ($order['transaction_ref']): ?>
        <tr><td class="text-muted"><?= t('receipt.transaction_ref') ?></td><td class="text-end"><code><?= htmlspecialchars($order['transaction_ref']) ?></code></td></tr>
        <?php endif; ?>
        <?php if ($order['paid_at']): ?>
        <tr><td class="text-muted"><?= $isCod ? t('receipt.ordered_at') : t('receipt.paid_at') ?></td><td class="text-end"><?= htmlspecialchars($order['paid_at']) ?></td></tr>
        <?php endif; ?>
        <tr><td class="text-muted"><?= t('receipt.order_status') ?></td><td class="text-end"><span class="badge bg-secondary"><?= orderStatusLabel($order['status']) ?></span></td></tr>
      </table>
      <hr>
      <div class="d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><?= $isCod ? t('receipt.amount_due_cod') : t('receipt.amount_paid') ?></span>
        <span class="receipt-total">৳<?= number_format($total, 2) ?></span>
      </div>

      <a href="consumer_dashboard.php" class="btn btn-success w-100 mt-4"><?= t('common.back_to_dashboard') ?></a>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
