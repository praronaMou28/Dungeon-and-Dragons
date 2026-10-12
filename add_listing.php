<?php
require 'auth.php';
requireRole('farmer');
require 'db_connect.php';
require 'notifications.php';
require 'upload_helper.php';
require_once 'lang.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $crop_name = trim($_POST['crop_name']);
    $quantity = $_POST['quantity_kg'];
    $price = $_POST['price_per_kg'];
    $image_path = handleImageUpload('crop_image', 'crops');

    $stmt = $pdo->prepare("INSERT INTO crop_listings (farmer_id, crop_name, quantity_kg, price_per_kg, image_path) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $crop_name, $quantity, $price, $image_path]);

    flash(t('add_listing.flash_success', ['crop' => $crop_name]), "success");
    header("Location: farmer_dashboard.php");
    exit;
}

include 'header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card p-4">
      <div class="d-flex align-items-center gap-3 mb-3">
        <div class="icon-tile gold"><i class="bi bi-flower1"></i></div>
        <div>
          <h5 class="mb-0" style="font-family:'Poppins',sans-serif;"><?= t('add_listing.title') ?></h5>
          <small class="text-muted"><?= t('add_listing.subtitle') ?></small>
        </div>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
          <label class="form-label"><i class="bi bi-flower1 me-1"></i><?= t('add_listing.crop_name') ?></label>
          <input type="text" name="crop_name" class="form-control" placeholder="<?= t('add_listing.crop_name_ph') ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label"><i class="bi bi-box-seam me-1"></i><?= t('add_listing.quantity') ?></label>
          <input type="number" step="0.01" name="quantity_kg" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label"><i class="bi bi-cash me-1"></i><?= t('add_listing.price') ?></label>
          <input type="number" step="0.01" name="price_per_kg" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label"><i class="bi bi-image me-1"></i><?= t('add_listing.photo') ?> <span class="text-muted">(<?= t('common.optional') ?>)</span></label>
          <input type="file" name="crop_image" accept="image/jpeg,image/png,image/webp" class="form-control" id="cropImageInput">
          <img id="cropImagePreview" class="d-none mt-2 rounded" style="max-height:160px;max-width:100%;object-fit:cover;">
          <small class="text-muted d-block mt-1"><?= t('add_listing.photo_hint') ?></small>
        </div>
        <button type="submit" class="btn btn-success w-100 py-2"><i class="bi bi-plus-circle me-1"></i><?= t('add_listing.submit') ?></button>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('cropImageInput').addEventListener('change', function(e) {
  const preview = document.getElementById('cropImagePreview');
  const file = e.target.files[0];
  if (!file) { preview.classList.add('d-none'); return; }
  const reader = new FileReader();
  reader.onload = ev => { preview.src = ev.target.result; preview.classList.remove('d-none'); };
  reader.readAsDataURL(file);
});
</script>

<?php include 'footer.php'; ?>
