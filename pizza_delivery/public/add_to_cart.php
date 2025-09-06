<?php
/**
 * AJAX Handler for Adding Items to Cart
 */

// Include functions file
require_once '../includes/functions.php';
require_once '../config/db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode([
        'success' => false,
        'message' => 'Please log in to add items to your cart',
        'redirect' => 'login.php'
    ]);
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get POST data
$food_id = filter_input(INPUT_POST, 'food_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT) ?: 1;

// Validate data
if (!$food_id || $quantity < 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid food item or quantity'
    ]);
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

try {
    // Check if food item exists and is available
    $stmt = $pdo->prepare("SELECT * FROM food_items WHERE food_id = ? AND available = 1");
    $stmt->execute([$food_id]);
    $food_item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$food_item) {
        echo json_encode([
            'success' => false,
            'message' => 'Food item not found or not available'
        ]);
        exit;
    }
    
    // Check if item already exists in cart
    $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND food_id = ?");
    $stmt->execute([$user_id, $food_id]);
    $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cart_item) {
        // Update quantity
        $new_quantity = $cart_item['quantity'] + $quantity;
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ?");
        $stmt->execute([$new_quantity, $cart_item['cart_id']]);
        
        $message = 'Cart updated! ' . htmlspecialchars($food_item['name']) . ' quantity increased to ' . $new_quantity;
    } else {
        // Add new item to cart
        $stmt = $pdo->prepare("INSERT INTO cart (user_id, food_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $food_id, $quantity]);
        
        $message = htmlspecialchars($food_item['name']) . ' added to your cart!';
    }
    
    // Get total items in cart for the user
    $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $cart_count = $result['total'] ?: 0;
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => $message,
        'cart_count' => $cart_count
    ]);
    
} catch (PDOException $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}