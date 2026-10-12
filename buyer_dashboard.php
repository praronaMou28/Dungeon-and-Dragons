<?php
require 'auth.php';
requireRole('buyer');
require 'db_connect.php';
require_once 'lang.php';
require 'review_helpers.php';
require 'illustrations.php';

$buyer_id = $_SESSION['user_id'];

$stmtV = $pdo->prepare("SELECT * FROM verified_buyers WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmtV->execute([$buyer_id]);
$verification = $stmtV->fetch();

$isApproved = $verification && $verification['status'] === 'approved';

$stmtO = $pdo->prepare("SELECT o.*, c.crop_name, c.price_per_kg, u.name AS farmer_name FROM orders o
                         JOIN crop_listings c ON o.listing_id = c.id
                         JOIN users u ON c.farmer_id = u.id
                         WHERE o.buyer_id = ? ORDER BY o.created_at DESC");
$stmtO->execute([$buyer_id]);
$orderRows = $stmtO->fetchAll();
$totalSpentFarmers = array_sum(array_map(fn($o) => in_array($o['payment_status'], ['paid', 'cod']) ? $o['paid_amount'] : 0, $orderRows));
$pendingPayment = count(array_filter($orderRows, fn($o) => $o['payment_status'] === 'unpaid' && $o['status'] === 'requested'));

$reviewedStmt = $pdo->prepare("SELECT order_id FROM reviews WHERE reviewer_id = ? AND order_type = 'crop'");
$reviewedStmt->execute([$buyer_id]);
$reviewedOrderIds = $reviewedStmt->fetchAll(PDO::FETCH_COLUMN);

$myOrders = [];
foreach ($orderRows as $r) {
    $key = $r['group_ref'] ?: ('single-' . $r['id']);
    if (!isset($myOrders[$key])) {
        $myOrders[$key] = ['status' => $r['status'], 'payment_status' => $r['payment_status'], 'payment_method' => $r['payment_method'], 'first_id' => $r['id'], 'items' => []];
    }
    $myOrders[$key]['items'][] = $r;
}

$ORDER_STEPS = ['requested', 'accepted', 'out_for_delivery', 'completed'];
$ORDER_STEP_LABELS = ['requested' => 'Requested', 'accepted' => 'Accepted', 'out_for_delivery' => 'Out for Delivery', 'completed' => 'Completed'];

$myReviewsStmt = $pdo->prepare("SELECT r.*, u.name AS reviewer_name FROM reviews r
                                 JOIN users u ON r.reviewer_id = u.id
                                 WHERE r.target_id = ? AND r.order_type = 'storefront'
                                 ORDER BY r.created_at DESC");
$myReviewsStmt->execute([$buyer_id]);
$myReviews = $myReviewsStmt->fetchAll();
$myAvgRating = count($myReviews) > 0 ? array_sum(array_column($myReviews, 'rating')) / count($myReviews) : 0;

$stmtS = $pdo->prepare("SELECT * FROM storefront_listings WHERE buyer_id = ?");
$stmtS->execute([$buyer_id]);
$myStorefront = $stmtS->fetchAll();

$stmtRev = $pdo->prepare("SELECT COALESCE(SUM(co.paid_amount), 0) FROM consumer_orders co
                          JOIN storefront_listings s ON co.storefront_id = s.id
                          WHERE s.buyer_id = ? AND co.payment_status IN ('paid','cod')");
$stmtRev->execute([$buyer_id]);
$totalReceived = (float) $stmtRev->fetchColumn();

include 'header.php';
?>

<div class="hero-band furrow has-illustration tint-buyer">
  <div class="hero-text">
    <span class="hero-eyebrow"><i class="bi bi-shop"></i> <?= t('hero.buyer.eyebrow') ?></span>
    <h1><?= t('hero.buyer.welcome', ['name' => htmlspecialchars($_SESSION['name'])]) ?></h1>
    <p><?= t('hero.buyer.sub') ?></p>
    <div class="d-flex flex-wrap gap-2 mt-3">
      <a href="browse_crops.php" class="btn btn-success <?= !$isApproved ? 'disabled' : '' ?>"><i class="bi bi-basket me-1"></i>Browse Farmer Listings</a>
      <a href="crop_cart.php" class="btn btn-outline-success <?= !$isApproved ? 'disabled' : '' ?>"><i class="bi bi-cart3 me-1"></i>My Crop Cart</a>
      <a href="add_storefront_item.php" class="btn btn-outline-success <?= !$isApproved ? 'disabled' : '' ?>"><i class="bi bi-shop-window me-1"></i>Add Storefront Item</a>
    </div>
  </div>
  <div class="hero-illustration"><?= heroIllustration('buyer') ?></div>
</div>

<?php if (!$verification): ?>
  <div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div><i class="bi bi-hourglass me-1"></i>You haven't applied for verification yet.</div>
    <a href="apply_verification.php" class="btn btn-warning btn-sm">Apply Now <i class="bi bi-arrow-right ms-1"></i></a>
  </div>
<?php elseif ($verification['status'] === 'pending'): ?>
  <div class="alert alert-warning"><i class="bi bi-hourglass-split me-1"></i>Your verification is <strong>pending admin approval</strong>. You'll be able to buy and open a storefront once approved.</div>
<?php elseif ($verification['status'] === 'rejected'): ?>
  <div class="alert alert-danger d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div><i class="bi bi-x-circle me-1"></i>Your verification was rejected.</div>
    <a href="apply_verification.php" class="btn btn-danger btn-sm">Reapply</a>
  </div>
<?php else: ?>
  <div class="alert alert-success"><i class="bi bi-patch-check-fill me-1"></i>You are a <strong>Verified Buyer</strong>.</div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col-xl-3 col-md-6">
    <div class="dash-stat-card clay">
      <div class="dash-stat-top"><div class="dash-stat-icon"><i class="bi bi-arrow-up-circle"></i></div></div>
      <div class="dash-stat-num">৳<?= number_format($totalSpentFarmers, 0) ?></div>
      <div class="dash-stat-label">Sent to Farmers</div>
    </div>
  </div>
  <div class="col-xl-3 col-md-6">
    <div class="dash-stat-card">
      <div class="dash-stat-top"><div class="dash-stat-icon"><i class="bi bi-arrow-down-circle"></i></div></div>
      <div class="dash-stat-num">৳<?= number_format($totalReceived, 0) ?></div>
      <div class="dash-stat-label">Received from Consumers</div>
    </div>
  </div>
  <div class="col-xl-3 col-md-6">
    <div class="dash-stat-card gold">
      <div class="dash-stat-top"><div class="dash-stat-icon"><i class="bi bi-shop-window"></i></div></div>
      <div class="dash-stat-num"><?= count($myStorefront) ?></div>
      <div class="dash-stat-label">Storefront Items</div>
    </div>
  </div>
  <div class="col-xl-3 col-md-6">
    <div class="dash-stat-card blue">
      <div class="dash-stat-top"><div class="dash-stat-icon"><i class="bi bi-hourglass-split"></i></div></div>
      <div class="dash-stat-num"><?= $pendingPayment ?></div>
      <div class="dash-stat-label">Awaiting Farmer's Response</div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-xl-8">
    <div class="dash-panel mb-3">
      <div class="dash-panel-title"><span>My Purchase Requests</span></div>
      <?php if (count($myOrders) === 0): ?>
        <div class="empty-state py-3"><i class="bi bi-basket"></i><p class="mb-0">No purchase requests yet. <a href="browse_crops.php">Browse crops</a> to get started.</p></div>
      <?php else: ?>
        <?php foreach ($myOrders as $order): ?>
          <?php
            $stepIndex = array_search($order['status'], $ORDER_STEPS);
            if ($order['status'] === 'rejected' || $order['status'] === 'cancelled') $stepIndex = -1;
            $orderTotal = array_sum(array_map(fn($i) => $i['quantity_kg'] * $i['price_per_kg'], $order['items']));
          ?>
          <div class="tx-row align-items-start flex-column" style="border-bottom:1px solid var(--line);padding-bottom:1rem;margin-bottom:1rem;">
            <div class="w-100">
              <?php foreach ($order['items'] as $item): ?>
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <div>
                    <span class="tx-title"><?= htmlspecialchars($item['crop_name']) ?></span>
                    <span class="tx-sub"> &middot; <?= htmlspecialchars($item['farmer_name']) ?> &middot; <?= htmlspecialchars($item['quantity_kg']) ?> kg</span>
                  </div>
                  <span class="text-muted" style="font-size:0.85rem;">৳<?= number_format($item['quantity_kg'] * $item['price_per_kg'], 2) ?></span>
                </div>
              <?php endforeach; ?>

              <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2">
                <div class="fw-semibold" style="color:var(--paddy);">৳<?= number_format($orderTotal, 2) ?></div>
                <?php if ($order['status'] === 'rejected'): ?>
                  <span class="pill pill-danger">Rejected</span>
                <?php elseif ($order['payment_status'] === 'paid'): ?>
                  <span class="pill pill-green">Paid (<?= htmlspecialchars(strtoupper($order['payment_method'])) ?>)</span>
                <?php elseif ($order['payment_status'] === 'cod'): ?>
                  <span class="pill pill-blue">Cash on Delivery</span>
                <?php else: ?>
                  <span class="pill pill-gold" title="You'll only be charged if the farmer accepts">Awaiting response (<?= htmlspecialchars(strtoupper($order['payment_method'])) ?> on accept)</span>
                <?php endif; ?>
              </div>

              <?php if ($stepIndex >= 0): ?>
                <div class="d-flex align-items-center gap-1 mt-3" style="max-width:340px;">
                  <?php foreach ($ORDER_STEPS as $i => $s): ?>
                    <div style="height:5px;flex:1;border-radius:3px;background:<?= $i <= $stepIndex ? 'var(--paddy)' : 'var(--line)' ?>;"></div>
                  <?php endforeach; ?>
                </div>
                <div class="text-muted d-flex justify-content-between mt-1" style="font-size:0.64rem;max-width:340px;">
                  <?php foreach ($ORDER_STEPS as $s): ?><span><?= $ORDER_STEP_LABELS[$s] ?></span><?php endforeach; ?>
                </div>
              <?php endif; ?>

              <?php if ($order['status'] === 'completed'): ?>
                <div class="d-flex flex-wrap gap-2 mt-2">
                  <?php foreach ($order['items'] as $item): ?>
                    <?php if (in_array($item['id'], $reviewedOrderIds)): ?>
                      <span class="pill pill-gray"><i class="bi bi-check-circle"></i> Reviewed <?= htmlspecialchars($item['crop_name']) ?></span>
                    <?php else: ?>
                      <a href="review_farmer.php?order_id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-star me-1"></i>Rate <?= htmlspecialchars($item['crop_name']) ?></a>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="dash-panel">
      <div class="dash-panel-title"><span>My Storefront (selling to consumers)</span></div>
      <?php if (count($myStorefront) === 0): ?>
        <div class="empty-state py-3"><i class="bi bi-shop-window"></i><p class="mb-0">No items listed yet. <a href="add_storefront_item.php">Add your first item</a>.</p></div>
      <?php else: ?>
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <tr><th>Product</th><th>Price/unit</th><th>Stock</th></tr>
          <?php foreach ($myStorefront as $s): ?>
            <tr>
              <td><?= htmlspecialchars($s['product_name']) ?></td>
              <td>৳<?= htmlspecialchars($s['price_per_unit']) ?></td>
              <td><?= htmlspecialchars($s['stock_qty']) ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="dash-panel mb-3">
      <div class="dash-panel-title"><span>Verification Status</span></div>
      <?php if ($isApproved): ?>
        <div class="d-flex align-items-center gap-3">
          <div class="icon-tile"><i class="bi bi-patch-check-fill"></i></div>
          <div>
            <div class="fw-semibold" style="color:var(--text-primary);">Verified Buyer</div>
            <div class="text-muted" style="font-size:0.8rem;">Full marketplace access unlocked.</div>
          </div>
        </div>
      <?php elseif ($verification && $verification['status'] === 'pending'): ?>
        <div class="d-flex align-items-center gap-3">
          <div class="icon-tile gold"><i class="bi bi-hourglass-split"></i></div>
          <div>
            <div class="fw-semibold" style="color:var(--text-primary);">Pending Review</div>
            <div class="text-muted" style="font-size:0.8rem;">Applied <?= htmlspecialchars(date('d M Y', strtotime($verification['applied_at']))) ?></div>
          </div>
        </div>
      <?php else: ?>
        <div class="d-flex align-items-center gap-3">
          <div class="icon-tile clay"><i class="bi bi-exclamation-circle"></i></div>
          <div>
            <div class="fw-semibold" style="color:var(--text-primary);">Not Verified</div>
            <a href="apply_verification.php" class="text-decoration-none" style="font-size:0.82rem;">Apply for verification &rarr;</a>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div class="dash-panel mb-3">
      <div class="dash-panel-title">
        <span>My Reviews (as seller)</span>
        <?php if (count($myReviews) > 0): ?><span class="rating-pill"><i class="bi bi-star-fill"></i> <?= number_format($myAvgRating, 1) ?></span><?php endif; ?>
      </div>
      <?php if (count($myReviews) === 0): ?>
        <div class="empty-state py-3"><i class="bi bi-star"></i><p class="mb-0">No reviews yet. They'll show up here once consumers rate your storefront.</p></div>
      <?php else: ?>
        <?php foreach (array_slice($myReviews, 0, 4) as $r): ?>
          <div class="review-row">
            <div class="review-head">
              <span class="review-author"><?= htmlspecialchars($r['reviewer_name']) ?></span>
              <span class="review-date"><?= htmlspecialchars(date('d M', strtotime($r['created_at']))) ?></span>
            </div>
            <?= renderStars($r['rating']) ?>
            <?php if ($r['comment']): ?><p class="review-comment mt-1">&ldquo;<?= htmlspecialchars($r['comment']) ?>&rdquo;</p><?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="dash-panel mb-3">
      <div class="dash-panel-title"><span>Quick Links</span></div>
      <div class="d-flex flex-column gap-2">
        <a href="market.php" class="btn btn-outline-success btn-sm text-start"><i class="bi bi-shop me-1"></i>Public Market</a>
        <a href="manage_storefront_orders.php" class="btn btn-outline-success btn-sm text-start"><i class="bi bi-truck me-1"></i>Storefront Orders</a>
        <a href="equipment.php" class="btn btn-outline-success btn-sm text-start"><i class="bi bi-tools me-1"></i>Equipment Marketplace</a>
        <a href="payment_history.php" class="btn btn-outline-success btn-sm text-start"><i class="bi bi-receipt me-1"></i>Payment History</a>
        <a href="complaints.php" class="btn btn-outline-danger btn-sm text-start"><i class="bi bi-flag me-1"></i>File a Complaint</a>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
