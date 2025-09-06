<?php
/**
 * Database Connection
 * 
 * This file establishes a connection to the MySQL database for the Pizza Delivery Website.
 * It uses PDO for secure database interactions with prepared statements.
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Change to your MySQL username
define('DB_PASS', '');           // Change to your MySQL password
define('DB_NAME', 'pizza_delivery');

// Establish database connection
try {
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
    
    // Connection successful
    // echo "Database connection established successfully";
} catch (PDOException $e) {
    // Connection failed
    die("Database Connection Error: " . $e->getMessage());
}