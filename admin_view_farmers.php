<?php
require 'auth.php';
requireRole('admin');
require 'db_connect.php';
require_once 'lang.php';

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'farmer' AND (name LIKE ? OR phone LIKE ? OR district LIKE ? OR upazila LIKE ?) ORDER BY created_at DESC");
    $like = "%$search%";
    $stmt->execute([$like, $like, $like, $like]);
} else {
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'farmer' ORDER BY created_at DESC");
}
$farmers = $stmt->fetchAll();

include 'header.php';
?>

<div class="hero-band furrow">
  <span class="hero-eyebrow"><i class="bi bi-flower1"></i> <?= t('admin_view.farmers_eyebrow') ?></span>
  <h1><?= t('admin_view.farmers_title') ?></h1>
  <p><?= t('admin_view.count_farmers', ['count' => count($farmers)]) ?></p>
</div>

<a href="admin_dashboard.php" class="btn btn-outline-success mb-3"><i class="bi bi-arrow-left me-1"></i><?= t('common.back_to_dashboard') ?></a>

<form method="GET" class="mb-3" style="max-width:340px;">
  <input type="text" name="q" class="form-control" placeholder="<?= t('common.search_generic') ?>" value="<?= htmlspecialchars($search) ?>">
</form>

<?php if (count($farmers) === 0): ?>
  <div class="empty-state"><i class="bi bi-flower1"></i><h5><?= t('admin_view.farmers_empty') ?></h5></div>
<?php else: ?>
<table class="table table-hover">
  <tr><th><?= t('col.name') ?></th><th><?= t('col.phone') ?></th><th><?= t('col.district') ?></th><th><?= t('col.upazila') ?></th><th><?= t('col.primary_crop') ?></th><th><?= t('col.land_size') ?></th><th><?= t('col.joined') ?></th></tr>
  <?php foreach ($farmers as $f): ?>
    <tr>
      <td class="fw-semibold"><?= htmlspecialchars($f['name']) ?></td>
      <td><?= htmlspecialchars($f['phone']) ?></td>
      <td><?= htmlspecialchars($f['district']) ?></td>
      <td><?= htmlspecialchars($f['upazila']) ?></td>
      <td><?= htmlspecialchars($f['primary_crop'] ?: '—') ?></td>
      <td><?= htmlspecialchars($f['land_size'] ?: '—') ?></td>
      <td><small class="text-muted"><?= htmlspecialchars(date('d M Y', strtotime($f['created_at']))) ?></small></td>
    </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

<?php include 'footer.php'; ?>
