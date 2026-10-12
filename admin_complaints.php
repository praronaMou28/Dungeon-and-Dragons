<?php
require 'auth.php';
requireRole('admin');
require 'db_connect.php';
require 'notifications.php';
require_once 'lang.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $complaint_id = (int) ($_POST['complaint_id'] ?? 0);
    $status = $_POST['status'] ?? 'open';
    $response = trim($_POST['admin_response'] ?? '');

    if (in_array($status, ['open', 'in_progress', 'resolved'], true)) {
        $stmt = $pdo->prepare("SELECT user_id, subject FROM complaints WHERE id = ?");
        $stmt->execute([$complaint_id]);
        $complaint = $stmt->fetch();

        if ($complaint) {
            $update = $pdo->prepare("UPDATE complaints SET status = ?, admin_response = ? WHERE id = ?");
            $update->execute([$status, $response ?: null, $complaint_id]);

            if ($response) {
                notify($pdo, (int)$complaint['user_id'], "You have a response to your complaint: \"" . $complaint['subject'] . "\".", "complaints.php");
            }
            flash(t('admin_complaints.flash_updated'), "success");
        }
    }
    header("Location: admin_complaints.php");
    exit;
}

$filter = $_GET['status'] ?? 'all';
$sql = "SELECT c.*, u.name AS user_name, u.role AS user_role FROM complaints c JOIN users u ON c.user_id = u.id";
if (in_array($filter, ['open', 'in_progress', 'resolved'], true)) {
    $sql .= " WHERE c.status = " . $pdo->quote($filter);
}
$sql .= " ORDER BY FIELD(c.status, 'open', 'in_progress', 'resolved'), c.created_at DESC";
$complaints = $pdo->query($sql)->fetchAll();

$counts = $pdo->query("SELECT status, COUNT(*) AS cnt FROM complaints GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$STATUS_LABELS = ['open' => t('complaints.status_open'), 'in_progress' => t('complaints.status_in_progress'), 'resolved' => t('complaints.status_resolved')];

include 'header.php';
?>

<div class="hero-band furrow">
  <span class="hero-eyebrow"><i class="bi bi-flag"></i> <?= t('admin_complaints.eyebrow') ?></span>
  <h1><?= t('admin_complaints.title') ?></h1>
  <p><?= t('admin_complaints.subtitle') ?></p>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="dash-stat-card clay">
      <div class="dash-stat-top"><div class="dash-stat-icon"><i class="bi bi-exclamation-circle"></i></div></div>
      <div class="dash-stat-num"><?= $counts['open'] ?? 0 ?></div>
      <div class="dash-stat-label"><?= t('complaints.status_open') ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="dash-stat-card gold">
      <div class="dash-stat-top"><div class="dash-stat-icon"><i class="bi bi-hourglass-split"></i></div></div>
      <div class="dash-stat-num"><?= $counts['in_progress'] ?? 0 ?></div>
      <div class="dash-stat-label"><?= t('complaints.status_in_progress') ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="dash-stat-card">
      <div class="dash-stat-top"><div class="dash-stat-icon"><i class="bi bi-check-circle"></i></div></div>
      <div class="dash-stat-num"><?= $counts['resolved'] ?? 0 ?></div>
      <div class="dash-stat-label"><?= t('complaints.status_resolved') ?></div>
    </div>
  </div>
</div>

<div class="filter-chip-row mb-4">
  <a href="admin_complaints.php" class="filter-chip <?= $filter === 'all' ? 'active' : '' ?>" style="text-decoration:none;"><?= t('common.all') ?></a>
  <a href="admin_complaints.php?status=open" class="filter-chip <?= $filter === 'open' ? 'active' : '' ?>" style="text-decoration:none;"><?= t('complaints.status_open') ?></a>
  <a href="admin_complaints.php?status=in_progress" class="filter-chip <?= $filter === 'in_progress' ? 'active' : '' ?>" style="text-decoration:none;"><?= t('complaints.status_in_progress') ?></a>
  <a href="admin_complaints.php?status=resolved" class="filter-chip <?= $filter === 'resolved' ? 'active' : '' ?>" style="text-decoration:none;"><?= t('complaints.status_resolved') ?></a>
</div>

<?php if (count($complaints) === 0): ?>
  <div class="empty-state">
    <i class="bi bi-inbox"></i>
    <h5><?= t('admin_complaints.empty_title') ?></h5>
    <p class="mb-0"><?= t('admin_complaints.empty_sub') ?></p>
  </div>
<?php else: ?>
  <?php foreach ($complaints as $c): ?>
    <div class="dash-panel mb-3">
      <div class="dash-panel-title">
        <span><?= htmlspecialchars($c['subject']) ?></span>
        <span class="pill status-pill-<?= $c['status'] ?>"><?= $STATUS_LABELS[$c['status']] ?></span>
      </div>
      <p class="text-muted mb-1" style="font-size:0.8rem;">
        <i class="bi bi-person me-1"></i><?= htmlspecialchars($c['user_name']) ?> (<?= t('role.' . $c['user_role']) ?>)
        &middot; <?= htmlspecialchars(date('d M Y, h:i A', strtotime($c['created_at']))) ?>
      </p>
      <p class="mb-3"><?= nl2br(htmlspecialchars($c['message'])) ?></p>

      <form method="POST" class="row g-2 align-items-end">
        <input type="hidden" name="complaint_id" value="<?= $c['id'] ?>">
        <div class="col-md-8">
          <label class="form-label" style="font-size:0.8rem;"><?= t('admin_complaints.response_label') ?> <span class="text-muted">(<?= t('admin_complaints.response_hint') ?>)</span></label>
          <textarea name="admin_response" class="form-control" rows="2"><?= htmlspecialchars($c['admin_response'] ?? '') ?></textarea>
        </div>
        <div class="col-md-2">
          <label class="form-label" style="font-size:0.8rem;"><?= t('admin_complaints.status_label') ?></label>
          <select name="status" class="form-select">
            <option value="open" <?= $c['status'] === 'open' ? 'selected' : '' ?>><?= t('complaints.status_open') ?></option>
            <option value="in_progress" <?= $c['status'] === 'in_progress' ? 'selected' : '' ?>><?= t('complaints.status_in_progress') ?></option>
            <option value="resolved" <?= $c['status'] === 'resolved' ? 'selected' : '' ?>><?= t('complaints.status_resolved') ?></option>
          </select>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-success w-100"><?= t('common.save') ?></button>
        </div>
      </form>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php include 'footer.php'; ?>
