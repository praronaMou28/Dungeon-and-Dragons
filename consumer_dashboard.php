<?php
require 'auth.php';
requireRole('consumer');
require 'db_connect.php';
require_once 'lang.php';
require 'review_helpers.php';
require 'illustrations.php';

$stmt = $pdo->prepare("SELECT co.*, s.product_name, s.price_per_unit, s.image_path, s.unit_label FROM consumer_orders co
                        JOIN storefront_listings s ON co.storefront_id = s.id
                        WHERE co.consumer_id = ? ORDER BY co.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$rows = $stmt->fetchAll();

$reviewedStmt = $pdo->prepare("SELECT order_id FROM reviews WHERE reviewer_id = ? AND order_type = 'storefront'");
$reviewedStmt->execute([$_SESSION['user_id']]);
$reviewedOrderIds = $reviewedStmt->fetchAll(PDO::FETCH_COLUMN);

$totalSpent = array_sum(array_map(fn($o) => in_array($o['payment_status'], ['paid', 'cod']) ? $o['paid_amount'] : 0, $rows));
$pendingPayment = count(array_filter($rows, fn($o) => $o['payment_status'] === 'unpaid' && $o['status'] === 'placed'));
$completedOrders = count(array_filter($rows, fn($o) => $o['status'] === 'completed'));

$orders = [];
foreach ($rows as $r) {
    $key = $r['group_ref'] ?: ('single-' . $r['id']);
    if (!isset($orders[$key])) {
        $orders[$key] = ['status' => $r['status'], 'payment_status' => $r['payment_status'], 'payment_method' => $r['payment_method'], 'created_at' => $r['created_at'], 'first_id' => $r['id'], 'items' => []];
    }
    $orders[$key]['items'][] = $r;
}

$stmtF = $pdo->prepare("SELECT s.*, u.name AS seller_name, u.business_name FROM consumer_favorites f
                         JOIN storefront_listings s ON f.storefront_id = s.id
                         JOIN users u ON s.buyer_id = u.id
                         WHERE f.consumer_id = ? ORDER BY f.created_at DESC");
$stmtF->execute([$_SESSION['user_id']]);
$favorites = $stmtF->fetchAll();

$orderedCategoryStmt = $pdo->prepare("SELECT DISTINCT s.category FROM consumer_orders co
                                       JOIN storefront_listings s ON co.storefront_id = s.id
                                       WHERE co.consumer_id = ?");
$orderedCategoryStmt->execute([$_SESSION['user_id']]);
$orderedCategories = $orderedCategoryStmt->fetchAll(PDO::FETCH_COLUMN);

$suggested = [];
if (count($orderedCategories) > 0) {
    $placeholders = implode(',', array_fill(0, count($orderedCategories), '?'));
    $suggStmt = $pdo->prepare("SELECT s.*, u.name AS seller_name, u.business_name FROM storefront_listings s
                                JOIN users u ON s.buyer_id = u.id
                                WHERE s.category IN ($placeholders) AND s.stock_qty > 0
                                AND s.id NOT IN (SELECT storefront_id FROM consumer_orders WHERE consumer_id = ?)
                                ORDER BY s.created_at DESC LIMIT 3");
    $suggStmt->execute([...$orderedCategories, $_SESSION['user_id']]);
    $suggested = $suggStmt->fetchAll();
}

$STEPS = ['placed', 'confirmed', 'out_for_delivery', 'completed'];
$STEP_LABELS = ['placed' => 'Placed', 'confirmed' => 'Confirmed', 'out_for_delivery' => 'Out for Delivery', 'completed' => 'Completed'];

include 'header.php';
?>

<div class="hero-band furrow has-illustration tint-consumer">
  <div class="hero-text">
    <span class="hero-eyebrow"><i class="bi bi-basket2"></i> <?= t('hero.consumer.eyebrow') ?></span>
    <h1><?= t('hero.consumer.welcome', ['name' => htmlspecialchars($_SESSION['name'])]) ?></h1>
    <p><?= t('hero.consumer.sub') ?></p>
    <div class="d-flex flex-wrap gap-2 mt-3">
      <a href="market.php" class="btn btn-success"><i class="bi bi-basket me-1"></i>Browse Marketplace</a>
      <a href="cart.php" class="btn btn-outline-success"><i class="bi bi-cart3 me-1"></i>My Cart</a>
    </div>
  </div>
  <div class="hero-illustration"><?= heroIllustration('consumer') ?></div>
</div>

<div class="row g-3 mb-4">
  <div class="col-xl-4 col-md-6">
    <div class="dash-stat-card clay">
      <div class="dash-stat-top"><div class="dash-stat-icon"><i class="bi bi-wallet2"></i></div></div>
      <div class="dash-stat-num">৳<?= number_format($totalSpent, 0) ?></div>
      <div class="dash-stat-label">Total Spent</div>
    </div>
  </div>
  <div class="col-xl-4 col-md-6">
    <div class="dash-stat-card">
      <div class="dash-stat-top"><div class="dash-stat-icon"><i class="bi bi-bag-check"></i></div></div>
      <div class="dash-stat-num"><?= count($orders) ?></div>
      <div class="dash-stat-label"><?= $completedOrders ?> completed</div>
    </div>
  </div>
  <div class="col-xl-4 col-md-6">
    <div class="dash-stat-card gold">
      <div class="dash-stat-top"><div class="dash-stat-icon"><i class="bi bi-heart"></i></div></div>
      <div class="dash-stat-num"><?= count($favorites) ?></div>
      <div class="dash-stat-label">Saved Favorites</div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-xl-8">
    <div class="dash-panel mb-3">
      <div class="dash-panel-title"><span>My Orders</span></div>
      <?php if (count($orders) === 0): ?>
        <div class="empty-state py-3"><i class="bi bi-basket"></i><p class="mb-0">No orders yet. <a href="market.php">Browse the marketplace</a> to place your first order.</p></div>
      <?php else: ?>
        <?php foreach ($orders as $order): ?>
          <?php
            $stepIndex = array_search($order['status'], $STEPS);
            if ($stepIndex === false) $stepIndex = 0;
            $orderTotal = array_sum(array_map(fn($i) => $i['quantity'] * $i['price_per_unit'], $order['items']));
          ?>
          <div class="tx-row align-items-start flex-column flex-md-row" style="border-bottom:1px solid var(--line);padding-bottom:1rem;margin-bottom:1rem;">
            <div class="flex-grow-1 w-100">
              <?php foreach ($order['items'] as $item): ?>
                <div class="d-flex align-items-center gap-2 mb-2">
                  <?php if (!empty($item['image_path'])): ?>
                    <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:8px;flex-shrink:0;">
                  <?php else: ?>
                    <div class="tx-icon" style="width:40px;height:40px;"><i class="bi bi-basket3"></i></div>
                  <?php endif; ?>
                  <div>
                    <div class="tx-title"><?= htmlspecialchars($item['product_name']) ?></div>
                    <div class="tx-sub"><?= htmlspecialchars($item['quantity']) ?> <?= htmlspecialchars($item['unit_label'] ?: 'unit') ?></div>
                  </div>
                </div>
              <?php endforeach; ?>

              <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2">
                <div class="fw-semibold" style="color:var(--paddy);">৳<?= number_format($orderTotal, 2) ?></div>
                <?php if ($order['status'] === 'cancelled'): ?>
                  <span class="pill pill-danger">Declined by seller</span>
                <?php elseif ($order['payment_status'] === 'paid'): ?>
                  <span class="pill pill-green">Paid (<?= htmlspecialchars(strtoupper($order['payment_method'])) ?>)</span>
                <?php elseif ($order['payment_status'] === 'cod'): ?>
                  <span class="pill pill-blue">Cash on Delivery</span>
                <?php else: ?>
                  <span class="pill pill-gold" title="You'll only be charged if the seller confirms">Awaiting response (<?= htmlspecialchars(strtoupper($order['payment_method'])) ?> on confirm)</span>
                <?php endif; ?>
              </div>

              <?php if ($order['status'] !== 'cancelled' && in_array($order['payment_status'], ['paid', 'cod', 'unpaid'])): ?>
                <div class="d-flex align-items-center gap-1 mt-3" style="max-width:340px;">
                  <?php foreach ($STEPS as $i => $s): ?>
                    <div style="height:5px;flex:1;border-radius:3px;background:<?= $i <= $stepIndex ? 'var(--paddy)' : 'var(--line)' ?>;"></div>
                  <?php endforeach; ?>
                </div>
                <div class="text-muted d-flex justify-content-between mt-1" style="font-size:0.64rem;max-width:340px;">
                  <?php foreach ($STEPS as $s): ?><span><?= $STEP_LABELS[$s] ?></span><?php endforeach; ?>
                </div>
              <?php endif; ?>

              <?php if ($order['status'] === 'completed'): ?>
                <div class="d-flex flex-wrap gap-2 mt-2">
                  <?php foreach ($order['items'] as $item): ?>
                    <?php if (in_array($item['id'], $reviewedOrderIds)): ?>
                      <span class="pill pill-gray"><i class="bi bi-check-circle"></i> Reviewed <?= htmlspecialchars($item['product_name']) ?></span>
                    <?php else: ?>
                      <a href="review_seller.php?order_id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-star me-1"></i>Rate <?= htmlspecialchars($item['product_name']) ?></a>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="dash-panel mb-3">
      <div class="dash-panel-title"><span><i class="bi bi-heart me-1"></i>My Favorites</span></div>
      <?php if (count($favorites) === 0): ?>
        <div class="empty-state py-3"><i class="bi bi-heart"></i><p class="mb-0">Tap the heart on any product in the <a href="market.php">marketplace</a> to save it here.</p></div>
      <?php else: ?>
        <?php foreach ($favorites as $f): ?>
          <div class="tx-row">
            <?php if (!empty($f['image_path'])): ?>
              <img src="<?= htmlspecialchars($f['image_path']) ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:8px;flex-shrink:0;">
            <?php else: ?>
              <div class="tx-icon"><i class="bi bi-basket3"></i></div>
            <?php endif; ?>
            <div class="tx-main">
              <div class="tx-title"><?= htmlspecialchars($f['product_name']) ?></div>
              <div class="tx-sub">৳<?= number_format($f['price_per_unit'], 2) ?> / <?= htmlspecialchars($f['unit_label'] ?: 'unit') ?></div>
            </div>
          </div>
        <?php endforeach; ?>
        <a href="market.php" class="btn btn-outline-success btn-sm w-100 mt-2">Order from Marketplace</a>
      <?php endif; ?>
    </div>

    <?php if (count($suggested) > 0): ?>
    <div class="dash-panel">
      <div class="dash-panel-title"><span><i class="bi bi-stars me-1"></i>You Might Also Like</span></div>
      <?php foreach ($suggested as $s): ?>
        <div class="tx-row">
          <?php if (!empty($s['image_path'])): ?>
            <img src="<?= htmlspecialchars($s['image_path']) ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:8px;flex-shrink:0;">
          <?php else: ?>
            <div class="tx-icon"><i class="bi bi-basket3"></i></div>
          <?php endif; ?>
          <div class="tx-main">
            <div class="tx-title"><?= htmlspecialchars($s['product_name']) ?></div>
            <div class="tx-sub">৳<?= number_format($s['price_per_unit'], 2) ?> &middot; <?= htmlspecialchars($s['business_name'] ?: $s['seller_name']) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
      <a href="market.php" class="btn btn-outline-success btn-sm w-100 mt-2">See More</a>
    </div>
    <?php endif; ?>

    <div class="dash-panel mt-3">
      <div class="dash-panel-title"><span>Need Help?</span></div>
      <a href="complaints.php" class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-flag me-1"></i>File a Complaint</a>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
