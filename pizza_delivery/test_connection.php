<?php
/**
 * Test Database Connection
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pizza_delivery');

echo "<h1>Database Connection Test</h1>";

try {
    // Connect to MySQL server
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
        )
    );
    
    echo "<p style='color: green;'>Database connection successful!</p>";
    
    // Check if tables exist
    $tables = ['users', 'food_items', 'orders', 'order_details', 'cart'];
    $missing_tables = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->rowCount() === 0) {
            $missing_tables[] = $table;
        }
    }
    
    if (empty($missing_tables)) {
        echo "<p style='color: green;'>All required tables exist.</p>";
        
        // Check if admin user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'admin'");
        $stmt->execute();
        $admin_users = $stmt->fetchAll();
        
        if (count($admin_users) > 0) {
            echo "<p style='color: green;'>Admin user exists.</p>";
            echo "<p>Admin email: " . $admin_users[0]['email'] . "</p>";
        } else {
            echo "<p style='color: red;'>No admin user found. You may need to run the setup script.</p>";
        }
        
        // Check user table structure
        $stmt = $pdo->prepare("DESCRIBE users");
        $stmt->execute();
        $user_columns = $stmt->fetchAll();
        
        echo "<h3>Users Table Structure:</h3>";
        echo "<ul>";
        foreach ($user_columns as $column) {
            echo "<li>" . $column['Field'] . " - " . $column['Type'] . "</li>";
        }
        echo "</ul>";
        
    } else {
        echo "<p style='color: red;'>Missing tables: " . implode(", ", $missing_tables) . "</p>";
        echo "<p>Please run the setup script to create the required tables.</p>";
        echo "<p><a href='setup_database.php'>Run Setup Script</a></p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database connection failed: " . $e->getMessage() . "</p>";
    
    // Check if database exists
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST,
            DB_USER,
            DB_PASS,
            array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        );
        
        $stmt = $pdo->prepare("SHOW DATABASES LIKE ?");
        $stmt->execute([DB_NAME]);
        
        if ($stmt->rowCount() === 0) {
            echo "<p style='color: red;'>Database '" . DB_NAME . "' does not exist.</p>";
            echo "<p>Please run the setup script to create the database.</p>";
            echo "<p><a href='setup_database.php'>Run Setup Script</a></p>";
        }
    } catch (PDOException $e2) {
        echo "<p style='color: red;'>MySQL server connection failed: " . $e2->getMessage() . "</p>";
        echo "<p>Please make sure MySQL server is running and credentials are correct.</p>";
    }
}
?>