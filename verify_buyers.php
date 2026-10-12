<?php
require 'auth.php';
requireRole('admin');
require 'db_connect.php';
require 'notifications.php';
require_once 'lang.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['action'])) {
    $newStatus = ($_POST['action'] === 'approve') ? 'approved' : 'rejected';
    $stmt = $pdo->prepare("UPDATE verified_buyers SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $_POST['id']]);

    $ownerStmt = $pdo->prepare("SELECT user_id FROM verified_buyers WHERE id = ?");
    $ownerStmt->execute([$_POST['id']]);
    if ($buyerId = $ownerStmt->fetchColumn()) {
        $verb = $newStatus === 'approved' ? 'approved' : 'rejected';
        notify($pdo, (int)$buyerId, "Your buyer verification was $verb by admin.", "buyer_dashboard.php");
    }

    flash(t('verify_buyers.flash_' . $newStatus), $newStatus === 'approved' ? 'success' : 'warning');
    header("Location: verify_buyers.php");
    exit;
}

$stmt = $pdo->prepare("SELECT v.*, u.name, u.phone, u.business_name FROM verified_buyers v
                        JOIN users u ON v.user_id = u.id
                        WHERE v.status = 'pending'
                        ORDER BY v.applied_at ASC");
$stmt->execute();
$pending = $stmt->fetchAll();

include 'header.php';
?>

<div class="hero-band furrow">
  <span class="hero-eyebrow"><i class="bi bi-patch-check"></i> <?= t('verify_buyers.eyebrow') ?></span>
  <h1><?= t('verify_buyers.title') ?></h1>
  <p><?= t('verify_buyers.count_pending', ['count' => count($pending)]) ?></p>
</div>

<a href="admin_dashboard.php" class="btn btn-outline-success mb-3"><i class="bi bi-arrow-left me-1"></i><?= t('common.back_to_dashboard') ?></a>

<?php if (count($pending) === 0): ?>
  <div class="empty-state"><i class="bi bi-patch-check"></i><h5><?= t('verify_buyers.empty_title') ?></h5><p class="mb-0"><?= t('verify_buyers.empty_sub') ?></p></div>
<?php else: ?>
<table class="table table-hover">
  <tr><th><?= t('col.name') ?></th><th><?= t('col.business') ?></th><th><?= t('col.phone') ?></th><th><?= t('col.trade_license') ?></th><th><?= t('col.nid') ?></th><th><?= t('common.action') ?></th></tr>
  <?php foreach ($pending as $p): ?>
    <tr>
      <td class="fw-semibold"><?= htmlspecialchars($p['name']) ?></td>
      <td><?= htmlspecialchars($p['business_name']) ?></td>
      <td><?= htmlspecialchars($p['phone']) ?></td>
      <td><?= htmlspecialchars($p['trade_license']) ?></td>
      <td><?= htmlspecialchars($p['nid']) ?></td>
      <td>
        <form method="POST" class="d-inline">
          <input type="hidden" name="id" value="<?= $p['id'] ?>">
          <input type="hidden" name="action" value="approve">
          <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> <?= t('common.approve') ?></button>
        </form>
        <form method="POST" class="d-inline" onsubmit="return confirm(<?= json_encode(t('verify_buyers.confirm_reject')) ?>);">
          <input type="hidden" name="id" value="<?= $p['id'] ?>">
          <input type="hidden" name="action" value="reject">
          <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-x-lg"></i> <?= t('common.reject') ?></button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

<?php include 'footer.php'; ?>
