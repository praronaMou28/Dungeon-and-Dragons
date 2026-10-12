<?php
require 'auth.php';
requireLogin();
require 'db_connect.php';
require 'notifications.php';

header('Content-Type: application/json');
markAllNotificationsRead($pdo, $_SESSION['user_id']);
echo json_encode(['ok' => true]);
