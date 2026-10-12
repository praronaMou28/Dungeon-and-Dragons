<?php
require 'auth.php';
requireRole('farmer');
require 'db_connect.php';
require 'notifications.php';

$order_id = $_GET['id'];
$action = $_GET['action'];
$newStatus = ($action === 'accept') ? 'accepted' : 'rejected';

$stmt = $pdo->prepare("SELECT o.id, o.buyer_id, o.quantity_kg AS ordered_qty,
                               o.payment_method, o.payment_status, o.paid_amount,
                               c.id AS listing_id, c.crop_name, c.quantity_kg AS listing_qty
                        FROM orders o
                        JOIN crop_listings c ON o.listing_id = c.id
                        WHERE o.id = ? AND c.farmer_id = ? AND o.status = 'requested'");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$row = $stmt->fetch();

if ($row) {
    $update = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $update->execute([$newStatus, $order_id]);

    if ($newStatus === 'accepted') {
        $remaining = $row['listing_qty'] - $row['ordered_qty'];
        if ($remaining < 0) $remaining = 0;
        $newListingStatus = ($remaining <= 0) ? 'sold' : 'available';

        $mark = $pdo->prepare("UPDATE crop_listings SET quantity_kg = ?, status = ? WHERE id = ?");
        $mark->execute([$remaining, $newListingStatus, $row['listing_id']]);

        if ($row['payment_status'] === 'unpaid' && in_array($row['payment_method'], ['bkash', 'card'], true)) {
            $charge = $pdo->prepare("UPDATE orders SET payment_status = 'paid', transaction_ref = ?, paid_at = NOW() WHERE id = ?");
            $charge->execute([generateTransactionRef($row['payment_method']), $order_id]);
        }
    }


    $verb = $newStatus === 'accepted' ? 'accepted' : 'rejected';
    $message = "Your request for " . $row['ordered_qty'] . " kg of " . $row['crop_name'] . " was " . $verb . " by " . $_SESSION['name'] . ".";
    if ($newStatus === 'accepted' && $row['payment_status'] === 'unpaid' && in_array($row['payment_method'], ['bkash', 'card'], true)) {
        $message .= " You've been charged ৳" . number_format($row['paid_amount'], 2) . " via " . strtoupper($row['payment_method']) . ".";
    } elseif ($newStatus === 'rejected') {
        $message .= $row['payment_status'] === 'unpaid' ? " You were not charged." : "";
    }
    notify($pdo, (int)$row['buyer_id'], $message, "buyer_dashboard.php");

    flash("Order request " . $verb . ".", $newStatus === 'accepted' ? 'success' : 'warning');
}

header("Location: farmer_dashboard.php");
exit;
?>
