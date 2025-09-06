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
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS pizza_delivery");
    $pdo->exec("USE pizza_delivery");
    
    echo "<p>Database 'pizza_delivery' created or selected successfully.</p>";
    
    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY, 
        name VARCHAR(100) NOT NULL, 
        email VARCHAR(150) UNIQUE NOT NULL, 
        password VARCHAR(255) NOT NULL, 
        phone VARCHAR(20), 
        address TEXT, 
        role ENUM('customer', 'admin') DEFAULT 'customer', 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
    )");
    
    echo "<p>Users table created successfully.</p>";
    
    // Create food_items table
    $pdo->exec("CREATE TABLE IF NOT EXISTS food_items (
        food_id INT AUTO_INCREMENT PRIMARY KEY, 
        name VARCHAR(100) NOT NULL, 
        description TEXT, 
        price DECIMAL(8,2) NOT NULL, 
        category ENUM('pizza', 'burger', 'colddrink') NOT NULL, 
        image_url VARCHAR(255), 
        available BOOLEAN DEFAULT TRUE, 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
    )");
    
    echo "<p>Food items table created successfully.</p>";
    
    // Create orders table
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        order_id INT AUTO_INCREMENT PRIMARY KEY, 
        user_id INT NOT NULL, 
        status ENUM('pending', 'confirmed', 'delivered', 'cancelled') DEFAULT 'pending', 
        total_amount DECIMAL(10,2) NOT NULL, 
        order_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE 
    )");
    
    echo "<p>Orders table created successfully.</p>";
    
    // Create order_details table
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_details (
        order_detail_id INT AUTO_INCREMENT PRIMARY KEY, 
        order_id INT NOT NULL, 
        food_id INT NOT NULL, 
        quantity INT NOT NULL DEFAULT 1, 
        price DECIMAL(8,2) NOT NULL, 
        FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE, 
        FOREIGN KEY (food_id) REFERENCES food_items(food_id) ON DELETE CASCADE 
    )");
    
    echo "<p>Order details table created successfully.</p>";
    
    // Create cart table
    $pdo->exec("CREATE TABLE IF NOT EXISTS cart (
        cart_id INT AUTO_INCREMENT PRIMARY KEY, 
        user_id INT NOT NULL, 
        food_id INT NOT NULL, 
        quantity INT NOT NULL DEFAULT 1, 
        UNIQUE KEY unique_cart (user_id, food_id), 
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE, 
        FOREIGN KEY (food_id) REFERENCES food_items(food_id) ON DELETE CASCADE 
    )");
    
    echo "<p>Cart table created successfully.</p>";
    
    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = 'admin@example.com'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // Insert admin user with hashed password
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, address, role) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['Admin', 'admin@example.com', $admin_password, '1234567890', 'Admin Address', 'admin']);
        
        echo "<p>Admin user created successfully.</p>";
    } else {
        echo "<p>Admin user already exists.</p>";
    }
    
    // Check if sample food items exist
    $stmt = $pdo->prepare("SELECT food_id FROM food_items LIMIT 1");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // Insert sample food items
        $food_items = [
            // Pizzas
            ['Margherita Pizza', 'Classic pizza with tomato sauce, mozzarella, and basil', 9.99, 'pizza', 'margherita.jpg'],
            ['Pepperoni Pizza', 'Pizza topped with pepperoni slices and cheese', 11.99, 'pizza', 'pepperoni.jpg'],
            ['Vegetarian Pizza', 'Pizza loaded with bell peppers, onions, mushrooms, and olives', 10.99, 'pizza', 'vegetarian.jpg'],
            ['Hawaiian Pizza', 'Pizza with ham and pineapple toppings', 12.99, 'pizza', 'hawaiian.jpg'],
            ['BBQ Chicken Pizza', 'Pizza with BBQ sauce, chicken, red onions, and cilantro', 13.99, 'pizza', 'bbq-chicken.jpg'],
            
            // Burgers
            ['Classic Burger', 'Beef patty with lettuce, tomato, onion, and special sauce', 8.99, 'burger', 'classic-burger.jpg'],
            ['Cheeseburger', 'Beef patty with American cheese, lettuce, tomato, and onion', 9.99, 'burger', 'cheeseburger.jpg'],
            ['Bacon Burger', 'Beef patty with bacon, cheese, lettuce, and tomato', 10.99, 'burger', 'bacon-burger.jpg'],
            ['Veggie Burger', 'Plant-based patty with lettuce, tomato, and special sauce', 9.99, 'burger', 'veggie-burger.jpg'],
            ['Chicken Burger', 'Grilled chicken breast with lettuce, tomato, and mayo', 9.99, 'burger', 'chicken-burger.jpg'],
            
            // Cold Drinks
            ['Cola', 'Refreshing cola drink', 1.99, 'colddrink', 'cola.jpg'],
            ['Diet Cola', 'Sugar-free cola drink', 1.99, 'colddrink', 'diet-cola.jpg'],
            ['Lemon Soda', 'Refreshing lemon-flavored soda', 1.99, 'colddrink', 'lemon-soda.jpg'],
            ['Orange Juice', 'Freshly squeezed orange juice', 2.99, 'colddrink', 'orange-juice.jpg'],
            ['Bottled Water', 'Pure mineral water', 1.49, 'colddrink', 'water.jpg']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO food_items (name, description, price, category, image_url) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($food_items as $item) {
            $stmt->execute($item);
        }
        
        echo "<p>Sample food items added successfully.</p>";
    } else {
        echo "<p>Food items already exist in the database.</p>";
    }
    
    echo "<p style='color: green;'>Database setup completed successfully!</p>";
    echo "<p>You can now proceed with using the website.</p>";
    echo "<p><a href='/pizza_delivery/public/index.php'>Go to Homepage</a></p>";
    
} catch (PDOException $e) {
    // Connection or query failed
    die("<p style='color: red;'>Database Setup Error: " . $e->getMessage() . "</p>");
}