<?php
require 'auth.php';
requireRole('admin');
require 'db_connect.php';
require_once 'lang.php';

$search = trim($_GET['q'] ?? '');
$where = "u.role = 'buyer'";
$params = [];
if ($search !== '') {
    $where .= " AND (u.name LIKE ? OR u.phone LIKE ? OR u.business_name LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like];
}

$stmt = $pdo->prepare("SELECT u.*, v.status AS verification_status, v.trade_license, v.nid
                        FROM users u
                        LEFT JOIN verified_buyers v ON v.id = (
                            SELECT id FROM verified_buyers WHERE user_id = u.id ORDER BY id DESC LIMIT 1
                        )
                        WHERE $where
                        ORDER BY u.created_at DESC");
$stmt->execute($params);
$buyers = $stmt->fetchAll();

include 'header.php';
?>

<div class="hero-band furrow">
  <span class="hero-eyebrow"><i class="bi bi-shop"></i> <?= t('admin_view.buyers_eyebrow') ?></span>
  <h1><?= t('admin_view.buyers_title') ?></h1>
  <p><?= t('admin_view.count_buyers', ['count' => count($buyers)]) ?></p>
</div>

<a href="admin_dashboard.php" class="btn btn-outline-success mb-3"><i class="bi bi-arrow-left me-1"></i><?= t('common.back_to_dashboard') ?></a>

<form method="GET" class="mb-3" style="max-width:340px;">
  <input type="text" name="q" class="form-control" placeholder="<?= t('common.search_generic') ?>" value="<?= htmlspecialchars($search) ?>">
</form>

<?php if (count($buyers) === 0): ?>
  <div class="empty-state"><i class="bi bi-shop"></i><h5><?= t('admin_view.buyers_empty') ?></h5></div>
<?php else: ?>
<table class="table table-hover">
  <tr><th><?= t('col.name') ?></th><th><?= t('col.business') ?></th><th><?= t('col.phone') ?></th><th><?= t('col.type') ?></th><th><?= t('col.verification') ?></th><th><?= t('col.trade_license') ?></th><th><?= t('col.joined') ?></th></tr>
  <?php foreach ($buyers as $b): ?>
    <tr>
      <td class="fw-semibold"><?= htmlspecialchars($b['name']) ?></td>
      <td><?= htmlspecialchars($b['business_name'] ?: '—') ?></td>
      <td><?= htmlspecialchars($b['phone']) ?></td>
      <td><?= htmlspecialchars($b['business_type'] ?: '—') ?></td>
      <td>
        <?php if ($b['verification_status'] === 'approved'): ?>
          <span class="pill pill-green"><i class="bi bi-patch-check-fill"></i> <?= t('common.approved') ?></span>
        <?php elseif ($b['verification_status'] === 'pending'): ?>
          <span class="pill pill-gold"><i class="bi bi-hourglass-split"></i> <?= t('common.pending') ?></span>
        <?php elseif ($b['verification_status'] === 'rejected'): ?>
          <span class="pill pill-danger"><i class="bi bi-x-circle"></i> <?= t('common.rejected') ?></span>
        <?php else: ?>
          <span class="pill pill-gray"><?= t('common.not_applied') ?></span>
        <?php endif; ?>
      </td>
      <td><small class="text-muted"><?= htmlspecialchars($b['trade_license'] ?: '—') ?></small></td>
      <td><small class="text-muted"><?= htmlspecialchars(date('d M Y', strtotime($b['created_at']))) ?></small></td>
    </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

<?php include 'footer.php'; ?>
