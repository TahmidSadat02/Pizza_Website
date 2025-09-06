<?php
// Include database connection
require_once 'config/db.php';

// Password for both users (123456 for admin)
$password = '123456';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

echo "Hashed password: " . $hashed_password . "\n";

try {
    // Update test user password
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->execute([$hashed_password, 'test@example.com']);
    echo "Updated test user password\n";
    
    // Update admin user password
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->execute([$hashed_password, 'admin@example.com']);
    echo "Updated admin user password\n";
    
    echo "Password update complete!\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}