<?php
require 'auth.php';
requireRole('buyer');
require 'db_connect.php';
require 'notifications.php';
require 'upload_helper.php';
require_once 'lang.php';

$stmtV = $pdo->prepare("SELECT status FROM verified_buyers WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmtV->execute([$_SESSION['user_id']]);
if ($stmtV->fetchColumn() !== 'approved') {
    header("Location: buyer_dashboard.php");
    exit;
}

$categories = ['Vegetables', 'Fruits', 'Grains', 'Dairy', 'Spices', 'Other'];
$categoryKeys = ['Vegetables'=>'cat.vegetables','Fruits'=>'cat.fruits','Grains'=>'cat.grains','Dairy'=>'cat.dairy','Spices'=>'cat.spices','Other'=>'cat.other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = trim($_POST['product_name']);
    $category = in_array($_POST['category'], $categories) ? $_POST['category'] : 'Other';
    $price = $_POST['price_per_unit'];
    $unit_label = trim($_POST['unit_label']) ?: 'unit';
    $stock = $_POST['stock_qty'];
    $image_path = handleImageUpload('product_image', 'storefront');

    $stmt = $pdo->prepare("INSERT INTO storefront_listings (buyer_id, product_name, category, price_per_unit, unit_label, stock_qty, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $product_name, $category, $price, $unit_label, $stock, $image_path]);

    flash(t('add_storefront.flash_success', ['product' => $product_name]), "success");
    header("Location: buyer_dashboard.php");
    exit;
}

include 'header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card p-4">
      <div class="d-flex align-items-center gap-3 mb-3">
        <div class="icon-tile"><i class="bi bi-shop-window"></i></div>
        <div>
          <h5 class="mb-0" style="font-family:'Poppins',sans-serif;"><?= t('add_storefront.title') ?></h5>
          <small class="text-muted"><?= t('add_storefront.subtitle') ?></small>
        </div>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
          <label class="form-label"><i class="bi bi-tag me-1"></i><?= t('add_storefront.product_name') ?></label>
          <input type="text" name="product_name" class="form-control" placeholder="<?= t('add_storefront.product_name_ph') ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label"><i class="bi bi-grid me-1"></i><?= t('add_storefront.category') ?></label>
          <select name="category" class="form-control">
            <?php foreach ($categories as $c): ?>
              <option value="<?= htmlspecialchars($c) ?>"><?= t($categoryKeys[$c]) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="row">
          <div class="col-6 mb-3">
            <label class="form-label"><i class="bi bi-cash me-1"></i><?= t('add_storefront.price') ?></label>
            <input type="number" step="0.01" name="price_per_unit" class="form-control" required>
          </div>
          <div class="col-6 mb-3">
            <label class="form-label"><i class="bi bi-rulers me-1"></i><?= t('add_storefront.unit_label') ?></label>
            <input type="text" name="unit_label" class="form-control" placeholder="<?= t('add_storefront.unit_label_ph') ?>" value="kg">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label"><i class="bi bi-box-seam me-1"></i><?= t('add_storefront.stock') ?></label>
          <input type="number" step="0.01" name="stock_qty" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label"><i class="bi bi-image me-1"></i><?= t('add_storefront.photo') ?> <span class="text-muted">(<?= t('common.optional') ?>)</span></label>
          <input type="file" name="product_image" accept="image/jpeg,image/png,image/webp" class="form-control" id="prodImageInput">
          <img id="prodImagePreview" class="d-none mt-2 rounded" style="max-height:160px;max-width:100%;object-fit:cover;">
          <small class="text-muted d-block mt-1"><?= t('common.photo_hint_short') ?></small>
        </div>
        <button type="submit" class="btn btn-success w-100 py-2"><i class="bi bi-plus-circle me-1"></i><?= t('add_storefront.submit') ?></button>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('prodImageInput').addEventListener('change', function(e) {
  const preview = document.getElementById('prodImagePreview');
  const file = e.target.files[0];
  if (!file) { preview.classList.add('d-none'); return; }
  const reader = new FileReader();
  reader.onload = ev => { preview.src = ev.target.result; preview.classList.remove('d-none'); };
  reader.readAsDataURL(file);
});
</script>

<?php include 'footer.php'; ?>
