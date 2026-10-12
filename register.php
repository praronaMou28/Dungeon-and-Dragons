<?php
$role = $_GET['role'] ?? '';
$target = 'login.php?mode=register';
if (in_array($role, ['farmer', 'buyer', 'consumer'], true)) {
    $target .= '&role=' . urlencode($role);
}
header("Location: " . $target);
exit;
