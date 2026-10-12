<?php
require 'auth.php';
requireLogin();
require 'db_connect.php';
require_once 'lang.php';

$role = $_SESSION['role'];
$uid = $_SESSION['user_id'];

include 'header.php';
?>

<div class="hero-band furrow">
  <span class="hero-eyebrow"><i class="bi bi-receipt"></i> <?= t('payment_history.eyebrow') ?></span>
  <h1><?= t('payment_history.title') ?></h1>
  <p><?= t('payment_history.subtitle') ?></p>
</div>

<?php if ($role === 'farmer'): ?>
  <?php
    $stmt = $pdo->prepare("SELECT o.*, c.crop_name, u.name AS buyer_name FROM orders o
                            JOIN crop_listings c ON o.listing_id = c.id
                            JOIN users u ON o.buyer_id = u.id
                            WHERE c.farmer_id = ? AND o.payment_status = 'paid'
                            ORDER BY o.paid_at DESC");
    $stmt->execute([$uid]);
    $rows = $stmt->fetchAll();
    $total = array_sum(array_column($rows, 'paid_amount'));
  ?>
  <div class="stat-card mb-4" style="max-width:280px; cursor:default;">
    <div class="num">৳<?= number_format($total, 2) ?></div>
    <small><i class="bi bi-arrow-down-circle me-1"></i><?= t('payment_history.total_received', ['count' => count($rows)]) ?></small>
  </div>

  <?php if (count($rows) === 0): ?>
    <div class="empty-state"><i class="bi bi-receipt"></i><h5><?= t('payment_history.no_received') ?></h5></div>
  <?php else: ?>
  <table class="table table-hover">
    <tr><th><?= t('col.date') ?></th><th><?= t('role.buyer') ?></th><th><?= t('col.crop') ?></th><th><?= t('col.quantity') ?></th><th><?= t('col.amount') ?></th><th><?= t('col.method') ?></th><th><?= t('col.transaction_ref') ?></th></tr>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= htmlspecialchars(date('d M Y, h:i A', strtotime($r['paid_at']))) ?></td>
        <td><?= htmlspecialchars($r['buyer_name']) ?></td>
        <td><?= htmlspecialchars($r['crop_name']) ?></td>
        <td><?= htmlspecialchars($r['quantity_kg']) ?> kg</td>
        <td class="fw-semibold text-success">+৳<?= number_format($r['paid_amount'], 2) ?></td>
        <td><span class="pill pill-blue"><?= strtoupper(htmlspecialchars($r['payment_method'])) ?></span></td>
        <td><small class="text-muted"><?= htmlspecialchars($r['transaction_ref'] ?? '—') ?></small></td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>

<?php elseif ($role === 'buyer'): ?>
  <?php
    $sentStmt = $pdo->prepare("SELECT o.*, c.crop_name, u.name AS farmer_name FROM orders o
                                JOIN crop_listings c ON o.listing_id = c.id
                                JOIN users u ON c.farmer_id = u.id
                                WHERE o.buyer_id = ? AND o.payment_status = 'paid'
                                ORDER BY o.paid_at DESC");
    $sentStmt->execute([$uid]);
    $sent = $sentStmt->fetchAll();
    $totalSent = array_sum(array_column($sent, 'paid_amount'));

    $recvStmt = $pdo->prepare("SELECT co.*, s.product_name, u.name AS consumer_name FROM consumer_orders co
                                JOIN storefront_listings s ON co.storefront_id = s.id
                                JOIN users u ON co.consumer_id = u.id
                                WHERE s.buyer_id = ? AND co.payment_status IN ('paid','cod')
                                ORDER BY co.created_at DESC");
    $recvStmt->execute([$uid]);
    $received = $recvStmt->fetchAll();
    $totalReceived = array_sum(array_column($received, 'paid_amount'));
  ?>
  <div class="row g-3 mb-4">
    <div class="col-md-4"><div class="stat-card stat-clay" style="cursor:default;"><div class="num">৳<?= number_format($totalSent, 2) ?></div><small><i class="bi bi-arrow-up-circle me-1"></i><?= t('payment_history.sent_to_farmers', ['count' => count($sent)]) ?></small></div></div>
    <div class="col-md-4"><div class="stat-card" style="cursor:default;"><div class="num">৳<?= number_format($totalReceived, 2) ?></div><small><i class="bi bi-arrow-down-circle me-1"></i><?= t('payment_history.received_from_consumers', ['count' => count($received)]) ?></small></div></div>
  </div>

  <h5 class="page-title" style="font-size:1.1rem;"><?= t('payment_history.payments_sent') ?></h5>
  <?php if (count($sent) === 0): ?>
    <div class="empty-state py-4"><i class="bi bi-receipt"></i><p class="mb-0"><?= t('payment_history.no_sent') ?></p></div>
  <?php else: ?>
  <table class="table table-hover mb-5">
    <tr><th><?= t('col.date') ?></th><th><?= t('role.farmer') ?></th><th><?= t('col.crop') ?></th><th><?= t('col.quantity') ?></th><th><?= t('col.amount') ?></th><th><?= t('col.method') ?></th></tr>
    <?php foreach ($sent as $r): ?>
      <tr>
        <td><?= htmlspecialchars(date('d M Y, h:i A', strtotime($r['paid_at']))) ?></td>
        <td><?= htmlspecialchars($r['farmer_name']) ?></td>
        <td><?= htmlspecialchars($r['crop_name']) ?></td>
        <td><?= htmlspecialchars($r['quantity_kg']) ?> kg</td>
        <td class="fw-semibold" style="color:var(--clay);">−৳<?= number_format($r['paid_amount'], 2) ?></td>
        <td><span class="pill pill-blue"><?= strtoupper(htmlspecialchars($r['payment_method'])) ?></span></td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>

  <h5 class="page-title" style="font-size:1.1rem;"><?= t('payment_history.payments_received') ?></h5>
  <?php if (count($received) === 0): ?>
    <div class="empty-state py-4"><i class="bi bi-receipt"></i><p class="mb-0"><?= t('payment_history.no_received_p') ?></p></div>
  <?php else: ?>
  <table class="table table-hover">
    <tr><th><?= t('col.date') ?></th><th><?= t('role.consumer') ?></th><th><?= t('col.product') ?></th><th><?= t('col.quantity') ?></th><th><?= t('col.amount') ?></th><th><?= t('col.method') ?></th></tr>
    <?php foreach ($received as $r): ?>
      <tr>
        <td><?= htmlspecialchars(date('d M Y, h:i A', strtotime($r['created_at']))) ?></td>
        <td><?= htmlspecialchars($r['consumer_name']) ?></td>
        <td><?= htmlspecialchars($r['product_name']) ?></td>
        <td><?= htmlspecialchars($r['quantity']) ?></td>
        <td class="fw-semibold text-success">+৳<?= number_format($r['paid_amount'], 2) ?></td>
        <td><span class="pill <?= $r['payment_method'] === 'cod' ? 'pill-clay' : 'pill-blue' ?>"><?= $r['payment_method'] === 'cod' ? t('payment_history.cod_short') : strtoupper(htmlspecialchars($r['payment_method'])) ?></span></td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>

<?php elseif ($role === 'consumer'): ?>
  <?php
    $stmt = $pdo->prepare("SELECT co.*, s.product_name, u.name AS seller_name, u.business_name FROM consumer_orders co
                            JOIN storefront_listings s ON co.storefront_id = s.id
                            JOIN users u ON s.buyer_id = u.id
                            WHERE co.consumer_id = ? AND co.payment_status IN ('paid','cod')
                            ORDER BY co.created_at DESC");
    $stmt->execute([$uid]);
    $rows = $stmt->fetchAll();
    $total = array_sum(array_column($rows, 'paid_amount'));
  ?>
  <div class="stat-card stat-clay mb-4" style="max-width:280px; cursor:default;">
    <div class="num">৳<?= number_format($total, 2) ?></div>
    <small><i class="bi bi-arrow-up-circle me-1"></i><?= t('payment_history.total_spent', ['count' => count($rows)]) ?></small>
  </div>

  <?php if (count($rows) === 0): ?>
    <div class="empty-state"><i class="bi bi-receipt"></i><h5><?= t('payment_history.no_made') ?></h5></div>
  <?php else: ?>
  <table class="table table-hover">
    <tr><th><?= t('col.date') ?></th><th><?= t('col.seller') ?></th><th><?= t('col.product') ?></th><th><?= t('col.quantity') ?></th><th><?= t('col.amount') ?></th><th><?= t('col.method') ?></th></tr>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= htmlspecialchars(date('d M Y, h:i A', strtotime($r['created_at']))) ?></td>
        <td><?= htmlspecialchars($r['business_name'] ?: $r['seller_name']) ?></td>
        <td><?= htmlspecialchars($r['product_name']) ?></td>
        <td><?= htmlspecialchars($r['quantity']) ?></td>
        <td class="fw-semibold" style="color:var(--clay);">−৳<?= number_format($r['paid_amount'], 2) ?></td>
        <td><span class="pill <?= $r['payment_method'] === 'cod' ? 'pill-clay' : 'pill-blue' ?>"><?= $r['payment_method'] === 'cod' ? t('payment_history.cod_short') : strtoupper(htmlspecialchars($r['payment_method'])) ?></span></td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>

<?php else: ?>
  <div class="empty-state"><i class="bi bi-shield-lock"></i><h5><?= t('payment_history.not_for_admin') ?></h5></div>
<?php endif; ?>

<?php include 'footer.php'; ?>
