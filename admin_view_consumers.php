<?php
require 'auth.php';
requireRole('admin');
require 'db_connect.php';
require_once 'lang.php';

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'consumer' AND (name LIKE ? OR phone LIKE ? OR district LIKE ?) ORDER BY created_at DESC");
    $like = "%$search%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'consumer' ORDER BY created_at DESC");
}
$consumers = $stmt->fetchAll();

include 'header.php';
?>

<div class="hero-band furrow">
  <span class="hero-eyebrow"><i class="bi bi-people"></i> <?= t('admin_view.consumers_eyebrow') ?></span>
  <h1><?= t('admin_view.consumers_title') ?></h1>
  <p><?= t('admin_view.count_consumers', ['count' => count($consumers)]) ?></p>
</div>

<a href="admin_dashboard.php" class="btn btn-outline-success mb-3"><i class="bi bi-arrow-left me-1"></i><?= t('common.back_to_dashboard') ?></a>

<form method="GET" class="mb-3" style="max-width:340px;">
  <input type="text" name="q" class="form-control" placeholder="<?= t('common.search_generic') ?>" value="<?= htmlspecialchars($search) ?>">
</form>

<?php if (count($consumers) === 0): ?>
  <div class="empty-state"><i class="bi bi-people"></i><h5><?= t('admin_view.consumers_empty') ?></h5></div>
<?php else: ?>
<table class="table table-hover">
  <tr><th><?= t('col.name') ?></th><th><?= t('col.phone') ?></th><th><?= t('col.district') ?></th><th><?= t('col.upazila') ?></th><th><?= t('col.joined') ?></th></tr>
  <?php foreach ($consumers as $c): ?>
    <tr>
      <td class="fw-semibold"><?= htmlspecialchars($c['name']) ?></td>
      <td><?= htmlspecialchars($c['phone']) ?></td>
      <td><?= htmlspecialchars($c['district']) ?></td>
      <td><?= htmlspecialchars($c['upazila']) ?></td>
      <td><small class="text-muted"><?= htmlspecialchars(date('d M Y', strtotime($c['created_at']))) ?></small></td>
    </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

<?php include 'footer.php'; ?>
