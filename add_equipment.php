<?php
require 'auth.php';
requireRole('farmer');
require 'db_connect.php';
require 'notifications.php';
require_once 'lang.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = trim($_POST['equipment_type']);
    $note = trim($_POST['contact_note']);

    $stmt = $pdo->prepare("INSERT INTO equipment_listings (owner_id, equipment_type, contact_note) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $type, $note]);

    flash(t('add_equipment.flash_success'), "success");
    header("Location: equipment.php");
    exit;
}

include 'header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card p-4">
      <div class="d-flex align-items-center gap-3 mb-3">
        <div class="icon-tile"><i class="bi bi-tools"></i></div>
        <div>
          <h5 class="mb-0" style="font-family:'Poppins',sans-serif;"><?= t('add_equipment.title') ?></h5>
          <small class="text-muted"><?= t('add_equipment.subtitle') ?></small>
        </div>
      </div>
      <form method="POST">
        <div class="mb-3">
          <label class="form-label"><i class="bi bi-truck-front me-1"></i><?= t('add_equipment.type') ?></label>
          <input type="text" name="equipment_type" class="form-control" placeholder="<?= t('add_equipment.type_ph') ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label"><i class="bi bi-chat-left-text me-1"></i><?= t('add_equipment.contact_note') ?></label>
          <input type="text" name="contact_note" class="form-control" placeholder="<?= t('add_equipment.contact_note_ph') ?>" required>
        </div>
        <button type="submit" class="btn btn-success w-100 py-2"><i class="bi bi-plus-circle me-1"></i><?= t('add_equipment.submit') ?></button>
      </form>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
