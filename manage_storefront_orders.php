<?php
require 'auth.php';
requireRole('buyer');
require 'db_connect.php';
require 'notifications.php';
require_once 'lang.php';

$STEPS = ['placed', 'confirmed', 'out_for_delivery', 'completed'];
$STEP_LABELS = ['placed' => t('fulfillment.placed'), 'confirmed' => t('fulfillment.confirmed'), 'out_for_delivery' => t('fulfillment.out_for_delivery'), 'completed' => t('fulfillment.completed')];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $alsoIds = array_map('intval', $_POST['also'] ?? []);
    $allIds = array_unique(array_merge([$orderId], $alsoIds));
    $nextStatus = $_POST['next_status'] ?? '';

    if (in_array($nextStatus, array_merge($STEPS, ['cancelled']), true)) {
        $consumerId = null;
        foreach ($allIds as $id) {
          
            $check = $pdo->prepare("SELECT co.consumer_id, co.status, co.payment_status, co.payment_method, co.quantity, co.storefront_id, co.paid_amount
                                     FROM consumer_orders co
                                     JOIN storefront_listings s ON co.storefront_id = s.id
                                     WHERE co.id = ? AND s.buyer_id = ?");
            $check->execute([$id, $_SESSION['user_id']]);
            $order = $check->fetch();

            if ($order) {
                $consumerId = $order['consumer_id'];
                $update = $pdo->prepare("UPDATE consumer_orders SET status = ? WHERE id = ?");
                $update->execute([$nextStatus, $id]);

                if ($nextStatus === 'confirmed' && $order['status'] === 'placed') {
           
                    $pdo->prepare("UPDATE storefront_listings SET stock_qty = stock_qty - ? WHERE id = ?")
                        ->execute([$order['quantity'], $order['storefront_id']]);

                    if ($order['payment_status'] === 'unpaid' && in_array($order['payment_method'], ['bkash', 'card'], true)) {
                        $pdo->prepare("UPDATE consumer_orders SET payment_status = 'paid', transaction_ref = ?, paid_at = NOW() WHERE id = ?")
                            ->execute([generateTransactionRef($order['payment_method']), $id]);
                    }
                }
            }
        }

        if ($consumerId) {
            if ($nextStatus === 'cancelled') {
                notify($pdo, $consumerId, "Your order was declined by the seller. You were not charged.", "consumer_dashboard.php");
            } else {
                notify($pdo, $consumerId, "Your order is now: " . $STEP_LABELS[$nextStatus] . ".", "consumer_dashboard.php");
            }
        }
    }
    header("Location: manage_storefront_orders.php");
    exit;
}


$stmt = $pdo->prepare("SELECT co.*, s.product_name, s.image_path, s.unit_label, u.name AS consumer_name FROM consumer_orders co
                        JOIN storefront_listings s ON co.storefront_id = s.id
                        JOIN users u ON co.consumer_id = u.id
                        WHERE s.buyer_id = ? AND co.status != 'cancelled'
                        ORDER BY co.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$rows = $stmt->fetchAll();


$orders = [];
foreach ($rows as $r) {
    $key = $r['group_ref'] ?: 'single-' . $r['id'];
    if (!isset($orders[$key])) {
        $orders[$key] = ['status' => $r['status'], 'payment_status' => $r['payment_status'], 'payment_method' => $r['payment_method'], 'consumer_name' => $r['consumer_name'], 'delivery_address' => $r['delivery_address'], 'delivery_phone' => $r['delivery_phone'], 'created_at' => $r['created_at'], 'items' => []];
    }
    $orders[$key]['items'][] = $r;
}

include 'header.php';
?>

<div class="hero-band furrow">
  <span class="hero-eyebrow"><i class="bi bi-truck"></i> <?= t('nav.storefront_orders') ?></span>
  <h1><?= t('manage_storefront_orders.title') ?></h1>
  <p><?= t('manage_storefront_orders.subtitle') ?></p>
</div>

<?php if (count($orders) === 0): ?>
  <div class="empty-state">
    <i class="bi bi-inbox"></i>
    <h5><?= t('manage_storefront_orders.empty_title') ?></h5>
    <p class="mb-0"><?= t('manage_storefront_orders.empty_sub') ?></p>
  </div>
<?php else: ?>
  <?php foreach ($orders as $key => $order): ?>
    <?php
      $stepIndex = array_search($order['status'], $STEPS);
      if ($stepIndex === false) $stepIndex = 0;
      $orderTotal = array_sum(array_map(fn($i) => $i['paid_amount'], $order['items']));
    ?>
    <div class="dash-panel mb-3">
      <div class="dash-panel-title">
        <span><i class="bi bi-person me-1"></i><?= htmlspecialchars($order['consumer_name']) ?></span>
        <span class="text-muted" style="font-size:0.76rem;"><?= htmlspecialchars(date('d M, h:i A', strtotime($order['created_at']))) ?></span>
      </div>

      <div class="row">
        <div class="col-md-7">
          <?php foreach ($order['items'] as $item): ?>
            <div class="tx-row">
              <?php if (!empty($item['image_path'])): ?>
                <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:8px;flex-shrink:0;">
              <?php else: ?>
                <div class="tx-icon"><i class="bi bi-basket3"></i></div>
              <?php endif; ?>
              <div class="tx-main">
                <div class="tx-title"><?= htmlspecialchars($item['product_name']) ?></div>
                <div class="tx-sub"><?= htmlspecialchars($item['quantity']) ?> <?= htmlspecialchars($item['unit_label'] ?: t('common.unit')) ?> &middot; ৳<?= number_format($item['paid_amount'], 2) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
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

          <div class="text-end mb-2 fw-semibold" style="color:var(--paddy);">৳<?= number_format($orderTotal, 2) ?></div>
          <div class="text-end mb-2">
            <?php if ($order['payment_status'] === 'cod'): ?>
              <span class="pill pill-blue"><?= t('checkout.cod') ?></span>
            <?php elseif ($order['payment_status'] === 'paid'): ?>
              <span class="pill pill-green"><?= t('common.paid') ?> (<?= strtoupper($order['payment_method']) ?>)</span>
            <?php else: ?>
              <span class="pill pill-gold" title="<?= t('manage_storefront_orders.charge_on_confirm_hint') ?>"><?= t('manage_storefront_orders.will_charge_on_confirm', ['method' => strtoupper($order['payment_method'])]) ?></span>
            <?php endif; ?>
          </div>

          <?php if ($order['status'] === 'placed'): ?>
            <div class="d-flex gap-2">
              <form method="POST" class="flex-grow-1">
                <input type="hidden" name="order_id" value="<?= $order['items'][0]['id'] ?>">
                <input type="hidden" name="next_status" value="confirmed">
                <?php foreach ($order['items'] as $i => $item): if ($i === 0) continue; ?>
                  <input type="hidden" name="also[]" value="<?= $item['id'] ?>">
                <?php endforeach; ?>
                <button type="submit" class="btn btn-success btn-sm w-100"><?= t('common.accept') ?></button>
              </form>
              <form method="POST" class="flex-grow-1">
                <input type="hidden" name="order_id" value="<?= $order['items'][0]['id'] ?>">
                <input type="hidden" name="next_status" value="cancelled">
                <?php foreach ($order['items'] as $i => $item): if ($i === 0) continue; ?>
                  <input type="hidden" name="also[]" value="<?= $item['id'] ?>">
                <?php endforeach; ?>
                <button type="submit" class="btn btn-outline-danger btn-sm w-100"><?= t('common.reject') ?></button>
              </form>
            </div>
          <?php elseif ($stepIndex < count($STEPS) - 1): ?>
            <?php $nextStep = $STEPS[$stepIndex + 1]; ?>
            <form method="POST">
              <input type="hidden" name="order_id" value="<?= $order['items'][0]['id'] ?>">
              <input type="hidden" name="next_status" value="<?= $nextStep ?>">
              <?php foreach ($order['items'] as $i => $item): if ($i === 0) continue; ?>
                <input type="hidden" name="also[]" value="<?= $item['id'] ?>">
              <?php endforeach; ?>
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
