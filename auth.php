<?php
define('SESSION_TIMEOUT', 7200);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_TIMEOUT,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        header("Location: login.php?expired=1");
        exit;
    }

    $_SESSION['last_activity'] = time();
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        header("Location: login.php");
        exit;
    }
}

function touchActivity() {
    if (isset($_SESSION['user_id'])) {
        $_SESSION['last_activity'] = time();
    }
}
?>
