<?php
require 'auth.php';
requireRole('consumer');
require 'db_connect.php';
require 'review_helpers.php';
require 'notifications.php';
require_once 'lang.php';

$order_id = (int) ($_GET['order_id'] ?? $_POST['order_id'] ?? 0);

$stmt = $pdo->prepare("SELECT co.*, s.product_name, s.buyer_id AS seller_id, u.name AS seller_name, u.business_name FROM consumer_orders co
                        JOIN storefront_listings s ON co.storefront_id = s.id
                        JOIN users u ON s.buyer_id = u.id
                        WHERE co.id = ? AND co.consumer_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order || $order['status'] !== 'completed') {
    header("Location: consumer_dashboard.php");
    exit;
}

$existing = $pdo->prepare("SELECT * FROM reviews WHERE reviewer_id = ? AND order_type = 'storefront' AND order_id = ?");
$existing->execute([$_SESSION['user_id'], $order_id]);
$existingReview = $existing->fetch();

$error = "";
$sellerLabel = $order['business_name'] ?: $order['seller_name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existingReview) {
    $rating = (int) ($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = t('review.err_rating');
    } else {
        $ins = $pdo->prepare("INSERT INTO reviews (reviewer_id, target_id, order_type, order_id, rating, comment) VALUES (?, ?, 'storefront', ?, ?, ?)");
        $ins->execute([$_SESSION['user_id'], $order['seller_id'], $order_id, $rating, $comment ?: null]);

        flash(t('review.flash_thanks', ['name' => $sellerLabel]), "success");
        header("Location: consumer_dashboard.php");
        exit;
    }
}

include 'header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card p-4">
      <div class="d-flex align-items-center gap-3 mb-3">
        <div class="icon-tile gold"><i class="bi bi-star"></i></div>
        <div>
          <h5 class="mb-0" style="font-family:'Poppins',sans-serif;"><?= t('review.rate', ['name' => htmlspecialchars($sellerLabel)]) ?></h5>
          <small class="text-muted"><?= t('review.for_order', ['item' => htmlspecialchars($order['product_name']), 'qty' => htmlspecialchars($order['quantity'])]) ?></small>
        </div>
      </div>

      <?php if ($existingReview): ?>
        <div class="alert alert-success mb-0"><i class="bi bi-check-circle me-1"></i><?= t('review.already_reviewed') ?>
          <?= renderStars($existingReview['rating']) ?>
          <?php if ($existingReview['comment']): ?><p class="mt-2 mb-0 text-muted">&ldquo;<?= htmlspecialchars($existingReview['comment']) ?>&rdquo;</p><?php endif; ?>
        </div>
      <?php else: ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST">
          <input type="hidden" name="order_id" value="<?= $order_id ?>">
          <div class="mb-4 text-center">
            <div class="star-input mx-auto" style="width:fit-content;">
              <input type="radio" name="rating" id="r5" value="5"><label for="r5"><i class="bi bi-star-fill"></i></label>
              <input type="radio" name="rating" id="r4" value="4"><label for="r4"><i class="bi bi-star-fill"></i></label>
              <input type="radio" name="rating" id="r3" value="3"><label for="r3"><i class="bi bi-star-fill"></i></label>
              <input type="radio" name="rating" id="r2" value="2"><label for="r2"><i class="bi bi-star-fill"></i></label>
              <input type="radio" name="rating" id="r1" value="1"><label for="r1"><i class="bi bi-star-fill"></i></label>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label"><?= t('review.comment') ?> <span class="text-muted">(<?= t('common.optional') ?>)</span></label>
            <textarea name="comment" class="form-control" rows="3" placeholder="<?= t('review.seller_comment_ph') ?>" maxlength="500"></textarea>
          </div>
          <button type="submit" class="btn btn-success w-100 py-2"><i class="bi bi-send-check me-1"></i><?= t('review.submit') ?></button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
