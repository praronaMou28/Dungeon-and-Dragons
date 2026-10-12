<?php
require 'auth.php';
requireRole('buyer');
require 'db_connect.php';
require 'notifications.php';
require_once 'lang.php';

$stmt = $pdo->prepare("SELECT c.id AS cart_id, c.quantity_kg AS cart_qty, l.* FROM crop_cart_items c
                        JOIN crop_listings l ON c.listing_id = l.id
                        WHERE c.buyer_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll();

if (count($cartItems) === 0) {
    header("Location: crop_cart.php");
    exit;
}

$total = array_sum(array_map(fn($i) => $i['cart_qty'] * $i['price_per_kg'], $cartItems));
$error = "";

$stmtU = $pdo->prepare("SELECT district, upazila, phone FROM users WHERE id = ?");
$stmtU->execute([$_SESSION['user_id']]);
$userInfo = $stmtU->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['delivery_address'] ?? '');
    $phone = trim($_POST['delivery_phone'] ?? '');
    $note = trim($_POST['delivery_note'] ?? '');
    $method = $_POST['payment_method'] ?? '';

    if ($address === '') {
        $error = t('checkout_buyer.err_address');
    } elseif (!preg_match('/^01[0-9]{9}$/', $phone)) {
        $error = t('checkout_buyer.err_phone');
    } elseif ($method === 'bkash') {
        $bkashNumber = trim($_POST['bkash_number'] ?? '');
        $pin = trim($_POST['bkash_pin'] ?? '');
        if (!preg_match('/^01[0-9]{9}$/', $bkashNumber)) {
            $error = t('checkout.err_bkash_number');
        } elseif (strlen($pin) < 4) {
            $error = t('checkout.err_bkash_pin');
        }
    } elseif ($method === 'card') {
        $cardNumber = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
        $expiry = trim($_POST['card_expiry'] ?? '');
        $cvv = trim($_POST['card_cvv'] ?? '');
        if (!preg_match('/^\d{16}$/', $cardNumber)) {
            $error = t('checkout.err_card_number');
        } elseif (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) {
            $error = t('checkout.err_card_expiry');
        } elseif (!preg_match('/^\d{3,4}$/', $cvv)) {
            $error = t('checkout.err_cvv');
        }
    } elseif ($method !== 'cod') {
        $error = t('checkout.err_method');
    }

    if (!$error) {
        $pdo->beginTransaction();
        $ok = true;

        foreach ($cartItems as $item) {
            $lock = $pdo->prepare("SELECT quantity_kg, status FROM crop_listings WHERE id = ? FOR UPDATE");
            $lock->execute([$item['id']]);
            $current = $lock->fetch();
            if (!$current || $current['status'] !== 'available' || $current['quantity_kg'] < $item['cart_qty']) {
                $ok = false;
                $error = t('checkout_buyer.err_stock', ['crop' => $item['crop_name']]);
                break;
            }
        }

        if ($ok) {
            $groupRef = 'CRP' . strtoupper(bin2hex(random_bytes(5)));
            $status = ($method === 'cod') ? 'cod' : 'unpaid';

            foreach ($cartItems as $item) {
                $lineTotal = $item['cart_qty'] * $item['price_per_kg'];
                $ins = $pdo->prepare("INSERT INTO orders
                    (listing_id, buyer_id, group_ref, quantity_kg, delivery_address, delivery_phone, delivery_note,
                     status, payment_method, payment_status, paid_amount, transaction_ref, paid_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'requested', ?, ?, ?, NULL, ?)");
                $ins->execute([
                    $item['id'], $_SESSION['user_id'], $groupRef, $item['cart_qty'],
                    $address, $phone, $note, $method, $status, $lineTotal,
                    $method === 'cod' ? date('Y-m-d H:i:s') : null,
                ]);

                $methodLabel = $method === 'cod' ? t('checkout.cod') : strtoupper($method);
                notifyListingOwners(
                    $pdo,
                    $item['id'],
                    $_SESSION['name'] . " requested " . $item['cart_qty'] . " kg of " . $item['crop_name'] . " (৳" . number_format($lineTotal, 2) . ", " . $methodLabel . "). Review the request.",
                    "farmer_dashboard.php"
                );
            }

            $pdo->prepare("DELETE FROM crop_cart_items WHERE buyer_id = ?")->execute([$_SESSION['user_id']]);
            $pdo->commit();

            if ($method === 'cod') {
                flash(t('checkout_buyer.flash_cod', ['amount' => number_format($total, 2)]), "success");
            } else {
                flash(t('checkout_buyer.flash_requested', ['amount' => number_format($total, 2), 'method' => strtoupper($method)]), "success");
            }

            header("Location: order_confirmation_buyer.php?group=" . $groupRef);
            exit;
        } else {
            $pdo->rollBack();
        }
    }
}

include 'header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-8">

    <div class="checkout-steps">
      <div class="checkout-step done"><span class="dot"><i class="bi bi-check"></i></span> <?= t('checkout.step_cart') ?></div>
      <div class="checkout-connector"></div>
      <div class="checkout-step active"><span class="dot">2</span> <?= t('checkout.step_delivery') ?></div>
      <div class="checkout-connector"></div>
      <div class="checkout-step"><span class="dot">3</span> <?= t('checkout.step_done') ?></div>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row g-3">
      <div class="col-md-7">
        <div class="card p-4 mb-3">
          <h5 class="mb-3" style="font-family:'Poppins',sans-serif;"><i class="bi bi-geo-alt me-2"></i><?= t('checkout.delivery_details') ?></h5>
          <form method="POST" id="checkoutForm">
            <div class="mb-3">
              <label class="form-label"><?= t('checkout_buyer.delivery_address') ?></label>
              <textarea name="delivery_address" class="form-control" rows="2" placeholder="<?= t('checkout.address_ph') ?>" required><?= htmlspecialchars($_POST['delivery_address'] ?? ($userInfo['upazila'] . ', ' . $userInfo['district'])) ?></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label"><?= t('checkout.contact_number') ?></label>
              <input type="text" name="delivery_phone" class="form-control" placeholder="01XXXXXXXXX" value="<?= htmlspecialchars($_POST['delivery_phone'] ?? $userInfo['phone']) ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label"><?= t('checkout_buyer.note_to_farmer') ?> <span class="text-muted">(<?= t('common.optional') ?>)</span></label>
              <input type="text" name="delivery_note" class="form-control" placeholder="<?= t('checkout_buyer.note_ph') ?>" value="<?= htmlspecialchars($_POST['delivery_note'] ?? '') ?>">
            </div>

            <label class="form-label fw-semibold"><?= t('checkout.choose_payment') ?></label>
            <input type="hidden" name="payment_method" id="methodValue" value="">
            <div class="method-options mb-3">
              <label class="method-card" data-method="bkash">
                <input type="radio" name="method_radio" value="bkash">
                <i class="bi bi-phone"></i>
                <div class="label"><?= t('checkout.bkash') ?></div>
              </label>
              <label class="method-card" data-method="card">
                <input type="radio" name="method_radio" value="card">
                <i class="bi bi-credit-card"></i>
                <div class="label"><?= t('checkout.card') ?></div>
              </label>
              <label class="method-card" data-method="cod">
                <input type="radio" name="method_radio" value="cod">
                <i class="bi bi-truck"></i>
                <div class="label"><?= t('checkout.cod') ?></div>
              </label>
            </div>

            <div id="bkashFields" class="d-none">
              <div class="alert alert-info py-2" style="font-size:0.85rem;"><i class="bi bi-info-circle me-1"></i><?= t('checkout_buyer.charge_on_accept_notice', ['amount' => number_format($total, 2)]) ?></div>
              <div class="mb-3">
                <label class="form-label"><?= t('checkout.bkash_number') ?></label>
                <input type="text" name="bkash_number" class="form-control" placeholder="01XXXXXXXXX">
              </div>
              <div class="mb-3">
                <label class="form-label"><?= t('checkout.bkash_pin') ?></label>
                <input type="password" name="bkash_pin" class="form-control" maxlength="6">
              </div>
            </div>

            <div id="cardFields" class="d-none">
              <div class="alert alert-info py-2" style="font-size:0.85rem;"><i class="bi bi-info-circle me-1"></i><?= t('checkout_buyer.charge_on_accept_notice', ['amount' => number_format($total, 2)]) ?></div>
              <div class="mb-3">
                <label class="form-label"><?= t('checkout.card_number') ?></label>
                <input type="text" name="card_number" class="form-control" placeholder="1234 5678 9012 3456" maxlength="19">
              </div>
              <div class="row">
                <div class="col-6 mb-3">
                  <label class="form-label"><?= t('checkout.expiry') ?></label>
                  <input type="text" name="card_expiry" class="form-control" placeholder="MM/YY" maxlength="5">
                </div>
                <div class="col-6 mb-3">
                  <label class="form-label"><?= t('checkout.cvv') ?></label>
                  <input type="password" name="card_cvv" class="form-control" maxlength="4">
                </div>
              </div>
            </div>

            <div id="codFields" class="d-none">
              <div class="alert alert-info mb-0"><i class="bi bi-truck me-1"></i><?= t('checkout_buyer.cod_notice', ['amount' => number_format($total, 2)]) ?></div>
            </div>

            <button type="submit" class="btn btn-success w-100 py-2 mt-3"><i class="bi bi-lock-fill me-1"></i><?= t('checkout_buyer.place_request') ?> &mdash; ৳<?= number_format($total, 2) ?></button>
          </form>
        </div>
        <p class="text-muted mb-0" style="font-size:0.82rem;"><i class="bi bi-shield-check me-1"></i><?= t('checkout.demo_notice') ?></p>
      </div>

      <div class="col-md-5">
        <div class="card p-4">
          <h5 class="mb-3" style="font-family:'Poppins',sans-serif;"><i class="bi bi-bag-check me-2"></i><?= t('cart.order_summary') ?></h5>
          <?php foreach ($cartItems as $item): ?>
            <div class="d-flex justify-content-between align-items-center mb-2" style="font-size:0.88rem;">
              <div class="d-flex align-items-center gap-2">
                <?php if (!empty($item['image_path'])): ?>
                  <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="" style="width:34px;height:34px;object-fit:cover;border-radius:8px;">
                <?php else: ?>
                  <div class="icon-tile gold" style="width:34px;height:34px;font-size:0.9rem;"><i class="bi bi-flower1"></i></div>
                <?php endif; ?>
                <span><?= htmlspecialchars($item['crop_name']) ?> <span class="text-muted">&times;<?= htmlspecialchars($item['cart_qty']) ?>kg</span></span>
              </div>
              <span class="fw-semibold">৳<?= number_format($item['cart_qty'] * $item['price_per_kg'], 2) ?></span>
            </div>
          <?php endforeach; ?>
          <div class="order-summary mt-2">
            <div class="row-total"><span><?= t('common.total') ?></span><span>৳<?= number_format($total, 2) ?></span></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const methodCards = document.querySelectorAll('.method-card');
const methodValue = document.getElementById('methodValue');
const bkashFields = document.getElementById('bkashFields');
const cardFields = document.getElementById('cardFields');
const codFields = document.getElementById('codFields');

methodCards.forEach(card => {
  card.addEventListener('click', () => {
    methodCards.forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    card.querySelector('input').checked = true;
    const method = card.dataset.method;
    methodValue.value = method;
    bkashFields.classList.toggle('d-none', method !== 'bkash');
    cardFields.classList.toggle('d-none', method !== 'card');
    codFields.classList.toggle('d-none', method !== 'cod');
  });
});
</script>

<?php include 'footer.php'; ?>
