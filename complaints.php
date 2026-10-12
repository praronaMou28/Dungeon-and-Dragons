<?php
require 'auth.php';
requireLogin();
require 'db_connect.php';
require 'notifications.php';
require_once 'lang.php';

if ($_SESSION['role'] === 'admin') {
    header("Location: admin_complaints.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($subject !== '' && $message !== '') {
        $ins = $pdo->prepare("INSERT INTO complaints (user_id, subject, message) VALUES (?, ?, ?)");
        $ins->execute([$_SESSION['user_id'], $subject, $message]);
        flash(t('complaints.flash_success'), "success");
        header("Location: complaints.php");
        exit;
    }
    flash(t('complaints.flash_missing_fields'), "danger");
}

$stmt = $pdo->prepare("SELECT * FROM complaints WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$complaints = $stmt->fetchAll();

$STATUS_LABELS = ['open' => t('complaints.status_open'), 'in_progress' => t('complaints.status_in_progress'), 'resolved' => t('complaints.status_resolved')];

include 'header.php';
?>

<div class="hero-band furrow">
  <span class="hero-eyebrow"><i class="bi bi-flag"></i> <?= t('complaints.eyebrow') ?></span>
  <h1><?= t('complaints.title') ?></h1>
  <p><?= t('complaints.subtitle') ?></p>
</div>

<div class="row">
  <div class="col-lg-5 mb-4">
    <div class="card p-4">
      <h5 class="mb-3" style="font-family:'Poppins',sans-serif;"><i class="bi bi-pencil-square me-2"></i><?= t('complaints.new_complaint') ?></h5>
      <form method="POST">
        <div class="mb-3">
          <label class="form-label"><?= t('complaints.subject') ?></label>
          <input type="text" name="subject" class="form-control" placeholder="<?= t('complaints.subject_ph') ?>" maxlength="150" required>
        </div>
        <div class="mb-3">
          <label class="form-label"><?= t('complaints.details') ?></label>
          <textarea name="message" class="form-control" rows="5" placeholder="<?= t('complaints.details_ph') ?>" required></textarea>
        </div>
        <button type="submit" class="btn btn-danger w-100 py-2"><i class="bi bi-send me-1"></i><?= t('complaints.submit') ?></button>
      </form>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="dash-panel">
      <div class="dash-panel-title"><span><?= t('complaints.my_complaints') ?></span></div>
      <?php if (count($complaints) === 0): ?>
        <div class="empty-state py-3"><i class="bi bi-inbox"></i><p class="mb-0"><?= t('complaints.empty') ?></p></div>
      <?php else: ?>
        <?php foreach ($complaints as $c): ?>
          <div class="review-row">
            <div class="review-head">
              <span class="review-author"><?= htmlspecialchars($c['subject']) ?></span>
              <span class="pill status-pill-<?= $c['status'] ?>"><?= $STATUS_LABELS[$c['status']] ?></span>
            </div>
            <p class="review-comment mb-1"><?= nl2br(htmlspecialchars($c['message'])) ?></p>
            <div class="review-date mb-2"><?= t('complaints.filed_on') ?> <?= htmlspecialchars(date('d M Y, h:i A', strtotime($c['created_at']))) ?></div>
            <?php if ($c['admin_response']): ?>
              <div class="alert alert-success mb-0 py-2 px-3" style="font-size:0.84rem;">
                <strong><i class="bi bi-reply-fill me-1"></i><?= t('complaints.admin_response') ?>:</strong> <?= nl2br(htmlspecialchars($c['admin_response'])) ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
