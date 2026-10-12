<?php
require 'auth.php';
requireRole('buyer');
require 'db_connect.php';

header('Content-Type: application/json');

$listing_id = (int) ($_POST['listing_id'] ?? 0);
$quantity = (float) ($_POST['quantity'] ?? 1);

if ($listing_id <= 0 || $quantity <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid item or quantity']);
    exit;
}

$stmt = $pdo->prepare("SELECT quantity_kg, status FROM crop_listings WHERE id = ?");
$stmt->execute([$listing_id]);
$listing = $stmt->fetch();

if (!$listing || $listing['status'] !== 'available') {
    echo json_encode(['ok' => false, 'error' => 'This listing is no longer available']);
    exit;
}

if ($quantity > $listing['quantity_kg']) {
    $quantity = $listing['quantity_kg'];
}

$stmt = $pdo->prepare("INSERT INTO crop_cart_items (buyer_id, listing_id, quantity_kg) VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE quantity_kg = LEAST(VALUES(quantity_kg) + quantity_kg, ?)");
$stmt->execute([$_SESSION['user_id'], $listing_id, $quantity, $listing['quantity_kg']]);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM crop_cart_items WHERE buyer_id = ?");
$countStmt->execute([$_SESSION['user_id']]);
$cartCount = (int) $countStmt->fetchColumn();

echo json_encode(['ok' => true, 'cartCount' => $cartCount]);
