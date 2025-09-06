<?php
/**
 * AJAX Handler for Updating Cart Items
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
        'message' => 'Please log in to update your cart',
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
$cart_id = filter_input(INPUT_POST, 'cart_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

// Validate data
if (!$cart_id || !$quantity || $quantity < 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid cart item or quantity'
    ]);
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

try {
    // Verify the cart item belongs to the user
    $stmt = $pdo->prepare(
        "SELECT c.*, f.name 
         FROM cart c 
         JOIN food_items f ON c.food_id = f.food_id 
         WHERE c.cart_id = ? AND c.user_id = ?"
    );
    $stmt->execute([$cart_id, $user_id]);
    $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cart_item) {
        echo json_encode([
            'success' => false,
            'message' => 'Cart item not found'
        ]);
        exit;
    }
    
    // Update quantity
    $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ?");
    $stmt->execute([$quantity, $cart_id]);
    
    // Get total items in cart for the user
    $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $cart_count = $result['total'] ?: 0;
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Cart updated successfully',
        'cart_count' => $cart_count
    ]);
    
} catch (PDOException $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}