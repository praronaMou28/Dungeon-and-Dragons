<?php

require 'auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false]);
    exit;
}

$_SESSION['last_activity'] = time();
echo json_encode(['ok' => true, 'timeout' => SESSION_TIMEOUT]);
