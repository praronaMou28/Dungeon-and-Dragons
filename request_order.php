<?php
require 'auth.php';
requireRole('buyer');
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $listing_id = (int) $_POST['listing_id'];
    $quantity = (float) $_POST['quantity_kg'];

    $stmt = $pdo->prepare("SELECT quantity_kg, status FROM crop_listings WHERE id = ?");
    $stmt->execute([$listing_id]);
    $listing = $stmt->fetch();

    if (!$listing || $listing['status'] !== 'available' || $quantity <= 0) {
        header("Location: browse_crops.php?error=stock");
        exit;
    }
    if ($quantity > $listing['quantity_kg']) {
        $quantity = $listing['quantity_kg'];
    }

    $ins = $pdo->prepare("INSERT INTO crop_cart_items (buyer_id, listing_id, quantity_kg) VALUES (?, ?, ?)
                           ON DUPLICATE KEY UPDATE quantity_kg = LEAST(VALUES(quantity_kg) + quantity_kg, ?)");
    $ins->execute([$_SESSION['user_id'], $listing_id, $quantity, $listing['quantity_kg']]);

    header("Location: crop_cart.php");
    exit;
}

header("Location: browse_crops.php");
exit;
