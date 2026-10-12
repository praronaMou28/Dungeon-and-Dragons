<?php
require 'auth.php';
requireRole('consumer');
require 'db_connect.php';

header('Content-Type: application/json');

$cart_id = (int) ($_POST['cart_id'] ?? 0);
$action = $_POST['action'] ?? 'update';

$stmt = $pdo->prepare("SELECT c.*, s.stock_qty, s.price_per_unit FROM cart_items c
                        JOIN storefront_listings s ON c.storefront_id = s.id
                        WHERE c.id = ? AND c.consumer_id = ?");
$stmt->execute([$cart_id, $_SESSION['user_id']]);
$item = $stmt->fetch();

if (!$item) {
    echo json_encode(['ok' => false, 'error' => 'Item not found']);
    exit;
}

if ($action === 'remove') {
    $del = $pdo->prepare("DELETE FROM cart_items WHERE id = ?");
    $del->execute([$cart_id]);
} else {
    $quantity = (float) ($_POST['quantity'] ?? 1);
    if ($quantity <= 0) {
        $pdo->prepare("DELETE FROM cart_items WHERE id = ?")->execute([$cart_id]);
    } else {
        if ($quantity > $item['stock_qty']) {
            $quantity = $item['stock_qty'];
        }
        $upd = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $upd->execute([$quantity, $cart_id]);
    }
}

$totalsStmt = $pdo->prepare("SELECT c.quantity, s.price_per_unit FROM cart_items c
                              JOIN storefront_listings s ON c.storefront_id = s.id
                              WHERE c.consumer_id = ?");
$totalsStmt->execute([$_SESSION['user_id']]);
$rows = $totalsStmt->fetchAll();

$cartCount = count($rows);
$cartTotal = array_sum(array_map(fn($r) => $r['quantity'] * $r['price_per_unit'], $rows));

echo json_encode(['ok' => true, 'cartCount' => $cartCount, 'cartTotal' => round($cartTotal, 2)]);
