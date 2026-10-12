<?php
require 'auth.php';
requireRole('farmer');
require 'db_connect.php';
require 'notifications.php';

$id = $_GET['id'] ?? null;
if ($id) {
   
    $stmt = $pdo->prepare("SELECT availability FROM equipment_listings WHERE id = ? AND owner_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $current = $stmt->fetchColumn();

    if ($current !== false) {
        $newStatus = $current === 'available' ? 'rented' : 'available';
        $update = $pdo->prepare("UPDATE equipment_listings SET availability = ? WHERE id = ?");
        $update->execute([$newStatus, $id]);
        flash("Equipment marked as " . $newStatus . ".", "success");
    }
}

$referer = $_SERVER['HTTP_REFERER'] ?? '';
$redirectTo = (strpos($referer, 'equipment.php') !== false) ? 'equipment.php' : 'farmer_dashboard.php';
header("Location: " . $redirectTo);
exit;
