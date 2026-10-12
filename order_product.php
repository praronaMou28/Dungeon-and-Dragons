<?php
require 'auth.php';
requireRole('consumer');
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $storefront_id = (int) $_POST['storefront_id'];
    $quantity = (float) $_POST['quantity'];

    $stmt = $pdo->prepare("SELECT stock_qty FROM storefront_listings WHERE id = ?");
    $stmt->execute([$storefront_id]);
    $available = $stmt->fetchColumn();

    if ($available === false || $quantity <= 0) {
        header("Location: market.php?error=stock");
        exit;
    }
    if ($quantity > $available) {
        $quantity = $available;
    }

    $ins = $pdo->prepare("INSERT INTO cart_items (consumer_id, storefront_id, quantity) VALUES (?, ?, ?)
                           ON DUPLICATE KEY UPDATE quantity = LEAST(VALUES(quantity) + quantity, ?)");
    $ins->execute([$_SESSION['user_id'], $storefront_id, $quantity, $available]);

    header("Location: cart.php");
    exit;
}

header("Location: market.php");
exit;
