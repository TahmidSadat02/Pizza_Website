<?php
/**
 * Database Check Script
 */

// Include database connection
require_once 'config/db.php';

try {
    // Check if tables exist
    $tables = ['users', 'food_items', 'orders', 'order_details', 'cart'];
    $existing_tables = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->rowCount() > 0) {
            $existing_tables[] = $table;
        }
    }
    
    echo "<h2>Database Check Results</h2>";
    echo "<p>Database name: " . DB_NAME . "</p>";
    
    if (count($existing_tables) === count($tables)) {
        echo "<p style='color: green;'>All required tables exist:</p>";
    } else {
        echo "<p style='color: red;'>Missing tables:</p>";
        echo "<ul>";
        foreach ($tables as $table) {
            if (!in_array($table, $existing_tables)) {
                echo "<li>" . $table . "</li>";
            }
        }
        echo "</ul>";
    }
    
    echo "<p>Existing tables:</p>";
    echo "<ul>";
    foreach ($existing_tables as $table) {
        echo "<li>" . $table . "</li>";
    }
    echo "</ul>";
    
    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'admin'");
    $stmt->execute();
    $admin_count = $stmt->rowCount();
    
    echo "<p>Admin users found: " . $admin_count . "</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . $e->getMessage() . "</p>";
}