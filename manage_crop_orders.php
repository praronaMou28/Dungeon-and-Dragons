<?php
require 'auth.php';
requireRole('farmer');
require 'db_connect.php';
require 'notifications.php';
require_once 'lang.php';

$STEPS = ['accepted', 'out_for_delivery', 'completed'];
$STEP_LABELS = ['accepted' => t('fulfillment.accepted'), 'out_for_delivery' => t('fulfillment.out_for_delivery'), 'completed' => t('fulfillment.completed')];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $nextStatus = $_POST['next_status'] ?? '';

    $check = $pdo->prepare("SELECT o.buyer_id, l.crop_name FROM orders o
                             JOIN crop_listings l ON o.listing_id = l.id
                             WHERE o.id = ? AND l.farmer_id = ?");
    $check->execute([$orderId, $_SESSION['user_id']]);
    $row = $check->fetch();

    if ($row && in_array($nextStatus, $STEPS, true)) {
        $update = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $update->execute([$nextStatus, $orderId]);
        notify($pdo, (int)$row['buyer_id'], "Your order for " . $row['crop_name'] . " is now: " . $STEP_LABELS[$nextStatus] . ".", "buyer_dashboard.php");
    }
    header("Location: manage_crop_orders.php");
    exit;
}


$stmt = $pdo->prepare("SELECT o.*, l.crop_name, l.image_path, u.name AS buyer_name FROM orders o
                        JOIN crop_listings l ON o.listing_id = l.id
                        JOIN users u ON o.buyer_id = u.id
                        WHERE l.farmer_id = ? AND o.status IN ('accepted','out_for_delivery','completed')
                        ORDER BY o.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

include 'header.php';
?>

<div class="hero-band furrow">
  <span class="hero-eyebrow"><i class="bi bi-truck"></i> <?= t('nav.fulfillment') ?></span>
  <h1><?= t('manage_crop_orders.title') ?></h1>
  <p><?= t('manage_crop_orders.subtitle') ?></p>
</div>

<a href="farmer_dashboard.php" class="btn btn-outline-success mb-3"><i class="bi bi-arrow-left me-1"></i><?= t('common.back_to_dashboard') ?></a>

<?php if (count($orders) === 0): ?>
  <div class="empty-state">
    <i class="bi bi-truck"></i>
    <h5><?= t('manage_crop_orders.empty_title') ?></h5>
    <p class="mb-0"><?= t('manage_crop_orders.empty_sub') ?></p>
  </div>
<?php else: ?>
  <?php foreach ($orders as $order): ?>
    <?php
      $stepIndex = array_search($order['status'], $STEPS);
      if ($stepIndex === false) $stepIndex = 0;
    ?>
    <div class="dash-panel mb-3">
      <div class="dash-panel-title">
        <span><i class="bi bi-person me-1"></i><?= htmlspecialchars($order['buyer_name']) ?></span>
        <span class="text-muted" style="font-size:0.76rem;"><?= htmlspecialchars(date('d M, h:i A', strtotime($order['created_at']))) ?></span>
      </div>
      <div class="row">
        <div class="col-md-7">
          <div class="tx-row">
            <?php if (!empty($order['image_path'])): ?>
              <img src="<?= htmlspecialchars($order['image_path']) ?>" alt="" style="width:44px;height:44px;object-fit:cover;border-radius:8px;flex-shrink:0;">
            <?php else: ?>
              <div class="tx-icon"><i class="bi bi-flower1"></i></div>
            <?php endif; ?>
            <div class="tx-main">
              <div class="tx-title"><?= htmlspecialchars($order['crop_name']) ?></div>
              <div class="tx-sub"><?= htmlspecialchars($order['quantity_kg']) ?> kg &middot; ৳<?= number_format($order['paid_amount'], 2) ?> &middot; <?= $order['payment_status'] === 'cod' ? t('checkout.cod') : t('manage_crop_orders.paid_via', ['method' => strtoupper($order['payment_method'])]) ?></div>
            </div>
          </div>
          <p class="text-muted mt-2 mb-0" style="font-size:0.82rem;"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($order['delivery_address']) ?> &middot; <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($order['delivery_phone']) ?></p>
        </div>

        <div class="col-md-5">
          <div class="d-flex align-items-center gap-1 mb-2">
            <?php foreach ($STEPS as $i => $s): ?>
              <div style="height:5px;flex:1;border-radius:3px;background:<?= $i <= $stepIndex ? 'var(--paddy)' : 'var(--line)' ?>;"></div>
            <?php endforeach; ?>
          </div>
          <div class="text-muted d-flex justify-content-between mb-3" style="font-size:0.64rem;">
            <?php foreach ($STEPS as $s): ?><span><?= $STEP_LABELS[$s] ?></span><?php endforeach; ?>
          </div>

          <?php if ($stepIndex < count($STEPS) - 1): ?>
            <?php $nextStep = $STEPS[$stepIndex + 1]; ?>
            <form method="POST">
              <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
              <input type="hidden" name="next_status" value="<?= $nextStep ?>">
              <button type="submit" class="btn btn-success btn-sm w-100"><?= t('manage_crop_orders.mark_as', ['step' => $STEP_LABELS[$nextStep]]) ?></button>
            </form>
          <?php else: ?>
            <span class="pill pill-green w-100 d-block text-center py-2"><i class="bi bi-check-circle-fill"></i> <?= t('fulfillment.completed') ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php include 'footer.php'; ?>
