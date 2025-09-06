<?php
/**
 * Database Setup Script
 * 
 * This script creates the database and tables for the Pizza Delivery Website.
 * Run this script once to set up the database structure.
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Change to your MySQL username
define('DB_PASS', '');           // Change to your MySQL password

try {
    // Connect to MySQL server without selecting a database
    $pdo = new PDO(
        "mysql:host=" . DB_HOST,
        DB_USER,
        DB_PASS,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
        )
    );
    
    echo "<h2>Pizza Delivery Website - Database Setup</h2>";
    echo "<p>Connected to MySQL server successfully.</p>";
    
    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/database.sql');
    
    // Execute multi-query SQL script
    $pdo->exec($sql);
    
    echo "<p style='color: green;'>Database and tables created successfully!</p>";
    echo "<p>You can now proceed with using the website.</p>";
    echo "<p><a href='/pizza_delivery/public/index.php'>Go to Homepage</a></p>";
    
} catch (PDOException $e) {
    // Connection or query failed
    die("<p style='color: red;'>Database Setup Error: " . $e->getMessage() . "</p>");
}