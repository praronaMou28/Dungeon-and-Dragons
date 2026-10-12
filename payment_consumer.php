<?php
require 'auth.php';
requireRole('consumer');
require 'db_connect.php';
require 'notifications.php';
require_once 'lang.php';


$order_id = $_GET['order_id'] ?? $_POST['order_id'] ?? null;
if (!$order_id) {
    header("Location: consumer_dashboard.php");
    exit;
}

$stmt = $pdo->prepare("SELECT co.*, s.product_name, s.price_per_unit FROM consumer_orders co
                        JOIN storefront_listings s ON co.storefront_id = s.id
                        WHERE co.id = ? AND co.consumer_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: consumer_dashboard.php");
    exit;
}

if ($order['payment_status'] !== 'unpaid') {
    header("Location: receipt_consumer.php?order_id=" . $order_id);
    exit;
}

$total = $order['quantity'] * $order['price_per_unit'];
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = $_POST['payment_method'] ?? '';

    if ($method === 'cod') {
        // No card/bKash details needed for cash on delivery.
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
    } else {
        $error = t('checkout.err_method');
    }

    if (!$error) {
    
        $pdo->beginTransaction();
        $lock = $pdo->prepare("SELECT stock_qty FROM storefront_listings WHERE id = ? FOR UPDATE");
        $lock->execute([$order['storefront_id']]);
        $currentStock = $lock->fetchColumn();

        if ($currentStock === false || $currentStock < $order['quantity']) {
            $pdo->rollBack();
            $error = t('payment_consumer.err_stock');
        } else {
       
            $status = ($method === 'cod') ? 'cod' : 'paid';
            $txnRef = $method === 'cod' ? null : generateTransactionRef($method);
            $update = $pdo->prepare("UPDATE consumer_orders
                                      SET payment_method = ?, payment_status = ?,
                                          paid_amount = ?, transaction_ref = ?, paid_at = NOW()
                                      WHERE id = ?");
            $update->execute([$method, $status, $total, $txnRef, $order_id]);

            $reduce = $pdo->prepare("UPDATE storefront_listings SET stock_qty = stock_qty - ? WHERE id = ?");
            $reduce->execute([$order['quantity'], $order['storefront_id']]);
        
            $pdo->commit();

            $methodLabel = $method === 'cod' ? t('checkout.cod') : strtoupper($method);
            notifyStorefrontOwner(
                $pdo,
                $order['storefront_id'],
                $_SESSION['name'] . " ordered " . $order['quantity'] . " x " . $order['product_name'] . " (৳" . number_format($total, 2) . ", " . $methodLabel . ").",
                "buyer_dashboard.php"
            );

            if ($method === 'cod') {
                flash(t('payment_consumer.flash_cod', ['amount' => number_format($total, 2)]), "success");
            } else {
                flash(t('checkout.flash_paid', ['amount' => number_format($total, 2), 'method' => strtoupper($method)]), "success");
            }

            header("Location: receipt_consumer.php?order_id=" . $order_id);
            exit;
        }
    }
}

include 'header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-7">

    <div class="checkout-steps">
      <div class="checkout-step done"><span class="dot"><i class="bi bi-check"></i></span> <?= t('checkout.step_cart') ?></div>
      <div class="checkout-connector"></div>
      <div class="checkout-step active"><span class="dot">2</span> <?= t('payment_buyer.step_payment') ?></div>
      <div class="checkout-connector"></div>
      <div class="checkout-step"><span class="dot">3</span> <?= t('checkout.step_done') ?></div>
    </div>

    <div class="card p-4">
      <h3 class="page-title mb-1"><i class="bi bi-credit-card-2-front me-2"></i><?= t('payment_buyer.title') ?></h3>
      <p class="text-muted mb-3"><?= htmlspecialchars($order['product_name']) ?> &mdash; <?= t('payment_consumer.units', ['qty' => htmlspecialchars($order['quantity'])]) ?></p>

      <div class="order-summary mb-4">
        <div class="row-line"><span><?= t('col.product') ?></span><span class="fw-semibold"><?= htmlspecialchars($order['product_name']) ?></span></div>
        <div class="row-line"><span><?= t('receipt.price_per_unit') ?></span><span>৳<?= number_format($order['price_per_unit'], 2) ?></span></div>
        <div class="row-line"><span><?= t('col.quantity') ?></span><span><?= htmlspecialchars($order['quantity']) ?></span></div>
        <div class="row-total"><span><?= t('common.total') ?></span><span>৳<?= number_format($total, 2) ?></span></div>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" id="payForm">
        <input type="hidden" name="order_id" value="<?= (int)$order_id ?>">
        <input type="hidden" name="payment_method" id="methodValue" value="">

        <label class="form-label fw-semibold"><?= t('checkout.choose_payment') ?></label>
        <div class="method-options mb-4">
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
          <div class="flip-card-widget">
            <div class="card-scene">
              <div class="card-inner" id="cardInner">
                <div class="card-front">
                  <div class="card-top">
                    <span class="card-chip-label">Krishi Sohayota</span>
                    <div class="mastercard-logo"><span class="mc-circle mc-left"></span><span class="mc-circle mc-right"></span></div>
                  </div>
                  <div class="card-number-display" id="cardNumDisplay">
                    <span id="g1">####</span><span id="g2">####</span><span id="g3">####</span><span id="g4">####</span>
                  </div>
                  <div class="card-bottom">
                    <div>
                      <div class="card-info-label">Card Holder</div>
                      <div class="card-info-value" id="holderDisplay">FULL NAME</div>
                    </div>
                    <div>
                      <div class="card-info-label">Expires</div>
                      <div class="card-info-value" id="expiryDisplay">MM/YY</div>
                    </div>
                  </div>
                </div>
                <div class="card-back">
                  <div class="magnetic-stripe"></div>
                  <div class="cvv-section">
                    <div class="cvv-band"><span class="cvv-dots" id="cvvDisplay">***</span></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="form-card">
              <div class="mb-3">
                <label class="form-label"><?= t('checkout.card_number') ?></label>
                <input type="text" id="cardNumber" name="card_number" autocomplete="off" placeholder="1234 5678 9012 3456">
              </div>
              <div class="mb-3">
                <label class="form-label"><?= t('checkout.card_holder') ?></label>
                <input type="text" id="cardHolder" name="card_holder" autocomplete="off" placeholder="<?= t('checkout.card_holder_ph') ?>">
              </div>
              <div class="row g-2">
                <div class="col-7">
                  <label class="form-label"><?= t('checkout.expiry') ?></label>
                  <div class="d-flex gap-2">
                    <select id="expMonth" class="form-select">
                      <option value="">MM</option>
                      <?php for ($m = 1; $m <= 12; $m++): $mm = str_pad((string)$m, 2, '0', STR_PAD_LEFT); ?>
                        <option value="<?= $mm ?>"><?= $mm ?></option>
                      <?php endfor; ?>
                    </select>
                    <select id="expYear" class="form-select">
                      <option value="">YY</option>
                      <?php $curYear = (int)date('y'); for ($y = $curYear; $y <= $curYear + 12; $y++): $yy = str_pad((string)$y, 2, '0', STR_PAD_LEFT); ?>
                        <option value="<?= $yy ?>"><?= $yy ?></option>
                      <?php endfor; ?>
                    </select>
                  </div>
                  <input type="hidden" name="card_expiry" id="cardExpiryHidden">
                </div>
                <div class="col-5 cvv-field">
                  <label class="form-label"><?= t('checkout.cvv') ?></label>
                  <input type="password" id="cvv" name="card_cvv" autocomplete="off" maxlength="4">
                </div>
              </div>
            </div>
          </div>
        </div>

        <div id="codFields" class="d-none">
          <div class="alert alert-info mb-0"><i class="bi bi-truck me-1"></i><?= t('checkout.cod_notice', ['amount' => number_format($total, 2)]) ?></div>
        </div>

        <button type="submit" class="btn btn-success w-100 py-2 mt-1"><i class="bi bi-lock-fill me-1"></i><?= t('payment_consumer.confirm_order') ?></button>
      </form>
      <p class="text-muted mt-3 mb-0" style="font-size:0.82rem;"><i class="bi bi-shield-check me-1"></i><?= t('checkout.demo_notice') ?></p>
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

const cardNumberInput = document.getElementById('cardNumber');
if (cardNumberInput) {
  cardNumberInput.addEventListener('input', function () {
    const val = this.value.replace(/\D/g, '').slice(0, 16);
    this.value = val.replace(/(.{4})/g, '$1 ').trim();
    const raw = [val.slice(0, 4), val.slice(4, 8), val.slice(8, 12), val.slice(12, 16)];
    ['g1', 'g2', 'g3', 'g4'].forEach((id, i) => {
      document.getElementById(id).textContent = raw[i].padEnd(4, '#');
    });
  });

  document.getElementById('cardHolder').addEventListener('input', function () {
    document.getElementById('holderDisplay').textContent = this.value.toUpperCase() || 'FULL NAME';
  });

  const expiryHidden = document.getElementById('cardExpiryHidden');
  function updateExpiry() {
    const m = document.getElementById('expMonth').value;
    const y = document.getElementById('expYear').value;
    document.getElementById('expiryDisplay').textContent = (m || 'MM') + '/' + (y || 'YY');
    expiryHidden.value = (m && y) ? (m + '/' + y) : '';
  }
  document.getElementById('expMonth').addEventListener('change', updateExpiry);
  document.getElementById('expYear').addEventListener('change', updateExpiry);

  const cvvInput = document.getElementById('cvv');
  const cardInner = document.getElementById('cardInner');
  cvvInput.addEventListener('focus', () => cardInner.classList.add('flipped'));
  cvvInput.addEventListener('blur', () => cardInner.classList.remove('flipped'));
  cvvInput.addEventListener('input', function () {
    const val = this.value.replace(/\D/g, '').slice(0, 4);
    this.value = val;
    document.getElementById('cvvDisplay').textContent = '*'.repeat(val.length) || '***';
  });
}
</script>

<?php include 'footer.php'; ?>

