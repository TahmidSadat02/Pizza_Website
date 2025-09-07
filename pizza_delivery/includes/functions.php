<?php
/**
 * Utility Functions
 * 
 * This file contains utility functions used throughout the Pizza Delivery Website.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Clean input data to prevent XSS attacks
 * 
 * @param string $data The input data to be cleaned
 * @return string The cleaned data
 */
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Debug function to log messages to a file
 * 
 * @param mixed $data The data to log
 * @param string $label Optional label for the log entry
 */
function debug_log($data, $label = '') {
    $log_file = __DIR__ . '/../logs/debug.log';
    
    // Create logs directory if it doesn't exist
    if (!file_exists(__DIR__ . '/../logs')) {
        mkdir(__DIR__ . '/../logs', 0777, true);
    }
    
    // Format the log entry
    $log_entry = date('[Y-m-d H:i:s]') . ' ' . $label . ': ';
    
    if (is_array($data) || is_object($data)) {
        $log_entry .= print_r($data, true);
    } else {
        $log_entry .= $data;
    }
    
    // Append to log file
    file_put_contents($log_file, $log_entry . "\n", FILE_APPEND);
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 * 
 * @return bool True if user is admin, false otherwise
 */
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Get current logged in user information
 * 
 * @return array|null User data if logged in, null otherwise
 */
function getCurrentUser() {
    if (!is_logged_in()) {
        return null;
    }
    
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        debug_log($e->getMessage(), 'getCurrentUser Error');
        return null;
    }
}

/**
 * Redirect to a specific page
 * 
 * @param string $location The location to redirect to
 */
function redirect($location) {
    header("Location: $location");
    exit;
}

/**
 * Display error message
 * 
 * @param string $message The error message to display
 * @return string HTML for the error message
 */
function display_error($message) {
    return "<div class='alert alert-danger'>$message</div>";
}

/**
 * Display success message
 * 
 * @param string $message The success message to display
 * @return string HTML for the success message
 */
function display_success($message) {
    return "<div class='alert alert-success'>$message</div>";
}

/**
 * Get user by ID
 * 
 * @param PDO $pdo The PDO connection object
 * @param int $user_id The user ID
 * @return array|false The user data or false if not found
 */
function get_user_by_id($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/**
 * Get food item by ID
 * 
 * @param PDO $pdo The PDO connection object
 * @param int $food_id The food ID
 * @return array|false The food item data or false if not found
 */
function get_food_by_id($pdo, $food_id) {
    $stmt = $pdo->prepare("SELECT * FROM food_items WHERE food_id = ?");
    $stmt->execute([$food_id]);
    return $stmt->fetch();
}

/**
 * Get all food items
 * 
 * @param PDO $pdo The PDO connection object
 * @param string|null $category The category to filter by (optional)
 * @return array The food items
 */
function get_all_food_items($pdo, $category = null) {
    if ($category) {
        $stmt = $pdo->prepare("SELECT * FROM food_items WHERE category = ? AND available = 1 ORDER BY name");
        $stmt->execute([$category]);
    } else {
        $stmt = $pdo->query("SELECT * FROM food_items WHERE available = 1 ORDER BY category, name");
    }
    return $stmt->fetchAll();
}

/**
 * Get all food categories
 * 
 * @param PDO $pdo The PDO connection object
 * @return array The food categories
 */
function get_all_categories($pdo) {
    $stmt = $pdo->query("SELECT DISTINCT category FROM food_items WHERE available = 1 ORDER BY category");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Get cart items for a user
 * 
 * @param PDO $pdo The PDO connection object
 * @param int $user_id The user ID
 * @return array The cart items
 */
function get_cart_items($pdo, $user_id) {
    $stmt = $pdo->prepare(
        "SELECT c.*, f.name, f.price, f.image_url, (c.quantity * f.price) as subtotal 
         FROM cart c 
         JOIN food_items f ON c.food_id = f.food_id 
         WHERE c.user_id = ?"
    );
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

/**
 * Get cart total for a user
 * 
 * @param PDO $pdo The PDO connection object
 * @param int $user_id The user ID
 * @return float The cart total
 */
function get_cart_total($pdo, $user_id) {
    $stmt = $pdo->prepare(
        "SELECT SUM(c.quantity * f.price) as total 
         FROM cart c 
         JOIN food_items f ON c.food_id = f.food_id 
         WHERE c.user_id = ?"
    );
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

/**
 * Get order details
 * 
 * @param PDO $pdo The PDO connection object
 * @param int $order_id The order ID
 * @return array The order details
 */
function get_order_details($pdo, $order_id) {
    $stmt = $pdo->prepare(
        "SELECT od.*, f.name, f.image_url 
         FROM order_details od 
         JOIN food_items f ON od.food_id = f.food_id 
         WHERE od.order_id = ?"
    );
    $stmt->execute([$order_id]);
    return $stmt->fetchAll();
}

/**
 * Get user orders
 * 
 * @param PDO $pdo The PDO connection object
 * @param int $user_id The user ID
 * @return array The user orders
 */
function get_user_orders($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_time DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

/**
 * Format price with currency symbol
 * 
 * @param float $price The price to format
 * @return string The formatted price
 */
function format_price($price) {
    return '$' . number_format($price, 2);
}

/**
 * Get color class for order status
 * 
 * @param string $status The order status
 * @return string The Bootstrap color class
 */
function get_status_color($status) {
    $colors = [
        'pending' => 'warning',
        'confirmed' => 'info',
        'delivered' => 'success',
        'cancelled' => 'danger'
    ];
    
    return $colors[$status] ?? 'secondary';
}