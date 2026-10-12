<?php

function notify(PDO $pdo, int $userId, string $message, ?string $link = null): void {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $message, $link]);
}


function notifyListingOwners(PDO $pdo, int $listingId, string $message, ?string $link = null): void {
    $stmt = $pdo->prepare("SELECT farmer_id FROM crop_listings WHERE id = ?");
    $stmt->execute([$listingId]);
    if ($ownerId = $stmt->fetchColumn()) {
        notify($pdo, (int)$ownerId, $message, $link);
    }
}


function notifyStorefrontOwner(PDO $pdo, int $storefrontId, string $message, ?string $link = null): void {
    $stmt = $pdo->prepare("SELECT buyer_id FROM storefront_listings WHERE id = ?");
    $stmt->execute([$storefrontId]);
    if ($ownerId = $stmt->fetchColumn()) {
        notify($pdo, (int)$ownerId, $message, $link);
    }
}

function getUnreadCount(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function getRecentNotifications(PDO $pdo, int $userId, int $limit = 8): array {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT " . (int)$limit);
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function markAllNotificationsRead(PDO $pdo, int $userId): void {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$userId]);
}


function flash(string $message, string $type = 'success'): void {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = ['message' => $message, 'type' => $type];
}


function consumeFlashMessages(): array {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}


function generateTransactionRef(string $method): string {
    $prefix = strtoupper($method) === 'BKASH' ? 'BKS' : (strtoupper($method) === 'CARD' ? 'CRD' : 'TXN');
    return $prefix . strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
}
?>
