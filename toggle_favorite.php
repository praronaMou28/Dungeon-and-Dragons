<?php
require 'auth.php';
requireRole('consumer');
require 'db_connect.php';

header('Content-Type: application/json');

$storefront_id = (int) ($_POST['storefront_id'] ?? 0);
if ($storefront_id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid item']);
    exit;
}

$check = $pdo->prepare("SELECT id FROM consumer_favorites WHERE consumer_id = ? AND storefront_id = ?");
$check->execute([$_SESSION['user_id'], $storefront_id]);
$existing = $check->fetchColumn();

if ($existing) {
    $del = $pdo->prepare("DELETE FROM consumer_favorites WHERE id = ?");
    $del->execute([$existing]);
    echo json_encode(['ok' => true, 'favorited' => false]);
} else {
    $ins = $pdo->prepare("INSERT INTO consumer_favorites (consumer_id, storefront_id) VALUES (?, ?)");
    $ins->execute([$_SESSION['user_id'], $storefront_id]);
    echo json_encode(['ok' => true, 'favorited' => true]);
}
