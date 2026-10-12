<?php
require 'auth.php';
requireRole('admin');
require 'db_connect.php';
require_once 'lang.php';

$stmt = $pdo->query("SELECT c.*, u.name AS farmer_name, u.phone, u.district, u.upazila
                      FROM crop_listings c
                      JOIN users u ON c.farmer_id = u.id
                      WHERE c.status = 'available' AND c.quantity_kg > 0
                      ORDER BY c.created_at DESC");
$listings = $stmt->fetchAll();

include 'header.php';
?>

<div class="hero-band furrow">
  <span class="hero-eyebrow"><i class="bi bi-box-seam"></i> <?= t('admin_view.listings_eyebrow') ?></span>
  <h1><?= t('admin_view.listings_title') ?></h1>
  <p><?= t('admin_view.count_listings', ['count' => count($listings)]) ?></p>
</div>

<a href="admin_dashboard.php" class="btn btn-outline-success mb-3"><i class="bi bi-arrow-left me-1"></i><?= t('common.back_to_dashboard') ?></a>

<?php if (count($listings) === 0): ?>
  <div class="empty-state"><i class="bi bi-box-seam"></i><h5><?= t('admin_view.listings_empty') ?></h5></div>
<?php else: ?>
<table class="table table-hover">
  <tr><th><?= t('col.crop') ?></th><th><?= t('col.farmer') ?></th><th><?= t('col.phone') ?></th><th><?= t('col.location') ?></th><th><?= t('col.remaining_qty') ?></th><th><?= t('col.price_per_kg') ?></th><th><?= t('col.listed') ?></th></tr>
  <?php foreach ($listings as $l): ?>
    <tr>
      <td class="fw-semibold"><?= htmlspecialchars($l['crop_name']) ?></td>
      <td><?= htmlspecialchars($l['farmer_name']) ?></td>
      <td><?= htmlspecialchars($l['phone']) ?></td>
      <td><?= htmlspecialchars($l['upazila']) ?>, <?= htmlspecialchars($l['district']) ?></td>
      <td><span class="pill pill-green"><?= htmlspecialchars($l['quantity_kg']) ?> kg</span></td>
      <td>৳<?= number_format($l['price_per_kg'], 2) ?></td>
      <td><small class="text-muted"><?= htmlspecialchars(date('d M Y', strtotime($l['created_at']))) ?></small></td>
    </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

<?php include 'footer.php'; ?>
