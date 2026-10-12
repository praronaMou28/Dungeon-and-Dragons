<?php
require 'auth.php';
requireRole('buyer');
require 'db_connect.php';

header('Content-Type: application/json');

$cart_id = (int) ($_POST['cart_id'] ?? 0);
$action = $_POST['action'] ?? 'update';

$stmt = $pdo->prepare("SELECT c.*, l.quantity_kg AS listing_qty, l.price_per_kg FROM crop_cart_items c
                        JOIN crop_listings l ON c.listing_id = l.id
                        WHERE c.id = ? AND c.buyer_id = ?");
$stmt->execute([$cart_id, $_SESSION['user_id']]);
$item = $stmt->fetch();

if (!$item) {
    echo json_encode(['ok' => false, 'error' => 'Item not found']);
    exit;
}

if ($action === 'remove') {
    $del = $pdo->prepare("DELETE FROM crop_cart_items WHERE id = ?");
    $del->execute([$cart_id]);
} else {
    $quantity = (float) ($_POST['quantity'] ?? 1);
    if ($quantity <= 0) {
        $pdo->prepare("DELETE FROM crop_cart_items WHERE id = ?")->execute([$cart_id]);
    } else {
        if ($quantity > $item['listing_qty']) {
            $quantity = $item['listing_qty'];
        }
        $upd = $pdo->prepare("UPDATE crop_cart_items SET quantity_kg = ? WHERE id = ?");
        $upd->execute([$quantity, $cart_id]);
    }
}

$totalsStmt = $pdo->prepare("SELECT c.quantity_kg, l.price_per_kg FROM crop_cart_items c
                              JOIN crop_listings l ON c.listing_id = l.id
                              WHERE c.buyer_id = ?");
$totalsStmt->execute([$_SESSION['user_id']]);
$rows = $totalsStmt->fetchAll();

$cartCount = count($rows);
$cartTotal = array_sum(array_map(fn($r) => $r['quantity_kg'] * $r['price_per_kg'], $rows));

echo json_encode(['ok' => true, 'cartCount' => $cartCount, 'cartTotal' => round($cartTotal, 2)]);
