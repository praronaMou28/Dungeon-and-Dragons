<?php
require 'auth.php';
requireRole('consumer');
require 'db_connect.php';

header('Content-Type: application/json');

$storefront_id = (int) ($_POST['storefront_id'] ?? 0);
$quantity = (float) ($_POST['quantity'] ?? 1);

if ($storefront_id <= 0 || $quantity <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid item or quantity']);
    exit;
}

$stmt = $pdo->prepare("SELECT stock_qty FROM storefront_listings WHERE id = ?");
$stmt->execute([$storefront_id]);
$stock = $stmt->fetchColumn();

if ($stock === false) {
    echo json_encode(['ok' => false, 'error' => 'This product no longer exists']);
    exit;
}

if ($quantity > $stock) {
    $quantity = $stock;
}

$stmt = $pdo->prepare("INSERT INTO cart_items (consumer_id, storefront_id, quantity) VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE quantity = LEAST(VALUES(quantity) + quantity, ?)");
$stmt->execute([$_SESSION['user_id'], $storefront_id, $quantity, $stock]);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM cart_items WHERE consumer_id = ?");
$countStmt->execute([$_SESSION['user_id']]);
$cartCount = (int) $countStmt->fetchColumn();

echo json_encode(['ok' => true, 'cartCount' => $cartCount]);
