<?php
require 'auth.php';
requireRole('farmer');
require 'db_connect.php';
require_once 'lang.php';
require 'review_helpers.php';
require 'illustrations.php';

$farmer_id = $_SESSION['user_id'];

$stmtL = $pdo->prepare("SELECT * FROM crop_listings WHERE farmer_id = ? ORDER BY created_at DESC");
$stmtL->execute([$farmer_id]);
$listings = $stmtL->fetchAll();
$activeListings = count(array_filter($listings, fn($l) => $l['status'] === 'available' && $l['quantity_kg'] > 0));

$stmtO = $pdo->prepare("SELECT o.*, c.crop_name, u.name AS buyer_name FROM orders o
                         JOIN crop_listings c ON o.listing_id = c.id
                         JOIN users u ON o.buyer_id = u.id
                         WHERE c.farmer_id = ? AND o.status = 'requested'");
$stmtO->execute([$farmer_id]);
$pendingOrders = $stmtO->fetchAll();

$stmtSales = $pdo->prepare("SELECT COALESCE(SUM(o.paid_amount), 0), COUNT(*) FROM orders o
                             JOIN crop_listings c ON o.listing_id = c.id
                             WHERE c.farmer_id = ? AND o.payment_status = 'paid'");
$stmtSales->execute([$farmer_id]);
[$totalSales, $paidOrderCount] = $stmtSales->fetch(PDO::FETCH_NUM);


$stmtE = $pdo->prepare("SELECT * FROM equipment_listings WHERE owner_id = ? ORDER BY created_at DESC");
$stmtE->execute([$farmer_id]);
$myEquipment = $stmtE->fetchAll();
$rentedEquipment = count(array_filter($myEquipment, fn($e) => $e['availability'] === 'rented'));


$myReviewsStmt = $pdo->prepare("SELECT r.*, u.name AS reviewer_name FROM reviews r
                                 JOIN users u ON r.reviewer_id = u.id
                                 WHERE r.target_id = ? AND r.order_type = 'crop'
                                 ORDER BY r.created_at DESC");
$myReviewsStmt->execute([$farmer_id]);
$myReviews = $myReviewsStmt->fetchAll();
$myAvgRating = count($myReviews) > 0 ? array_sum(array_column($myReviews, 'rating')) / count($myReviews) : 0;

include 'header.php';
?>

<div class="hero-band furrow has-illustration tint-farmer">
  <div class="hero-text">
    <span class="hero-eyebrow"><i class="bi bi-flower1"></i> <?= t('hero.farmer.eyebrow') ?></span>
    <h1><?= t('hero.farmer.welcome', ['name' => htmlspecialchars($_SESSION['name'])]) ?></h1>
    <p><?= t('hero.farmer.sub') ?></p>
    <div class="d-flex flex-wrap gap-2 mt-3 position-relative">
      <a href="add_listing.php" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i>Add New Crop Listing</a>
      <a href="add_equipment.php" class="btn btn-outline-success"><i class="bi bi-tools me-1"></i>Register Equipment</a>
    </div>
  </div>
  <div class="hero-illustration"><?= heroIllustration('farmer') ?></div>
</div>

<div class="row g-3 mb-4">
  <div class="col-xl-3 col-md-6">
    <div class="dash-stat-card">
      <div class="dash-stat-top">
        <div class="dash-stat-icon"><i class="bi bi-cash-stack"></i></div>
      </div>
      <div class="dash-stat-num">৳<?= number_format($totalSales, 0) ?></div>
      <div class="dash-stat-label">Total Sales (<?= $paidOrderCount ?> orders)</div>
    </div>
  </div>
  <div class="col-xl-3 col-md-6">
    <div class="dash-stat-card gold">
      <div class="dash-stat-top">
        <div class="dash-stat-icon"><i class="bi bi-box-seam"></i></div>
      </div>
      <div class="dash-stat-num"><?= $activeListings ?></div>
      <div class="dash-stat-label">Active Crop Listings</div>
    </div>
  </div>
  <div class="col-xl-3 col-md-6">
    <div class="dash-stat-card clay">
      <div class="dash-stat-top">
        <div class="dash-stat-icon"><i class="bi bi-hourglass-split"></i></div>
      </div>
      <div class="dash-stat-num"><?= count($pendingOrders) ?></div>
      <div class="dash-stat-label">Pending Requests</div>
    </div>
  </div>
  <div class="col-xl-3 col-md-6">
    <div class="dash-stat-card blue">
      <div class="dash-stat-top">
        <div class="dash-stat-icon"><i class="bi bi-truck-front"></i></div>
      </div>
      <div class="dash-stat-num"><?= count($myEquipment) ?></div>
      <div class="dash-stat-label"><?= $rentedEquipment ?> rented &middot; <?= count($myEquipment) - $rentedEquipment ?> available</div>
    </div>
  </div>
</div>

<div class="row g-3">

  <div class="col-xl-8">
    <div class="dash-panel mb-3">
      <div class="dash-panel-title"><span>Incoming Order Requests</span></div>
      <?php if (count($pendingOrders) === 0): ?>
        <div class="empty-state py-3"><i class="bi bi-inbox"></i><p class="mb-0">No pending requests right now.</p></div>
      <?php else: ?>
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <tr><th>Buyer</th><th>Crop</th><th>Quantity (kg)</th><th>Payment</th><th class="text-end">Action</th></tr>
          <?php foreach ($pendingOrders as $o): ?>
            <tr>
              <td><?= htmlspecialchars($o['buyer_name']) ?></td>
              <td><?= htmlspecialchars($o['crop_name']) ?></td>
              <td><?= htmlspecialchars($o['quantity_kg']) ?></td>
              <td>
                <?php if ($o['payment_status'] === 'cod'): ?>
                  <span class="pill pill-blue">Cash on Delivery</span>
                <?php elseif ($o['payment_status'] === 'paid'): ?>
                  <span class="pill pill-green">Paid (<?= strtoupper($o['payment_method']) ?>)</span>
                <?php else: ?>
                  <span class="pill pill-gold" title="Charged automatically only if you accept">Will charge <?= strtoupper($o['payment_method']) ?> on accept</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <a href="respond_order.php?id=<?= $o['id'] ?>&action=accept" class="btn btn-sm btn-success">Accept</a>
                <a href="respond_order.php?id=<?= $o['id'] ?>&action=reject" class="btn btn-sm btn-danger">Reject</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="dash-panel">
      <div class="dash-panel-title"><span>My Crop Listings</span></div>
      <?php if (count($listings) === 0): ?>
        <div class="empty-state py-3"><i class="bi bi-flower1"></i><p class="mb-0">You haven't listed any crops yet. <a href="add_listing.php">Add one now</a>.</p></div>
      <?php else: ?>
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <tr><th></th><th>Crop</th><th>Quantity (kg)</th><th>Price/kg</th><th>Status</th></tr>
          <?php foreach ($listings as $l): ?>
            <tr>
              <td style="width:48px;">
                <?php if (!empty($l['image_path'])): ?>
                  <img src="<?= htmlspecialchars($l['image_path']) ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:8px;">
                <?php else: ?>
                  <div class="icon-tile gold" style="width:40px;height:40px;font-size:1.1rem;"><i class="bi bi-flower1"></i></div>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($l['crop_name']) ?></td>
              <td><?= htmlspecialchars($l['quantity_kg']) ?></td>
              <td>৳<?= htmlspecialchars($l['price_per_kg']) ?></td>
              <td><span class="pill <?= $l['status'] === 'available' ? 'pill-green' : 'pill-gray' ?>"><?= htmlspecialchars(ucfirst($l['status'])) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </table>
        </div>
      <?php endif; ?>
    </div>
  </div>


  <div class="col-xl-4">
    <div class="dash-panel mb-3">
      <div class="dash-panel-title"><span>My Equipment</span></div>
      <?php if (count($myEquipment) === 0): ?>
        <div class="empty-state py-3"><i class="bi bi-tools"></i><p class="mb-0">Not listed yet. <a href="add_equipment.php">List one now</a>.</p></div>
      <?php else: ?>
        <?php foreach (array_slice($myEquipment, 0, 4) as $e): ?>
          <div class="tx-row">
            <div class="tx-icon"><i class="bi bi-truck-front"></i></div>
            <div class="tx-main">
              <div class="tx-title"><?= htmlspecialchars($e['equipment_type']) ?></div>
              <div class="tx-sub"><?= htmlspecialchars($e['contact_note']) ?></div>
            </div>
            <a href="toggle_equipment.php?id=<?= $e['id'] ?>" class="pill <?= $e['availability'] === 'available' ? 'pill-green' : 'pill-gray' ?> text-decoration-none flex-shrink-0">
              <?= ucfirst($e['availability']) ?>
            </a>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="dash-panel mb-3">
      <div class="dash-panel-title">
        <span>My Reviews</span>
        <?php if (count($myReviews) > 0): ?><span class="rating-pill"><i class="bi bi-star-fill"></i> <?= number_format($myAvgRating, 1) ?></span><?php endif; ?>
      </div>
      <?php if (count($myReviews) === 0): ?>
        <div class="empty-state py-3"><i class="bi bi-star"></i><p class="mb-0">No reviews yet. They'll show up here once buyers rate a completed order.</p></div>
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

    <div class="dash-panel">
      <div class="dash-panel-title"><span>Quick Links</span></div>
      <div class="d-flex flex-column gap-2">
        <a href="manage_crop_orders.php" class="btn btn-outline-success btn-sm text-start"><i class="bi bi-truck me-1"></i>Manage Accepted Orders</a>
        <a href="equipment.php" class="btn btn-outline-success btn-sm text-start"><i class="bi bi-truck-front me-1"></i>Browse Equipment</a>
        <a href="payment_history.php" class="btn btn-outline-success btn-sm text-start"><i class="bi bi-receipt me-1"></i>Payment History</a>
        <a href="complaints.php" class="btn btn-outline-danger btn-sm text-start"><i class="bi bi-flag me-1"></i>File a Complaint</a>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
