<?php
require 'db_connect.php';

$check = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
$check->execute();

if ($check->fetch()) {
    echo "An admin account already exists. Delete this file for safety.";
} else {
    $name = "Admin";
    $phone = "01700000000";
    $password = password_hash("admin123", PASSWORD_DEFAULT);
    $role = "admin";

    $stmt = $pdo->prepare("INSERT INTO users (name, phone, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $phone, $password, $role]);

    echo "Admin account created successfully.<br>";
    echo "Phone: $phone<br>Password: admin123<br>";
    echo "<strong>Please delete create_admin.php now.</strong>";
}
?>
