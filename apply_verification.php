<?php
require 'auth.php';
requireRole('buyer');
require 'db_connect.php';
require 'notifications.php';
require_once 'lang.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $check = $pdo->prepare("SELECT status FROM verified_buyers WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $check->execute([$_SESSION['user_id']]);
    $existing = $check->fetchColumn();

    if ($existing === 'pending' || $existing === 'approved') {
        header("Location: buyer_dashboard.php");
        exit;
    }

    $trade_license = trim($_POST['trade_license']);
    $nid = trim($_POST['nid']);

    $stmt = $pdo->prepare("INSERT INTO verified_buyers (user_id, trade_license, nid, status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([$_SESSION['user_id'], $trade_license, $nid]);

    flash(t('apply_verification.flash_success'), "success");
    header("Location: buyer_dashboard.php");
    exit;
}

include 'header.php';
?>

<div class="hero-band furrow">
  <span class="hero-eyebrow"><i class="bi bi-patch-check"></i> <?= t('apply_verification.eyebrow') ?></span>
  <h1><?= t('apply_verification.title') ?></h1>
  <p><?= t('apply_verification.subtitle') ?></p>
</div>

<div class="row justify-content-center">
  <div class="col-lg-7">

    <div class="d-flex gap-3 mb-4">
      <div class="d-flex align-items-center gap-2 text-primary">
        <span class="pill pill-green"><i class="bi bi-1-circle-fill"></i></span>
        <span class="fw-semibold" style="font-size:0.9rem;"><?= t('apply_verification.step_submit') ?></span>
      </div>
      <div class="d-flex align-items-center gap-2 text-muted">
        <span class="pill pill-gray"><i class="bi bi-2-circle"></i></span>
        <span style="font-size:0.9rem;"><?= t('apply_verification.step_review') ?></span>
      </div>
      <div class="d-flex align-items-center gap-2 text-muted">
        <span class="pill pill-gray"><i class="bi bi-3-circle"></i></span>
        <span style="font-size:0.9rem;"><?= t('apply_verification.step_verified') ?></span>
      </div>
    </div>

    <div class="card p-4">
      <div class="d-flex align-items-center gap-3 mb-3">
        <div class="icon-tile gold"><i class="bi bi-file-earmark-text"></i></div>
        <div>
          <h5 class="mb-0" style="font-family:'Poppins',sans-serif;"><?= t('apply_verification.form_title') ?></h5>
          <small class="text-muted"><?= t('apply_verification.form_subtitle') ?></small>
        </div>
      </div>
      <form method="POST">
        <div class="mb-3">
          <label class="form-label"><i class="bi bi-briefcase me-1"></i><?= t('register.trade_license') ?></label>
          <input type="text" name="trade_license" class="form-control" placeholder="<?= t('apply_verification.trade_license_ph') ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label"><i class="bi bi-person-vcard me-1"></i><?= t('apply_verification.nid_label') ?></label>
          <input type="text" name="nid" class="form-control" placeholder="<?= t('apply_verification.nid_ph') ?>" required>
        </div>
        <button type="submit" class="btn btn-success w-100 py-2"><i class="bi bi-send-check me-1"></i><?= t('apply_verification.submit') ?></button>
      </form>
      <p class="text-muted mt-3 mb-0" style="font-size:0.8rem;"><i class="bi bi-shield-lock me-1"></i><?= t('apply_verification.privacy_note') ?></p>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
