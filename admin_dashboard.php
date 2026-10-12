<?php
require 'auth.php';
requireRole('admin');
require 'db_connect.php';
require_once 'lang.php';
require 'illustrations.php';
$farmers   = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'farmer'")->fetchColumn();
$buyers    = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'buyer'")->fetchColumn();
$consumers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'consumer'")->fetchColumn();
$listings  = (int) $pdo->query("SELECT COUNT(*) FROM crop_listings WHERE status = 'available' AND quantity_kg > 0")->fetchColumn();
$pendingBuyers = (int) $pdo->query("SELECT COUNT(*) FROM verified_buyers WHERE status = 'pending'")->fetchColumn();

function weeklyTrend($pdo, $table, $where = '') {
    $sqlThis = "SELECT COUNT(*) FROM $table WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) $where";
    $sqlPrev = "SELECT COUNT(*) FROM $table WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY) AND created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) $where";
    $thisWeek = (int) $pdo->query($sqlThis)->fetchColumn();
    $prevWeek = (int) $pdo->query($sqlPrev)->fetchColumn();
    if ($prevWeek === 0) {
        $pct = $thisWeek > 0 ? 100 : 0;
    } else {
        $pct = round((($thisWeek - $prevWeek) / $prevWeek) * 100);
    }
    return ['pct' => $pct, 'this_week' => $thisWeek];
}

$farmerTrend   = weeklyTrend($pdo, 'users', "AND role = 'farmer'");
$buyerTrend    = weeklyTrend($pdo, 'users', "AND role = 'buyer'");
$consumerTrend = weeklyTrend($pdo, 'users', "AND role = 'consumer'");
$listingTrend  = weeklyTrend($pdo, 'crop_listings');

$activityLabels = [];
$activityFarmerOrders = [];
$activityConsumerOrders = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $activityLabels[] = date('D', strtotime($day));

    $stmt1 = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = ?");
    $stmt1->execute([$day]);
    $activityFarmerOrders[] = (int) $stmt1->fetchColumn();

    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM consumer_orders WHERE DATE(created_at) = ?");
    $stmt2->execute([$day]);
    $activityConsumerOrders[] = (int) $stmt2->fetchColumn();
}

$txStmt = $pdo->query("
    (SELECT o.created_at AS ts, u1.name AS from_name, u2.name AS to_name,
            c.crop_name AS item, o.quantity_kg AS qty, (o.quantity_kg * c.price_per_kg) AS amount,
            o.status AS status, 'Crop Order' AS kind
     FROM orders o
     JOIN crop_listings c ON o.listing_id = c.id
     JOIN users u1 ON o.buyer_id = u1.id
     JOIN users u2 ON c.farmer_id = u2.id)
    UNION ALL
    (SELECT co.created_at AS ts, u1.name AS from_name, u2.name AS to_name,
            s.product_name AS item, co.quantity AS qty, (co.quantity * s.price_per_unit) AS amount,
            co.status AS status, 'Storefront Order' AS kind
     FROM consumer_orders co
     JOIN storefront_listings s ON co.storefront_id = s.id
     JOIN users u1 ON co.consumer_id = u1.id
     JOIN users u2 ON s.buyer_id = u2.id)
    ORDER BY ts DESC
    LIMIT 8
");
$recentTx = $txStmt->fetchAll();

include 'header.php';

function trendBadge($trend) {
    $pct = $trend['pct'];
    if ($pct > 0) {
        return "<span class=\"dash-trend up\"><i class=\"bi bi-arrow-up-short\"></i>{$pct}%</span>";
    } elseif ($pct < 0) {
        return "<span class=\"dash-trend down\"><i class=\"bi bi-arrow-down-short\"></i>" . abs($pct) . "%</span>";
    }
    return "<span class=\"dash-trend flat\"><i class=\"bi bi-dash\"></i>0%</span>";
}
?>

<div class="hero-band furrow has-illustration tint-admin">
  <div class="hero-text">
    <span class="hero-eyebrow"><i class="bi bi-shield-check"></i> <?= t('hero.admin.eyebrow') ?></span>
    <h1><?= t('hero.admin.title') ?></h1>
    <p><?= t('hero.admin.sub') ?></p>
  </div>
  <div class="hero-illustration"><?= heroIllustration('admin') ?></div>
</div>

<!-- ===== Stat cards ===== -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-md-6">
    <a href="admin_view_farmers.php" class="dash-stat-card text-decoration-none">
      <div class="dash-stat-top">
        <div class="dash-stat-icon"><i class="bi bi-flower1"></i></div>
        <?= trendBadge($farmerTrend) ?>
      </div>
      <div class="dash-stat-num"><?= $farmers ?></div>
      <div class="dash-stat-label">Total Farmers</div>
    </a>
  </div>
  <div class="col-xl-3 col-md-6">
    <a href="admin_view_buyers.php" class="dash-stat-card emerald text-decoration-none">
      <div class="dash-stat-top">
        <div class="dash-stat-icon"><i class="bi bi-shop"></i></div>
        <?= trendBadge($buyerTrend) ?>
      </div>
      <div class="dash-stat-num"><?= $buyers ?></div>
      <div class="dash-stat-label">Total Buyers</div>
    </a>
  </div>
  <div class="col-xl-3 col-md-6">
    <a href="admin_view_consumers.php" class="dash-stat-card clay text-decoration-none">
      <div class="dash-stat-top">
        <div class="dash-stat-icon"><i class="bi bi-people"></i></div>
        <?= trendBadge($consumerTrend) ?>
      </div>
      <div class="dash-stat-num"><?= $consumers ?></div>
      <div class="dash-stat-label">Total Consumers</div>
    </a>
  </div>
  <div class="col-xl-3 col-md-6">
    <a href="admin_view_listings.php" class="dash-stat-card gold text-decoration-none">
      <div class="dash-stat-top">
        <div class="dash-stat-icon"><i class="bi bi-box-seam"></i></div>
        <?= trendBadge($listingTrend) ?>
      </div>
      <div class="dash-stat-num"><?= $listings ?></div>
      <div class="dash-stat-label">Active Listings</div>
    </a>
  </div>
</div>

<?php if ($pendingBuyers > 0): ?>
<div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
  <div><i class="bi bi-hourglass-split me-1"></i><strong><?= $pendingBuyers ?></strong> buyer<?= $pendingBuyers > 1 ? 's' : '' ?> waiting for verification.</div>
  <a href="verify_buyers.php" class="btn btn-warning btn-sm">Review now <i class="bi bi-arrow-right ms-1"></i></a>
</div>
<?php endif; ?>

<!-- ===== Charts ===== -->
<div class="row g-3 mb-4">
  <div class="col-xl-8">
    <div class="dash-panel">
      <div class="dash-panel-title">
        <span>Marketplace Activity <small class="text-muted fw-normal">(last 7 days)</small></span>
      </div>
      <canvas id="activityChart" height="90"></canvas>
      <div class="chart-legend">
        <div class="chart-legend-item"><span class="chart-legend-dot" style="background:#34D399;"></span>Crop Orders (Farmer &rarr; Buyer)</div>
        <div class="chart-legend-item"><span class="chart-legend-dot" style="background:#E7A93A;"></span>Storefront Orders (Buyer &rarr; Consumer)</div>
      </div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="dash-panel">
      <div class="dash-panel-title"><span>User Distribution</span></div>
      <canvas id="userDonutChart" height="180"></canvas>
      <div class="chart-legend">
        <div class="chart-legend-item"><span class="chart-legend-dot" style="background:#34D399;"></span>Farmers (<?= $farmers ?>)</div>
        <div class="chart-legend-item"><span class="chart-legend-dot" style="background:#1FAE7C;"></span>Buyers (<?= $buyers ?>)</div>
        <div class="chart-legend-item"><span class="chart-legend-dot" style="background:#D9642E;"></span>Consumers (<?= $consumers ?>)</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-xl-12">
    <div class="dash-panel">
      <div class="dash-panel-title">
        <span>Recent Transactions</span>
        <a href="payment_history.php" class="view-all">View all <i class="bi bi-arrow-right"></i></a>
      </div>
      <?php if (count($recentTx) === 0): ?>
        <div class="empty-state py-3"><i class="bi bi-receipt"></i><p class="mb-0">No transactions yet.</p></div>
      <?php else: ?>
        <?php foreach ($recentTx as $tx): ?>
          <div class="tx-row">
            <div class="tx-icon"><i class="bi <?= $tx['kind'] === 'Crop Order' ? 'bi-flower1' : 'bi-shop-window' ?>"></i></div>
            <div class="tx-main">
              <div class="tx-title"><?= htmlspecialchars($tx['from_name']) ?> &rarr; <?= htmlspecialchars($tx['to_name']) ?> &middot; <?= htmlspecialchars($tx['item']) ?></div>
              <div class="tx-sub"><?= htmlspecialchars($tx['kind']) ?> &middot; <?= htmlspecialchars(date('d M, h:i A', strtotime($tx['ts']))) ?></div>
            </div>
            <div class="text-end">
              <div class="tx-amount">৳<?= number_format($tx['amount'], 2) ?></div>
              <span class="pill <?= $tx['status'] === 'completed' ? 'pill-green' : ($tx['status'] === 'rejected' ? 'pill-danger' : 'pill-gold') ?>" style="font-size:0.68rem; padding:0.2rem 0.6rem;">
                <?= ucfirst($tx['status']) ?>
              </span>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="d-flex gap-2 flex-wrap">
  <a href="verify_buyers.php" class="btn btn-success"><i class="bi bi-patch-check me-1"></i>Verify Buyers</a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  if (typeof Chart === 'undefined') return;

  Chart.defaults.color = '#94a3b8';
  Chart.defaults.font.family = 'Inter, sans-serif';

  const activityCtx = document.getElementById('activityChart');
  if (activityCtx) {
    const gradientGreen = activityCtx.getContext('2d').createLinearGradient(0, 0, 0, 220);
    gradientGreen.addColorStop(0, 'rgba(47,143,78,0.35)');
    gradientGreen.addColorStop(1, 'rgba(47,143,78,0)');
    const gradientGold = activityCtx.getContext('2d').createLinearGradient(0, 0, 0, 220);
    gradientGold.addColorStop(0, 'rgba(251,191,36,0.30)');
    gradientGold.addColorStop(1, 'rgba(251,191,36,0)');

    new Chart(activityCtx, {
      type: 'line',
      data: {
        labels: <?= json_encode($activityLabels) ?>,
        datasets: [
          {
            label: 'Crop Orders',
            data: <?= json_encode($activityFarmerOrders) ?>,
            borderColor: '#34D399',
            backgroundColor: gradientGreen,
            fill: true,
            tension: 0.4,
            pointRadius: 3,
            pointBackgroundColor: '#34D399',
          },
          {
            label: 'Storefront Orders',
            data: <?= json_encode($activityConsumerOrders) ?>,
            borderColor: '#E7A93A',
            backgroundColor: gradientGold,
            fill: true,
            tension: 0.4,
            pointRadius: 3,
            pointBackgroundColor: '#E7A93A',
          },
        ],
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { color: 'rgba(255,255,255,0.05)' } },
          y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(255,255,255,0.05)' } },
        },
      },
    });
  }

  const donutCtx = document.getElementById('userDonutChart');
  if (donutCtx) {
    new Chart(donutCtx, {
      type: 'doughnut',
      data: {
        labels: ['Farmers', 'Buyers', 'Consumers'],
        datasets: [{
          data: [<?= $farmers ?>, <?= $buyers ?>, <?= $consumers ?>],
          backgroundColor: ['#34D399', '#1FAE7C', '#D9642E'],
          borderColor: '#111827',
          borderWidth: 3,
        }],
      },
      options: {
        responsive: true,
        cutout: '68%',
        plugins: { legend: { display: false } },
      },
    });
  }
});
</script>

<?php include 'footer.php'; ?>
